<?php

namespace Tests\Feature;

use App\Enums\DocumentType;
use App\Enums\DocumentVerificationStatus;
use App\Enums\FileLifecycleStatus;
use App\Enums\FileVisibility;
use App\Enums\StaffRoleSlug;
use App\Models\Application;
use App\Models\CandidateDocument;
use App\Models\CandidateResume;
use App\Models\Job;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AccountCandidateResumeDocumentTest extends TestCase
{
    public function test_candidate_can_upload_resume_with_metadata_and_primary_flag(): void
    {
        $candidate = $this->makeCandidate();
        $profile = $candidate->candidateProfile;
        $this->assertNotNull($profile);

        $this->actingAs($candidate)
            ->post(route('account.resumes.store'), [
                'file' => $this->fakeResume('civil-cv.pdf'),
                'title' => 'Civil CV',
                'visibility' => FileVisibility::Private->value,
                'is_primary' => true,
            ])
            ->assertRedirect();

        $resume = CandidateResume::query()->where('candidate_profile_id', $profile->id)->first();
        $this->assertNotNull($resume);
        $this->assertSame('Civil CV', $resume->title);
        $this->assertTrue($resume->is_primary);
        $this->assertSame($profile->id, $resume->primary_owner_id);
        $this->assertSame(FileVisibility::Private, $resume->visibility);
        $this->assertSame(FileLifecycleStatus::Active, $resume->status);
        $this->assertSame('candidate_files', $resume->storage_disk);
        $this->assertStringStartsWith('resumes/', $resume->storage_path);
        $this->assertMatchesRegularExpression('/^ONT-V-\d{6}$/', (string) $resume->business_id);
        Storage::disk('candidate_files')->assertExists($resume->storage_path);
        $this->assertFalse(Storage::disk('resumes')->exists($resume->storage_path));
    }

    public function test_resume_upload_rejects_invalid_files(): void
    {
        $candidate = $this->makeCandidate();

        $this->actingAs($candidate)
            ->from(route('account.resumes.index'))
            ->post(route('account.resumes.store'), [
                'file' => $this->fakeResume('notes.txt'),
            ])
            ->assertSessionHasErrors('file');

        $this->assertSame(0, CandidateResume::query()->count());
    }

    public function test_switching_primary_resume_keeps_only_one_primary(): void
    {
        $candidate = $this->makeCandidate();

        $this->actingAs($candidate)->post(route('account.resumes.store'), [
            'file' => $this->fakeResume('one.pdf'),
            'title' => 'One',
        ]);
        $first = CandidateResume::query()->where('candidate_profile_id', $candidate->candidateProfile?->id)->first();
        $this->assertNotNull($first);
        $this->assertTrue($first->is_primary);

        $this->actingAs($candidate)->post(route('account.resumes.store'), [
            'file' => $this->fakeResume('two.pdf'),
            'title' => 'Two',
        ]);

        $this->assertSame(1, CandidateResume::query()->where('candidate_profile_id', $candidate->candidateProfile?->id)->count());
        $current = CandidateResume::query()->where('candidate_profile_id', $candidate->candidateProfile?->id)->first();
        $this->assertNotNull($current);
        $this->assertSame('Two', $current->title);
        $this->assertTrue($current->is_primary);
        $this->assertSame(1, CandidateResume::query()->where('is_primary', true)->count());
    }

    public function test_candidate_can_download_view_replace_archive_and_delete_own_resume(): void
    {
        $candidate = $this->makeCandidate();
        $this->actingAs($candidate)->post(route('account.resumes.store'), [
            'file' => $this->fakeResume('keep.pdf'),
            'title' => 'Keep',
        ]);
        $resume = CandidateResume::query()->first();
        $this->assertNotNull($resume);
        $originalPath = $resume->storage_path;

        $this->actingAs($candidate)
            ->get(route('account.resumes.download', $resume))
            ->assertOk();
        $this->actingAs($candidate)
            ->get(route('account.resumes.view', $resume))
            ->assertOk();

        $this->actingAs($candidate)
            ->post(route('account.resumes.replace', $resume), [
                'file' => $this->fakeResume('replaced.pdf'),
            ])
            ->assertRedirect();

        $resume->refresh();
        $this->assertNotSame($originalPath, $resume->storage_path);
        Storage::disk('candidate_files')->assertExists($resume->storage_path);
        Storage::disk('candidate_files')->assertMissing($originalPath);

        $this->actingAs($candidate)
            ->patch(route('account.resumes.archive', $resume))
            ->assertRedirect();
        $this->assertSame(FileLifecycleStatus::Archived, $resume->fresh()->status);
        $this->assertFalse($resume->fresh()->is_primary);

        $this->actingAs($candidate)
            ->delete(route('account.resumes.destroy', $resume))
            ->assertRedirect();
        $this->assertDatabaseMissing('candidate_resumes', ['id' => $resume->id]);
        Storage::disk('candidate_files')->assertMissing($resume->storage_path);
    }

    public function test_candidate_cannot_access_another_candidates_resume(): void
    {
        $owner = $this->makeCandidate();
        $other = $this->makeCandidate();
        $this->actingAs($owner)->post(route('account.resumes.store'), [
            'file' => $this->fakeResume('owner.pdf'),
            'title' => 'Owner',
        ]);
        $resume = CandidateResume::query()->first();
        $this->assertNotNull($resume);

        $this->actingAs($other)->get(route('account.resumes.download', $resume))->assertForbidden();
        $this->actingAs($other)->get(route('account.resumes.view', $resume))->assertForbidden();
        $this->actingAs($other)->patch(route('account.resumes.primary', $resume))->assertForbidden();
        $this->actingAs($other)->delete(route('account.resumes.destroy', $resume))->assertForbidden();
        $this->assertDatabaseHas('candidate_resumes', ['id' => $resume->id]);
    }

    public function test_employer_and_guest_cannot_access_resume_library(): void
    {
        $candidate = $this->makeCandidate();
        $this->actingAs($candidate)->post(route('account.resumes.store'), [
            'file' => $this->fakeResume('private.pdf'),
        ]);
        $this->actingAs($candidate)->post(route('account.documents.store'), [
            'file' => $this->fakeDocument('private-doc.pdf'),
            'document_type' => DocumentType::Other->value,
        ]);
        $resume = CandidateResume::query()->first();
        $document = CandidateDocument::query()->first();
        $this->assertNotNull($resume);
        $this->assertNotNull($document);

        $this->actingAs($this->makeEmployer())
            ->get(route('account.resumes.index'))
            ->assertForbidden();
        $this->actingAs($this->makeEmployer())
            ->get(route('account.resumes.download', $resume))
            ->assertForbidden();
        $this->actingAs($this->makeEmployer())
            ->get(route('account.documents.download', $document))
            ->assertForbidden();

        auth()->logout();
        $this->flushSession();
        $this->get(route('account.resumes.download', $resume))->assertRedirect(route('login'));
        $this->get(route('account.documents.download', $document))->assertRedirect(route('login'));
    }

    public function test_candidate_can_manage_each_document_type_with_dates_and_private_storage(): void
    {
        $candidate = $this->makeCandidate();

        foreach (DocumentType::cases() as $type) {
            $this->actingAs($candidate)
                ->post(route('account.documents.store'), [
                    'file' => $this->fakeDocument($type->value.'.pdf'),
                    'document_type' => $type->value,
                    'title' => $type->label().' file',
                    'visibility' => FileVisibility::Private->value,
                    'issue_date' => '2024-01-01',
                    'expiry_date' => '2025-01-01',
                ])
                ->assertRedirect();
        }

        $this->assertSame(6, CandidateDocument::query()->count());
        $document = CandidateDocument::query()->first();
        $this->assertNotNull($document);
        $this->assertSame(DocumentVerificationStatus::Unverified, $document->verification_status);
        $this->assertNull($document->verified_at);
        $this->assertSame('candidate_files', $document->storage_disk);
        $this->assertStringStartsWith('documents/', $document->storage_path);
        $this->assertMatchesRegularExpression('/^ONT-D-\d{6}$/', (string) $document->business_id);
        Storage::disk('candidate_files')->assertExists($document->storage_path);

        $this->actingAs($candidate)
            ->from(route('account.documents.index'))
            ->post(route('account.documents.store'), [
                'file' => $this->fakeDocument('bad.pdf'),
                'document_type' => DocumentType::Licence->value,
                'issue_date' => '2025-01-01',
                'expiry_date' => '2024-01-01',
            ])
            ->assertSessionHasErrors('expiry_date');
    }

    public function test_candidate_can_download_replace_and_delete_own_document(): void
    {
        $candidate = $this->makeCandidate();
        $this->actingAs($candidate)->post(route('account.documents.store'), [
            'file' => $this->fakeDocument('cert.pdf'),
            'document_type' => DocumentType::Certificate->value,
            'title' => 'CSCS',
        ]);
        $document = CandidateDocument::query()->first();
        $this->assertNotNull($document);
        $path = $document->storage_path;

        $this->actingAs($candidate)->get(route('account.documents.download', $document))->assertOk();
        $this->actingAs($candidate)->post(route('account.documents.replace', $document), [
            'file' => $this->fakeDocument('cert-new.pdf'),
        ])->assertRedirect();
        $this->assertNotSame($path, $document->fresh()->storage_path);
        $this->assertSame(DocumentVerificationStatus::Unverified, $document->fresh()->verification_status);
        $this->actingAs($candidate)->delete(route('account.documents.destroy', $document))->assertRedirect();
        $this->assertDatabaseMissing('candidate_documents', ['id' => $document->id]);
    }

    public function test_candidate_cannot_access_another_candidates_documents(): void
    {
        $owner = $this->makeCandidate();
        $other = $this->makeCandidate();
        $this->actingAs($owner)->post(route('account.documents.store'), [
            'file' => $this->fakeDocument('owner-doc.pdf'),
            'document_type' => DocumentType::Payslip->value,
        ]);
        $document = CandidateDocument::query()->first();
        $this->assertNotNull($document);

        $this->actingAs($other)->get(route('account.documents.download', $document))->assertForbidden();
        $this->actingAs($other)->delete(route('account.documents.destroy', $document))->assertForbidden();
        $this->assertDatabaseHas('candidate_documents', ['id' => $document->id]);
    }

    public function test_resume_builder_loads_existing_profile_data_without_duplicating_it(): void
    {
        $candidate = $this->makeCandidate([
            'name' => 'Builder Candidate',
            'bio' => 'Site engineer bio',
            'location' => 'Hyderabad',
        ]);
        $profile = $candidate->candidateProfile;
        $this->assertNotNull($profile);
        $profile->update([
            'current_designation' => 'Site Engineer',
            'current_company' => 'Apex Civil Works',
        ]);

        $this->actingAs($candidate)
            ->get(route('account.resumes.builder'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Resumes/Builder')
                ->where('preview.personal.name', 'Builder Candidate')
                ->where('preview.professional.current_designation', 'Site Engineer')
                ->where('preview.professional.current_company', 'Apex Civil Works')
                ->missing('preview.professional.current_salary'));

        $this->assertSame(0, CandidateResume::query()->count());
        $this->assertDatabaseHas('candidate_profiles', [
            'id' => $profile->id,
            'current_designation' => 'Site Engineer',
        ]);
    }

    public function test_admin_can_see_and_download_library_files_but_finance_cannot(): void
    {
        $candidate = $this->makeCandidate(['name' => 'Admin File Candidate']);
        $this->actingAs($candidate)->post(route('account.resumes.store'), [
            'file' => $this->fakeResume('admin.pdf'),
            'title' => 'Admin visible CV',
        ]);
        $this->actingAs($candidate)->post(route('account.documents.store'), [
            'file' => $this->fakeDocument('licence.pdf'),
            'document_type' => DocumentType::Licence->value,
            'title' => 'Driving licence',
        ]);
        $resume = CandidateResume::query()->first();
        $document = CandidateDocument::query()->first();
        $this->assertNotNull($resume);
        $this->assertNotNull($document);

        $admin = $this->makeAdmin();
        $this->actingAs($admin)
            ->get(route('admin.candidates.index', ['candidate' => $candidate->candidateProfile?->id, 'search' => 'Admin File']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Candidates/Index')
                ->has('documents.library_resumes', 1)
                ->where('documents.library_resumes.0.title', 'Admin visible CV')
                ->has('documents.licences', 1)
                ->where('documents.licences.0.title', 'Driving licence'));

        $this->actingAs($admin)->get(route('admin.candidates.resumes.show', $resume))->assertOk();
        $this->actingAs($admin)->get(route('admin.candidates.documents.show', $document))->assertOk();

        $finance = $this->makeStaff(StaffRoleSlug::Finance->value);
        $this->actingAs($finance)->get(route('admin.candidates.resumes.show', $resume))->assertForbidden();
        $this->actingAs($finance)->get(route('admin.candidates.documents.show', $document))->assertForbidden();
    }

    public function test_library_operations_do_not_change_application_resume_snapshots(): void
    {
        $candidate = $this->makeCandidate();
        $path = 'application-snapshot.pdf';
        Storage::disk('resumes')->put($path, 'application-bytes');
        $application = Application::factory()->create([
            'user_id' => $candidate->id,
            'email' => $candidate->email,
            'resume_path' => $path,
        ]);

        $this->actingAs($candidate)->post(route('account.resumes.store'), [
            'file' => $this->fakeResume('library.pdf'),
            'title' => 'Library CV',
        ]);
        $library = CandidateResume::query()->first();
        $this->assertNotNull($library);

        $this->actingAs($candidate)->delete(route('account.resumes.destroy', $library))->assertRedirect();

        $this->assertSame($path, $application->fresh()->getAttributes()['resume_path']);
        Storage::disk('resumes')->assertExists($path);
        $this->actingAs($candidate)->get(route('account.applications.resume', $application))->assertOk();
        $this->actingAs($candidate)->post(route('account.applications.resume.replace', $application), [
            'resume' => $this->fakeResume('updated-application.pdf'),
        ])->assertRedirect();
        $this->assertNotSame($path, $application->fresh()->getAttributes()['resume_path']);
        Storage::disk('resumes')->assertExists($application->fresh()->getAttributes()['resume_path']);
    }

    public function test_guest_and_candidate_applications_still_work(): void
    {
        $job = Job::factory()->create();

        $this->post(route('jobs.apply.store', $job))->assertRedirect(route('login'));
        $this->assertDatabaseMissing('applications', [
            'email' => 'v12c.guest@example.com',
        ]);

        $candidate = $this->makeCandidate(['email' => 'v12c.candidate.apply@example.com']);
        $this->applyAsCandidate($candidate, Job::factory()->create());

        $this->assertDatabaseHas('applications', [
            'email' => 'v12c.candidate.apply@example.com',
            'user_id' => $candidate->id,
        ]);
        $this->assertSame(1, CandidateResume::query()->count());
        $this->assertTrue($candidate->isCandidate());
        $this->assertInstanceOf(User::class, $candidate);
        $this->assertNotNull($candidate->candidateProfile);
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasColumn('users', 'resume_path'));
    }

    public function test_profile_completion_is_not_changed_by_optional_files(): void
    {
        $candidate = $this->makeCandidate([
            'email_verified_at' => null,
            'mobile' => null,
            'location' => null,
            'bio' => null,
            'photo_path' => null,
        ]);
        $before = $candidate->fresh()->calculateProfileCompletion();

        $this->actingAs($candidate)->post(route('account.resumes.store'), [
            'file' => $this->fakeResume('score.pdf'),
        ]);
        $this->actingAs($candidate)->post(route('account.documents.store'), [
            'file' => $this->fakeDocument('score-doc.pdf'),
            'document_type' => DocumentType::Payslip->value,
        ]);

        $this->assertSame($before, $candidate->fresh()->calculateProfileCompletion());
        $this->assertLessThanOrEqual(100, $candidate->fresh()->calculateProfileCompletion());
    }
}
