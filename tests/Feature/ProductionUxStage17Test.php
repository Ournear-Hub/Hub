<?php

namespace Tests\Feature;

use App\Enums\BusinessIdPrefix;
use App\Enums\DocumentType;
use App\Enums\DocumentVerificationStatus;
use App\Enums\EmployerDocumentType;
use App\Enums\FileLifecycleStatus;
use App\Enums\PermissionName;
use App\Enums\StaffRoleSlug;
use App\Enums\UserActivityType;
use App\Models\Application;
use App\Models\CandidateDocument;
use App\Models\EmployerDocument;
use App\Models\Job;
use App\Models\User;
use App\Models\UserActivity;
use App\Support\EmployerPublicShare;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProductionUxStage17Test extends TestCase
{
    public function test_employer_can_manage_own_company_documents_with_edc_business_ids(): void
    {
        $employer = $this->eligibleEmployer();
        $profile = $employer->employerProfile;
        $this->assertNotNull($profile);

        $this->actingAs($employer)
            ->get(route('account.employer.documents.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Employer/Documents/Index')
                ->has('documents', 0)
                ->where('types.0.value', EmployerDocumentType::CompanyDocument->value));

        $this->actingAs($employer)->post(route('account.employer.documents.store'), [
            'file' => $this->fakeDocument('company-pack.pdf'),
            'document_type' => EmployerDocumentType::CompanyDocument->value,
            'title' => 'Company pack',
            'issue_date' => now()->toDateString(),
            'expiry_date' => now()->addYear()->toDateString(),
            'employer_id' => 999999,
            'verification_status' => DocumentVerificationStatus::Verified->value,
            'storage_path' => '../secret.pdf',
            'storage_disk' => 'public',
        ])->assertRedirect();

        $first = EmployerDocument::query()->where('employer_profile_id', $profile->id)->first();
        $this->assertNotNull($first);
        $this->assertSame('ONT-EDC-000001', $first->business_id);
        $this->assertSame($profile->id, $first->employer_profile_id);
        $this->assertSame(EmployerDocumentType::CompanyDocument, $first->document_type);
        $this->assertSame(DocumentVerificationStatus::Unverified, $first->verification_status);
        $this->assertSame(FileLifecycleStatus::Active, $first->status);
        $this->assertSame('employer_files', $first->storage_disk);
        $this->assertStringStartsWith('documents/', $first->storage_path);
        $this->assertStringNotContainsString('..', $first->storage_path);
        $this->assertSame('Company pack', $first->title);
        Storage::disk('employer_files')->assertExists($first->storage_path);
        $this->assertFalse(Storage::disk('candidate_files')->exists($first->storage_path));
        $this->assertFalse(Storage::disk('resumes')->exists($first->storage_path));
        $this->assertFalse(Storage::disk('public')->exists($first->storage_path));

        $this->actingAs($employer)->post(route('account.employer.documents.store'), [
            'file' => $this->fakeDocument('agreement.pdf'),
            'document_type' => EmployerDocumentType::Agreement->value,
            'title' => 'Company agreement',
        ])->assertRedirect();

        $second = EmployerDocument::query()->where('title', 'Company agreement')->first();
        $this->assertNotNull($second);
        $this->assertSame('ONT-EDC-000002', $second->business_id);

        $this->actingAs($employer)
            ->get(route('account.employer.documents.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Employer/Documents/Index')
                ->has('documents', 2)
                ->where('documents.0.business_id', 'ONT-EDC-000002')
                ->missing('documents.0.review_notes')
                ->missing('documents.0.verified_by_name')
                ->missing('documents.0.storage_path'));

        $download = $this->actingAs($employer)->get(route('account.employer.documents.download', $first));
        $download->assertOk();
        $download->assertDontSee($first->storage_path, false);
        $this->actingAs($employer)->get(route('account.employer.documents.view', $first))->assertOk();

        $this->actingAs($employer)->post(route('account.employer.documents.replace', $first), [
            'file' => $this->fakeDocument('company-pack-v2.pdf'),
        ])->assertRedirect();
        $first->refresh();
        $this->assertSame(DocumentVerificationStatus::Unverified, $first->verification_status);
        $this->assertNull($first->verified_by);
        $this->assertNull($first->verified_at);

        $this->actingAs($employer)->put(route('account.employer.documents.update', $first), [
            'status' => FileLifecycleStatus::Archived->value,
            'verification_status' => DocumentVerificationStatus::Verified->value,
        ])->assertRedirect();
        $this->assertSame(FileLifecycleStatus::Archived, $first->fresh()->status);
        $this->assertSame(DocumentVerificationStatus::Unverified, $first->fresh()->verification_status);

        $this->assertTrue(
            UserActivity::query()
                ->where('user_id', $employer->id)
                ->where('type', UserActivityType::UploadedDocument->value)
                ->exists()
        );

        $this->assertSame('ONT-U', BusinessIdPrefix::User->value);
        $this->assertSame('ONT-E', BusinessIdPrefix::Employer->value);
        $this->assertSame('ONT-D', BusinessIdPrefix::Document->value);
        $this->assertSame('ONT-V', BusinessIdPrefix::Resume->value);
        $this->assertSame('ONT-EDC', BusinessIdPrefix::EmployerDocument->value);
        $this->assertMatchesRegularExpression('/^ONT-E-\d{6}$/', (string) $profile->fresh()->business_id);
        $this->assertCount(34, PermissionName::cases());
        $this->assertNotContains('payslip', array_column(EmployerDocumentType::options(), 'value'));
        $this->assertNotContains('experience', array_column(EmployerDocumentType::options(), 'value'));
    }

    public function test_employer_cannot_access_another_company_document_by_id_business_id_or_spoofed_owner(): void
    {
        $owner = $this->eligibleEmployer();
        $other = $this->eligibleEmployer();
        $this->assertNotNull($owner->employerProfile);
        $this->assertNotNull($other->employerProfile);

        $this->actingAs($owner)->post(route('account.employer.documents.store'), [
            'file' => $this->fakeDocument('owner.pdf'),
            'document_type' => EmployerDocumentType::CompanyDocument->value,
            'title' => 'Owner file',
        ])->assertRedirect();

        $document = EmployerDocument::query()
            ->where('employer_profile_id', $owner->employerProfile->id)
            ->first();
        $this->assertNotNull($document);

        $this->actingAs($other)->get(route('account.employer.documents.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('documents', 0));

        $this->actingAs($other)->get(route('account.employer.documents.download', $document))->assertForbidden();
        $this->actingAs($other)->get(route('account.employer.documents.view', $document))->assertForbidden();
        $this->actingAs($other)->put(route('account.employer.documents.update', $document), [
            'title' => 'Hijacked',
        ])->assertForbidden();
        $this->actingAs($other)->post(route('account.employer.documents.replace', $document), [
            'file' => $this->fakeDocument('stolen.pdf'),
        ])->assertForbidden();
        $this->actingAs($other)->delete(route('account.employer.documents.destroy', $document))->assertForbidden();

        $this->actingAs($other)->post(route('account.employer.documents.store'), [
            'file' => $this->fakeDocument('other.pdf'),
            'document_type' => EmployerDocumentType::Other->value,
            'title' => 'Other company file',
            'employer_id' => $owner->employerProfile->id,
            'employer_profile_id' => $owner->employerProfile->id,
        ])->assertRedirect();

        $this->assertTrue(
            EmployerDocument::query()
                ->where('title', 'Other company file')
                ->where('employer_profile_id', $other->employerProfile->id)
                ->exists()
        );
        $this->assertFalse(
            EmployerDocument::query()
                ->where('title', 'Other company file')
                ->where('employer_profile_id', $owner->employerProfile->id)
                ->exists()
        );

        $this->actingAs($other)
            ->get('/account/company-documents/'.$document->business_id)
            ->assertNotFound();
    }

    public function test_candidate_guest_and_public_company_page_cannot_access_employer_documents(): void
    {
        $employer = $this->eligibleEmployer();
        $profile = $employer->employerProfile;
        $this->assertNotNull($profile);
        $profile->forceFill([
            'company_name' => 'Public Docs Co',
            'public_share' => EmployerPublicShare::normalize(['enabled' => true]),
        ])->save();

        $this->actingAs($employer)->post(route('account.employer.documents.store'), [
            'file' => $this->fakeDocument('private.pdf'),
            'document_type' => EmployerDocumentType::CompanyDocument->value,
            'title' => 'Private company file',
        ])->assertRedirect();

        $document = EmployerDocument::query()->where('employer_profile_id', $profile->id)->first();
        $this->assertNotNull($document);

        $candidate = $this->makeCandidate();
        $this->actingAs($candidate)
            ->get(route('account.employer.documents.index'))
            ->assertRedirect(route('account.hiring', ['destination' => '/account/company-documents']));
        $this->actingAs($candidate)
            ->get(route('account.employer.documents.download', $document))
            ->assertRedirect(route('account.hiring', ['destination' => '/account/company-documents/'.$document->id.'/download']));
        $this->actingAs($candidate)->post(route('account.employer.documents.store'), [
            'file' => $this->fakeDocument('candidate.pdf'),
            'document_type' => EmployerDocumentType::CompanyDocument->value,
        ])->assertForbidden();

        $this->forgetAuthentication();
        $this->get(route('account.employer.documents.index'))->assertRedirect(route('login'));
        $this->get(route('account.employer.documents.download', $document))->assertRedirect(route('login'));

        $this->get(route('companies.show', $profile))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Companies/Show')
                ->where('company.company_name', 'Public Docs Co')
                ->missing('company.documents')
                ->missing('company.storage_path'));
    }

    public function test_employer_remains_denied_from_candidate_files_and_keeps_snapshot_ownership(): void
    {
        $owner = $this->eligibleEmployer();
        $other = $this->eligibleEmployer();
        $candidate = $this->makeCandidate();
        $resume = $this->giveCandidateResume($candidate, 'library.pdf');

        $this->actingAs($candidate)->post(route('account.documents.store'), [
            'file' => $this->fakeDocument('certificate.pdf'),
            'document_type' => DocumentType::Certificate->value,
            'title' => 'Safety certificate',
        ])->assertRedirect();

        $candidateDocument = CandidateDocument::query()
            ->where('candidate_profile_id', $candidate->fresh()->candidateProfile?->id)
            ->first();
        $this->assertNotNull($candidateDocument);

        $this->actingAs($owner)->get(route('account.resumes.index'))->assertForbidden();
        $this->actingAs($owner)->get(route('account.resumes.download', $resume))->assertForbidden();
        $this->actingAs($owner)->get(route('account.documents.index'))->assertForbidden();
        $this->actingAs($owner)->get(route('account.documents.download', $candidateDocument))->assertForbidden();

        $job = Job::factory()->create(['employer_id' => $owner->employerProfile?->id]);
        $application = Application::factory()->create([
            'job_id' => $job->id,
            'user_id' => $candidate->id,
            'resume_path' => 'applications/owner-snapshot.pdf',
        ]);
        Storage::disk('resumes')->put('applications/owner-snapshot.pdf', '%PDF-1.4');

        $this->actingAs($owner)->get(route('account.employer.applications.resume', $application))->assertOk();
        $this->actingAs($other)->get(route('account.employer.applications.resume', $application))->assertForbidden();
    }

    public function test_admin_permissions_control_employer_document_view_and_review(): void
    {
        $employer = $this->eligibleEmployer();
        $profile = $employer->employerProfile;
        $this->assertNotNull($profile);

        $this->actingAs($employer)->post(route('account.employer.documents.store'), [
            'file' => $this->fakeDocument('review.pdf'),
            'document_type' => EmployerDocumentType::Certificate->value,
            'title' => 'Company certificate',
        ])->assertRedirect();

        $document = EmployerDocument::query()->where('employer_profile_id', $profile->id)->first();
        $this->assertNotNull($document);

        $viewer = $this->makeStaff(StaffRoleSlug::HrOperations->value);
        $this->actingAs($viewer)
            ->get(route('admin.employers.index', ['employer' => $profile->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Employers/Index')
                ->where('can_manage', false)
                ->has('documents', 1)
                ->where('documents.0.title', 'Company certificate')
                ->where('documents.0.verification_status', DocumentVerificationStatus::Unverified->value));

        $this->actingAs($viewer)
            ->get(route('admin.employers.documents.show', ['employer' => $profile, 'document' => $document]))
            ->assertOk();

        $this->actingAs($viewer)
            ->patch(route('admin.employers.documents.verification', ['employer' => $profile, 'document' => $document]), [
                'action' => 'verify',
            ])
            ->assertForbidden();

        $manager = $this->makeAdmin();
        $this->actingAs($manager)
            ->patch(route('admin.employers.documents.verification', ['employer' => $profile, 'document' => $document]), [
                'action' => 'verify',
                'review_notes' => 'Matches company records.',
            ])
            ->assertRedirect();

        $document->refresh();
        $this->assertSame(DocumentVerificationStatus::Verified, $document->verification_status);
        $this->assertSame($manager->id, $document->verified_by);
        $this->assertNotNull($document->verified_at);
        $this->assertSame('Matches company records.', $document->review_notes);

        $this->actingAs($manager)
            ->patch(route('admin.employers.documents.verification', ['employer' => $profile, 'document' => $document]), [
                'action' => 'reject',
                'review_notes' => 'Needs a clearer scan.',
            ])
            ->assertRedirect();
        $this->assertSame(DocumentVerificationStatus::Rejected, $document->fresh()->verification_status);

        $finance = $this->makeStaff(StaffRoleSlug::Finance->value);
        $this->actingAs($finance)
            ->get(route('admin.employers.index', ['employer' => $profile->id]))
            ->assertForbidden();
        $this->actingAs($finance)
            ->get(route('admin.employers.documents.show', ['employer' => $profile, 'document' => $document]))
            ->assertForbidden();

        $this->actingAs($employer)
            ->patch(route('admin.employers.documents.verification', ['employer' => $profile, 'document' => $document]), [
                'action' => 'verify',
            ])
            ->assertForbidden();

        $other = $this->eligibleEmployer();
        $this->actingAs($manager)
            ->get(route('admin.employers.documents.show', [
                'employer' => $other->employerProfile,
                'document' => $document,
            ]))
            ->assertForbidden();
    }

    public function test_employer_document_file_validation_and_wrong_disk_are_rejected(): void
    {
        $employer = $this->eligibleEmployer();
        $profile = $employer->employerProfile;
        $this->assertNotNull($profile);

        $this->actingAs($employer)
            ->from(route('account.employer.documents.index'))
            ->post(route('account.employer.documents.store'), [
                'file' => $this->fakeDocument('notes.txt'),
                'document_type' => EmployerDocumentType::CompanyDocument->value,
            ])
            ->assertSessionHasErrors('file');

        $this->actingAs($employer)
            ->from(route('account.employer.documents.index'))
            ->post(route('account.employer.documents.store'), [
                'file' => UploadedFile::fake()->create('huge.pdf', 6001),
                'document_type' => EmployerDocumentType::CompanyDocument->value,
            ])
            ->assertSessionHasErrors('file');

        $this->actingAs($employer)->post(route('account.employer.documents.store'), [
            'file' => UploadedFile::fake()->image('scan.jpg'),
            'document_type' => EmployerDocumentType::Other->value,
            'title' => 'Scan',
        ])->assertRedirect();

        $document = EmployerDocument::query()->where('title', 'Scan')->first();
        $this->assertNotNull($document);
        Storage::disk('employer_files')->assertExists($document->storage_path);

        $wrong = EmployerDocument::factory()->create([
            'employer_profile_id' => $profile->id,
            'storage_disk' => 'candidate_files',
            'storage_path' => 'documents/wrong.pdf',
        ]);
        Storage::disk('candidate_files')->put('documents/wrong.pdf', '%PDF-1.4');
        $this->actingAs($employer)->get(route('account.employer.documents.download', $wrong))->assertNotFound();

        Storage::disk('employer_files')->delete($document->storage_path);
        $this->actingAs($employer)->get(route('account.employer.documents.download', $document))->assertNotFound();

        $this->actingAs($employer)
            ->from(route('account.employer.documents.index'))
            ->post(route('account.employer.documents.store'), [
                'file' => $this->fakeDocument('../../etc/passwd.pdf'),
                'document_type' => EmployerDocumentType::CompanyDocument->value,
                'title' => 'Traversal',
            ])
            ->assertRedirect();

        $safe = EmployerDocument::query()->where('title', 'Traversal')->first();
        $this->assertNotNull($safe);
        $this->assertStringStartsWith('documents/', $safe->storage_path);
        $this->assertStringNotContainsString('..', $safe->storage_path);
        $this->assertSame('passwd.pdf', $safe->original_filename);
    }

    private function eligibleEmployer(): User
    {
        return $this->makeEmployer([
            'email_verified_at' => now(),
            'mobile_verified_at' => now(),
        ]);
    }
}
