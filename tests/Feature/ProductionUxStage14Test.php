<?php

namespace Tests\Feature;

use App\Enums\BusinessIdPrefix;
use App\Enums\LanguageProficiency;
use App\Enums\NoticePeriod;
use App\Enums\StaffRoleSlug;
use App\Models\Application;
use App\Models\CandidateCertification;
use App\Models\CandidateEmploymentHistory;
use App\Models\CandidateLanguage;
use App\Models\CandidateResume;
use App\Models\Industry;
use App\Models\Job;
use App\Models\Skill;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProductionUxStage14Test extends TestCase
{
    public function test_builder_projects_live_profile_without_salary_or_invented_fields(): void
    {
        $candidate = $this->makeCandidate([
            'name' => 'Stage Fourteen Candidate',
            'username' => 'stage-fourteen-candidate',
            'email' => 'stage14@example.com',
            'mobile' => '07123456789',
            'location' => 'Leeds',
            'bio' => 'Civil professional available for UK site roles.',
        ]);
        $profile = $candidate->candidateProfile;
        $this->assertNotNull($profile);

        $industry = Industry::query()->firstOrCreate(
            ['name' => 'Stage Fourteen Construction'],
            ['is_active' => true, 'sort_order' => 92],
        );
        $skill = Skill::findOrCreateFromName('Setting out');
        $profile->skills()->sync([$skill->id]);
        $profile->update([
            'current_designation' => 'Site Engineer',
            'current_company' => 'Harbor Works',
            'industry_id' => $industry->id,
            'experience' => '3-5 years',
            'qualification' => 'HNC Civil Engineering',
            'current_salary' => 42000,
            'expected_salary' => 48000,
            'notice_period' => NoticePeriod::Days30,
            'willing_to_relocate' => true,
            'availability' => 'Immediate',
        ]);
        CandidateEmploymentHistory::query()->create([
            'candidate_profile_id' => $profile->id,
            'company' => 'Harbor Works',
            'designation' => 'Site Engineer',
            'industry_id' => $industry->id,
            'started_on' => '2022-03-01',
            'ended_on' => null,
            'is_current' => true,
            'description' => 'Recorded site engineering duties.',
        ]);
        CandidateCertification::query()->create([
            'candidate_profile_id' => $profile->id,
            'name' => 'SSSTS',
            'issuer' => 'CITB',
            'issued_on' => '2023-01-15',
        ]);
        CandidateLanguage::query()->create([
            'candidate_profile_id' => $profile->id,
            'name' => 'English',
            'proficiency' => LanguageProficiency::Native,
        ]);

        $expectedItems = collect($candidate->fresh()->profileCompletionItems())->pluck('key')->all();

        $this->actingAs($candidate)
            ->get(route('account.resumes.builder'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Resumes/Builder')
                ->where('preview.personal.name', 'Stage Fourteen Candidate')
                ->missing('preview.personal.username')
                ->where('preview.personal.user_business_id', $candidate->business_id)
                ->where('preview.personal.candidate_business_id', $profile->business_id)
                ->where('preview.personal.designation', 'Site Engineer')
                ->where('preview.personal.bio', 'Civil professional available for UK site roles.')
                ->where('preview.professional.current_company', 'Harbor Works')
                ->where('preview.professional.industry', 'Stage Fourteen Construction')
                ->where('preview.professional.experience', '3-5 years')
                ->where('preview.professional.qualification', 'HNC Civil Engineering')
                ->where('preview.professional.notice_period_label', '30 days')
                ->where('preview.professional.relocation_summary', 'Willing to relocate')
                ->where('preview.professional.availability', 'Immediate')
                ->where('preview.professional.skills.0.name', 'Setting out')
                ->where('preview.professional.employment_histories.0.company', 'Harbor Works')
                ->where('preview.professional.employment_histories.0.description', 'Recorded site engineering duties.')
                ->where('preview.professional.certifications.0.name', 'SSSTS')
                ->where('preview.professional.languages.0.name', 'English')
                ->where('preview.professional.languages.0.proficiency_label', 'Native / bilingual')
                ->missing('preview.professional.current_salary')
                ->missing('preview.professional.expected_salary')
                ->has('completion_items')
                ->where('completion_items', fn ($items) => collect($items)->pluck('key')->all() === $expectedItems)
                ->where('profile_completion', (int) $candidate->fresh()->profile_completion));

        $this->assertMatchesRegularExpression('/^ONT-U-\d{6}$/', (string) $candidate->business_id);
        $this->assertMatchesRegularExpression('/^ONT-C-\d{6}$/', (string) $profile->business_id);
    }

    public function test_builder_omits_unrecorded_optional_profile_fields(): void
    {
        $candidate = $this->makeCandidate([
            'name' => 'Sparse Resume Candidate',
            'username' => 'sparse-resume-candidate',
            'bio' => null,
        ]);
        $profile = $candidate->candidateProfile;
        $this->assertNotNull($profile);
        $profile->update([
            'current_designation' => null,
            'current_company' => null,
            'industry_id' => null,
            'qualification' => null,
            'notice_period' => null,
            'willing_to_relocate' => false,
            'availability' => null,
        ]);

        $this->actingAs($candidate)
            ->get(route('account.resumes.builder'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Resumes/Builder')
                ->where('preview.personal.bio', null)
                ->where('preview.professional.qualification', null)
                ->where('preview.professional.relocation_summary', null)
                ->where('preview.professional.certifications', [])
                ->where('preview.professional.languages', [])
                ->where('preview.professional.employment_histories', [])
                ->where('preview.professional.skills', [])
                ->missing('preview.professional.current_salary'));
    }

    public function test_existing_resume_ids_and_library_isolation_remain_unchanged(): void
    {
        $this->assertSame('ONT-U', BusinessIdPrefix::User->value);
        $this->assertSame('ONT-C', BusinessIdPrefix::Candidate->value);
        $this->assertSame('ONT-V', BusinessIdPrefix::Resume->value);

        $candidate = $this->makeCandidate();
        $path = 'stage14-application-snapshot.pdf';
        Storage::disk('resumes')->put($path, 'application-bytes');
        $application = Application::factory()->create([
            'user_id' => $candidate->id,
            'email' => $candidate->email,
            'resume_path' => $path,
        ]);

        $this->actingAs($candidate)->post(route('account.resumes.store'), [
            'file' => $this->fakeResume('library-one.pdf'),
            'title' => 'Library One',
            'is_primary' => true,
        ])->assertRedirect();
        $this->actingAs($candidate)->post(route('account.resumes.store'), [
            'file' => $this->fakeResume('library-two.pdf'),
            'title' => 'Library Two',
        ])->assertRedirect();

        $active = CandidateResume::query()
            ->where('candidate_profile_id', $candidate->candidateProfile?->id)
            ->where('status', \App\Enums\FileLifecycleStatus::Active)
            ->get();
        $this->assertCount(1, $active);
        $resume = $active->first();
        $this->assertNotNull($resume);
        $this->assertMatchesRegularExpression('/^ONT-V-\d{6}$/', (string) $resume->business_id);
        $this->assertSame('library-two.pdf', $resume->original_filename);
        $this->assertTrue($resume->is_primary);

        $this->actingAs($candidate)->post(route('account.resumes.replace', $resume), [
            'file' => $this->fakeResume('library-two-replaced.pdf'),
        ])->assertRedirect();
        $this->assertSame('library-two-replaced.pdf', $resume->fresh()->original_filename);

        $this->assertSame($path, $application->fresh()->getAttributes()['resume_path']);
        Storage::disk('resumes')->assertExists($path);
        $this->actingAs($candidate)->get(route('account.resumes.builder'))->assertOk();
    }

    public function test_employer_cannot_open_builder_or_global_resume_library(): void
    {
        $employer = $this->makeEmployer();
        $candidate = $this->makeCandidate();
        $this->actingAs($candidate)->post(route('account.resumes.store'), [
            'file' => $this->fakeResume('private.pdf'),
            'title' => 'Private CV',
        ]);
        $resume = CandidateResume::query()->first();
        $this->assertNotNull($resume);

        $this->actingAs($employer)->get(route('account.resumes.builder'))->assertForbidden();
        $this->actingAs($employer)->get(route('account.resumes.index'))->assertForbidden();
        $this->actingAs($employer)->get(route('account.resumes.download', $resume))->assertForbidden();
        $this->actingAs($this->makeStaff(StaffRoleSlug::Finance->value))
            ->get(route('admin.candidates.resumes.show', $resume))
            ->assertForbidden();

        $admin = $this->makeAdmin();
        $this->actingAs($admin)->get(route('admin.candidates.resumes.show', $resume))->assertOk();
    }

    public function test_public_profile_does_not_expose_private_resume_files(): void
    {
        $candidate = $this->makeCandidate([
            'name' => 'Public Resume Candidate',
            'username' => 'public-resume-candidate',
        ]);
        $this->actingAs($candidate)->post(route('account.resumes.store'), [
            'file' => $this->fakeResume('hidden.pdf'),
            'title' => 'Hidden CV',
        ]);
        $candidate->candidateProfile?->update(['current_designation' => 'Chargehand']);
        $this->actingAs($candidate)
            ->put(route('account.profile.privacy.update'), [
                'enabled' => true,
                'bio' => true,
                'current_role' => true,
            ])
            ->assertRedirect();

        $this->get(route('profiles.show', ['user' => $candidate->username]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Profiles/Show')
                ->where('profile.name', 'Public Resume Candidate')
                ->missing('profile.resumes')
                ->missing('profile.documents')
                ->missing('profile.resume_url')
                ->missing('profile.download_url')
                ->missing('profile.view_url'));
    }
}
