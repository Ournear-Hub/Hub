<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\EmployerProfile;
use App\Models\Job;
use App\Models\User;
use App\Notifications\VerifyAccountEmailNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AccountEmailVerificationEnforcementTest extends TestCase
{
    public function test_registration_creates_an_unverified_account_and_sends_verification(): void
    {
        Notification::fake();

        $payload = $this->registrationPayload(['email' => 'fresh-register@example.test']);
        $this->post(route('register.store'), $payload)->assertRedirect(route('login'));

        $user = User::query()->where('email', 'fresh-register@example.test')->first();
        $this->assertNotNull($user);
        $this->assertNull($user->email_verified_at);

        Notification::assertSentTo($user, VerifyAccountEmailNotification::class);
    }

    public function test_unverified_candidate_can_access_dashboard_profile_and_saved_jobs(): void
    {
        $candidate = $this->makeCandidate([
            'username' => 'open-dash',
            'email' => 'open-dash@example.test',
            'email_verified_at' => null,
        ]);

        $this->actingAs($candidate)->get(route('account.home'))->assertOk();
        $this->actingAs($candidate)->get(route('account.profile.edit'))->assertOk();
        $this->actingAs($candidate)->get(route('account.saved-jobs.index'))->assertOk();
        $this->actingAs($candidate)->get(route('account.applications.index'))->assertOk();
        $this->actingAs($candidate)->get(route('account.resumes.index'))->assertOk();
        $this->actingAs($candidate)->get(route('account.resumes.builder'))->assertOk();
    }

    public function test_unverified_candidate_can_persist_personal_and_professional_profile_updates(): void
    {
        $candidate = $this->makeCandidate([
            'email_verified_at' => null,
            'bio' => null,
        ]);

        $this->actingAs($candidate)
            ->put(route('account.profile.update'), [
                'name' => $candidate->name,
                'username' => $candidate->username,
                'preferred_language' => 'en',
                'bio' => 'Updated bio while unverified',
                'location' => 'Test City',
            ])
            ->assertRedirect();

        $this->assertSame('Updated bio while unverified', $candidate->fresh()->bio);

        $this->actingAs($candidate)
            ->put(route('account.profile.professional.update'), [
                'current_designation' => 'Analyst',
                'current_company' => 'Acme',
            ])
            ->assertRedirect();

        $this->assertSame('Analyst', $candidate->fresh()->candidateProfile?->current_designation);
    }

    public function test_unverified_candidate_can_upload_resume(): void
    {
        Storage::fake('local');
        $candidate = $this->makeCandidate(['email_verified_at' => null]);
        $file = UploadedFile::fake()->create('resume.pdf', 100, 'application/pdf');

        $this->actingAs($candidate)
            ->post(route('account.resumes.store'), [
                'file' => $file,
                'title' => 'My Resume',
            ])
            ->assertRedirect();

        $this->assertTrue($candidate->fresh()->candidateProfile?->resumes()->exists() ?? false);
    }

    public function test_unverified_candidate_can_browse_jobs_and_open_apply_page(): void
    {
        $candidate = $this->makeCandidate(['email_verified_at' => null]);
        $job = Job::factory()->create();

        $this->get(route('jobs.index'))->assertOk();
        $this->get(route('jobs.show', $job))->assertOk();

        $this->actingAs($candidate)
            ->get(route('jobs.apply', $job))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Jobs/Apply')
                ->where('requires_email_verification', true));
    }

    public function test_unverified_candidate_cannot_submit_job_application(): void
    {
        $candidate = $this->makeCandidate(['email_verified_at' => null]);
        $job = Job::factory()->create();
        $this->giveCandidateResume($candidate);

        $this->actingAs($candidate)
            ->from(route('jobs.apply', $job))
            ->post(route('jobs.apply.store', $job))
            ->assertRedirect(route('jobs.apply', $job))
            ->assertSessionHasErrors('email');

        $this->assertSame(0, Application::query()->count());
    }

    public function test_unverified_candidate_cannot_bypass_apply_restriction_with_forged_verification_flag(): void
    {
        $candidate = $this->makeCandidate(['email_verified_at' => null]);
        $job = Job::factory()->create();
        $this->giveCandidateResume($candidate);

        $this->actingAs($candidate)
            ->post(route('jobs.apply.store', $job), ['email_verified' => true])
            ->assertRedirect(route('jobs.apply', $job))
            ->assertSessionHasErrors('email');

        $this->assertSame(0, Application::query()->count());
    }

    public function test_verified_candidate_can_apply_after_verification(): void
    {
        $candidate = $this->makeCandidate(['email_verified_at' => null]);
        $job = Job::factory()->create();

        $candidate->forceFill(['email_verified_at' => now()])->save();
        $this->giveCandidateResume($candidate);

        $this->actingAs($candidate)
            ->post(route('jobs.apply.store', $job))
            ->assertRedirect(route('jobs.show', $job));

        $this->assertSame(1, Application::query()->count());
    }

    public function test_public_jobs_remain_visible_to_guests(): void
    {
        $job = Job::factory()->create();

        $this->get(route('jobs.index'))->assertOk();
        $this->get(route('jobs.show', $job))->assertOk();
    }

    public function test_unverified_candidate_can_use_password_reset_and_verification_settings(): void
    {
        $candidate = $this->makeCandidate([
            'email' => 'reset-flow@example.test',
            'email_verified_at' => null,
        ]);

        $this->actingAs($candidate)
            ->get(route('account.settings.verification'))
            ->assertOk();

        $this->actingAs($candidate)
            ->get(route('account.settings.password'))
            ->assertOk();
    }

    public function test_unverified_employer_can_still_reach_hiring_workspace(): void
    {
        $employer = $this->makeEmployer(['email_verified_at' => null]);

        $this->actingAs($employer)
            ->get(route('account.home'))
            ->assertOk();

        $this->actingAs($employer)
            ->get(route('account.hiring'))
            ->assertOk();
    }

    public function test_unverified_dual_role_candidate_can_use_hiring_and_candidate_profile(): void
    {
        $user = $this->makeCandidate(['email_verified_at' => null]);
        EmployerProfile::query()->create([
            'user_id' => $user->id,
            'company_name' => 'Dual Role Co',
            'industry_id' => $this->industryId(),
        ]);

        $this->actingAs($user)
            ->get(route('account.hiring'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('account.profile.edit'))
            ->assertOk();
    }

    public function test_unverified_candidate_login_with_username_and_email(): void
    {
        $user = $this->makeCandidate([
            'username' => 'unv-user',
            'email' => 'unv-user@example.test',
            'email_verified_at' => null,
        ]);

        $this->post(route('login.store'), [
            'login' => 'unv-user',
            'password' => 'password',
        ])->assertRedirect(route('account.home'));

        $this->post(route('logout'));

        $this->post(route('login.store'), [
            'login' => 'unv-user@example.test',
            'password' => 'password',
        ])->assertRedirect(route('account.home'));
    }
}
