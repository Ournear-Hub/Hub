<?php

namespace Tests\Feature\Api\V1;

use App\Models\Application;
use App\Models\CandidateResume;
use App\Models\Job;
use App\Models\SavedJob;
use App\Models\User;
use App\Notifications\VerifyAccountEmailNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileCandidateApiTest extends TestCase
{
    private function bearer(User $user): self
    {
        Sanctum::actingAs($user, ['account:mobile']);

        return $this;
    }

    public function test_account_payload_includes_candidate_fields(): void
    {
        $user = $this->makeCandidate([
            'username' => 'api-candidate',
            'email' => 'candidate@example.test',
            'email_verified_at' => now(),
        ]);

        $this->bearer($user)
            ->getJson(route('api.v1.account.show'))
            ->assertOk()
            ->assertJsonPath('data.username', 'api-candidate')
            ->assertJsonPath('data.email', 'candidate@example.test')
            ->assertJsonStructure([
                'data' => [
                    'profile_completion',
                    'email_verified',
                    'completion_items',
                ],
            ]);
    }

    public function test_personal_profile_read_and_update(): void
    {
        $user = $this->makeCandidate(['email_verified_at' => now()]);

        $this->bearer($user)
            ->getJson(route('api.v1.account.profile.personal.show'))
            ->assertOk()
            ->assertJsonPath('data.name', $user->name);

        $this->bearer($user)
            ->patchJson(route('api.v1.account.profile.personal.update'), [
                'name' => 'Mobile Updated Name',
                'username' => $user->username,
                'preferred_language' => 'en',
                'bio' => 'Short bio from mobile.',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Mobile Updated Name')
            ->assertJsonPath('data.bio', 'Short bio from mobile.');

        $this->assertSame('Mobile Updated Name', $user->fresh()->name);
    }

    public function test_professional_profile_read_and_update_preserves_omitted_legacy_fields(): void
    {
        $user = $this->makeCandidate(['email_verified_at' => now()]);
        $profile = $user->candidateProfile;
        $this->assertNotNull($profile);

        $profile->update([
            'job_category_id' => $profile->job_category_id,
            'experience' => 'Legacy notes remain',
        ]);

        $this->bearer($user)
            ->getJson(route('api.v1.account.profile.professional.show'))
            ->assertOk()
            ->assertJsonStructure(['data' => ['skills', 'employment_histories']]);

        $this->bearer($user)
            ->patchJson(route('api.v1.account.profile.professional.update'), [
                'current_designation' => 'Site Supervisor',
            ])
            ->assertOk()
            ->assertJsonPath('data.current_designation', 'Site Supervisor');

        $profile->refresh();
        $this->assertSame('Legacy notes remain', $profile->experience);
    }

    public function test_education_and_certification_endpoints(): void
    {
        $user = $this->makeCandidate(['email_verified_at' => now()]);

        $this->bearer($user)
            ->postJson(route('api.v1.account.profile.professional.educations.store'), [
                'institution' => 'Trade Institute',
                'qualification' => 'Diploma',
            ])
            ->assertCreated()
            ->assertJsonPath('data.educations.0.institution', 'Trade Institute');

        $educationId = $user->fresh()->candidateProfile?->educations()->value('id');
        $this->assertNotNull($educationId);

        $this->bearer($user)
            ->patchJson(route('api.v1.account.profile.professional.educations.update', $educationId), [
                'institution' => 'Trade Institute',
                'qualification' => 'Advanced Diploma',
            ])
            ->assertOk()
            ->assertJsonPath('data.educations.0.qualification', 'Advanced Diploma');

        $this->bearer($user)
            ->postJson(route('api.v1.account.profile.professional.certifications.store'), [
                'name' => 'Site Safety',
                'issuer' => 'Safety Board',
            ])
            ->assertCreated()
            ->assertJsonPath('data.certifications.0.name', 'Site Safety');

        $certificationId = $user->fresh()->candidateProfile?->certifications()->value('id');
        $this->assertNotNull($certificationId);

        $other = $this->makeCandidate(['email_verified_at' => now()]);
        $this->bearer($other)
            ->deleteJson(route('api.v1.account.profile.professional.educations.destroy', $educationId))
            ->assertForbidden();

        $this->bearer($user)
            ->deleteJson(route('api.v1.account.profile.professional.educations.destroy', $educationId))
            ->assertOk();

        $this->bearer($user)
            ->deleteJson(route('api.v1.account.profile.professional.certifications.destroy', $certificationId))
            ->assertOk();
    }

    public function test_profile_photo_upload_returns_photo_url(): void
    {
        $user = $this->makeCandidate(['email_verified_at' => now()]);
        $photo = \Illuminate\Http\UploadedFile::fake()->image('avatar.jpg', 200, 200);

        $this->bearer($user)
            ->patch(route('api.v1.account.profile.personal.update'), [
                'name' => $user->name,
                'username' => $user->username,
                'preferred_language' => 'en',
                'photo' => $photo,
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonStructure(['data' => ['photo_url']]);

        $photoUrl = $this->bearer($user)
            ->getJson(route('api.v1.account.profile.personal.show'))
            ->json('data.photo_url');
        $this->assertIsString($photoUrl);
        $this->assertNotSame('', trim((string) $photoUrl));
    }

    public function test_skill_and_employment_history_endpoints(): void
    {
        $user = $this->makeCandidate(['email_verified_at' => now()]);

        $this->bearer($user)
            ->postJson(route('api.v1.account.profile.professional.skills.store'), [
                'name' => 'Scaffolding',
            ])
            ->assertCreated()
            ->assertJsonPath('data.skills.0.name', 'Scaffolding');

        $skillId = $user->fresh()->candidateProfile?->skills()->where('skills.name', 'Scaffolding')->value('skills.id');
        $this->assertNotNull($skillId);

        $this->bearer($user)
            ->deleteJson(route('api.v1.account.profile.professional.skills.destroy', $skillId))
            ->assertOk();

        $this->bearer($user)
            ->postJson(route('api.v1.account.profile.professional.employment-histories.store'), [
                'company' => 'Build Co',
                'designation' => 'Foreman',
                'started_on' => '2020-01-01',
                'is_current' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('data.employment_histories.0.company', 'Build Co');
    }

    public function test_resume_library_upload_and_download_are_scoped_to_owner(): void
    {
        $owner = $this->makeCandidate(['email_verified_at' => now()]);
        $other = $this->makeCandidate(['email_verified_at' => now()]);

        $this->bearer($owner)
            ->post(route('api.v1.account.resumes.store'), [
                'file' => $this->fakeResume('mobile-cv.pdf'),
                'title' => 'Mobile CV',
            ], ['Accept' => 'application/json'])
            ->assertCreated();

        $resume = CandidateResume::query()
            ->where('candidate_profile_id', $owner->candidateProfile?->id)
            ->latest('id')
            ->first();
        $this->assertNotNull($resume);

        $this->bearer($owner)
            ->getJson(route('api.v1.account.resumes.index'))
            ->assertOk()
            ->assertJsonPath('data.0.title', 'Mobile CV');

        $this->bearer($other)
            ->getJson(route('api.v1.account.resumes.download', $resume))
            ->assertForbidden();
    }

    public function test_saved_jobs_list_save_unsave_and_duplicate_handling(): void
    {
        $user = $this->makeCandidate(['email_verified_at' => now()]);
        $job = Job::factory()->create(['slug' => 'save-me-job']);

        $this->bearer($user)
            ->postJson(route('api.v1.account.saved-jobs.store', $job))
            ->assertCreated()
            ->assertJsonPath('data.is_saved', true);

        $this->assertSame(1, SavedJob::query()->where('user_id', $user->id)->where('job_id', $job->id)->count());

        $this->bearer($user)
            ->postJson(route('api.v1.account.saved-jobs.store', $job))
            ->assertCreated();

        $this->assertSame(1, SavedJob::query()->where('user_id', $user->id)->where('job_id', $job->id)->count());

        $this->bearer($user)
            ->getJson(route('api.v1.account.saved-jobs.index'))
            ->assertOk()
            ->assertJsonPath('data.0.slug', 'save-me-job');

        $this->bearer($user)
            ->deleteJson(route('api.v1.account.saved-jobs.destroy', $job))
            ->assertOk()
            ->assertJsonPath('data.is_saved', false);
    }

    public function test_applications_list_detail_and_ownership(): void
    {
        $candidate = $this->makeCandidate(['email_verified_at' => now()]);
        $other = $this->makeCandidate(['email_verified_at' => now()]);
        $job = Job::factory()->create();
        $application = $this->applyAsCandidate($candidate, $job);

        $this->bearer($candidate)
            ->getJson(route('api.v1.account.applications.index'))
            ->assertOk()
            ->assertJsonPath('data.0.id', $application->id);

        $this->bearer($candidate)
            ->getJson(route('api.v1.account.applications.show', $application))
            ->assertOk()
            ->assertJsonPath('data.id', $application->id);

        $this->bearer($other)
            ->getJson(route('api.v1.account.applications.show', $application))
            ->assertForbidden();
    }

    public function test_email_verification_status_and_resend(): void
    {
        Notification::fake();

        $user = $this->makeCandidate(['email_verified_at' => null]);

        $this->bearer($user)
            ->getJson(route('api.v1.account.verification.email.show'))
            ->assertOk()
            ->assertJsonPath('data.email_verified', false);

        $this->bearer($user)
            ->postJson(route('api.v1.account.verification.email.resend'))
            ->assertOk();

        Notification::assertSentTo($user, VerifyAccountEmailNotification::class);
    }

    public function test_verified_candidate_can_apply_via_api(): void
    {
        $candidate = $this->makeCandidate(['email_verified_at' => now()]);
        $job = Job::factory()->create();
        $this->giveCandidateResume($candidate);

        $this->bearer($candidate)
            ->postJson(route('api.v1.jobs.apply', $job))
            ->assertCreated()
            ->assertJsonStructure(['data' => ['application_id', 'business_id']]);

        $this->assertSame(1, Application::query()->where('user_id', $candidate->id)->where('job_id', $job->id)->count());
    }

    public function test_unverified_candidate_cannot_apply_via_api_or_bypass_with_flag(): void
    {
        $candidate = $this->makeCandidate(['email_verified_at' => null]);
        $job = Job::factory()->create();
        $this->giveCandidateResume($candidate);

        $this->bearer($candidate)
            ->postJson(route('api.v1.jobs.apply', $job))
            ->assertForbidden()
            ->assertJsonPath('error_code', 'email_verification_required');

        $this->assertSame(0, Application::query()->count());

        $this->bearer($candidate)
            ->postJson(route('api.v1.jobs.apply', $job), ['email_verified' => true])
            ->assertForbidden()
            ->assertJsonPath('error_code', 'email_verification_required');

        $this->assertSame(0, Application::query()->count());
    }

    public function test_public_jobs_include_is_saved_for_authenticated_candidate(): void
    {
        $user = $this->makeCandidate(['email_verified_at' => now()]);
        $job = Job::factory()->create(['slug' => 'saved-state-job']);
        SavedJob::query()->create(['user_id' => $user->id, 'job_id' => $job->id]);

        $token = $user->createToken('mobile', ['account:mobile'])->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson(route('api.v1.jobs.show', $job))
            ->assertOk()
            ->assertJsonPath('data.is_saved', true);

        $this->withoutHeader('Authorization')
            ->getJson(route('api.v1.jobs.show', $job))
            ->assertOk()
            ->assertJsonMissingPath('data.is_saved');
    }

    public function test_employer_token_cannot_access_candidate_endpoints(): void
    {
        $employer = $this->makeEmployer(['email_verified_at' => now()]);
        Sanctum::actingAs($employer, ['account:mobile']);

        $this->getJson(route('api.v1.account.profile.personal.show'))->assertForbidden();
    }

    public function test_candidate_web_profile_page_still_renders(): void
    {
        $candidate = $this->makeCandidate(['email_verified_at' => now()]);

        $this->actingAs($candidate)
            ->get(route('account.profile.edit'))
            ->assertOk();
    }
}
