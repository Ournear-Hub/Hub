<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\CommunicationChannel;
use App\Mail\ApplicationRejectedMail;
use App\Models\Application;
use App\Models\Job;
use App\Notifications\CandidateLifecycleNotification;
use App\Notifications\VerifyAccountEmailNotification;
use App\Services\ApplicationPipelineService;
use App\Services\CommunicationPreferenceService;
use App\Services\RecruitmentNotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * REG-COM-002 — candidate communication preferences vs notification delivery.
 */
class CandidateCommunicationPreferenceDeliveryTest extends TestCase
{
    public function test_system_opt_out_suppresses_email_but_not_in_app_when_push_enabled(): void
    {
        Mail::fake();
        $candidate = $this->makeCandidate(['email' => 'com002-optout@example.com']);
        $this->giveCandidateResume($candidate);
        app(CommunicationPreferenceService::class)->sync($candidate, [
            CommunicationChannel::System->value => false,
            CommunicationChannel::Push->value => true,
        ], 'test');

        $job = Job::factory()->create(['title' => 'Opt Out Role']);
        $this->actingAs($candidate)
            ->post(route('jobs.apply.store', $job))
            ->assertRedirect();

        $this->assertSame(1, $candidate->fresh()->notifications()->count());
        Mail::assertNothingSent();

        $application = Application::query()->where('user_id', $candidate->id)->firstOrFail();
        app(ApplicationPipelineService::class)->changeStatus(
            $application,
            ApplicationStatus::Rejected,
            $this->makeAdmin(),
        );

        $this->assertSame(2, $candidate->fresh()->notifications()->count());
        Mail::assertNothingSent();
    }

    public function test_push_opt_out_suppresses_in_app_but_not_operational_email(): void
    {
        Mail::fake();
        $candidate = $this->makeCandidate(['email' => 'com002-push-off@example.com']);
        app(CommunicationPreferenceService::class)->sync($candidate, [
            CommunicationChannel::Push->value => false,
            CommunicationChannel::System->value => true,
        ], 'test');

        $application = Application::factory()->create([
            'user_id' => $candidate->id,
            'email' => $candidate->email,
            'status' => ApplicationStatus::New,
        ]);

        app(RecruitmentNotificationService::class)->notifyStatusChanged(
            $application,
            ApplicationStatus::New,
            ApplicationStatus::Rejected,
        );

        $this->assertSame(0, $candidate->fresh()->notifications()->count());
        Mail::assertQueued(ApplicationRejectedMail::class, 1);
    }

    public function test_system_opt_in_delivers_in_app_and_operational_email_with_templates(): void
    {
        Mail::fake();
        $candidate = $this->makeCandidate(['email' => 'com002-optin@example.com']);
        $this->giveCandidateResume($candidate);
        app(CommunicationPreferenceService::class)->sync($candidate, [
            CommunicationChannel::System->value => true,
        ], 'test');

        $this->applyAsCandidate($candidate, Job::factory()->create(['title' => 'Opt In Role']));

        $this->assertSame(1, $candidate->fresh()->notifications()->where('type', CandidateLifecycleNotification::class)->count());

        $application = Application::query()->where('user_id', $candidate->id)->firstOrFail();
        app(ApplicationPipelineService::class)->changeStatus(
            $application,
            ApplicationStatus::Rejected,
            $this->makeAdmin(),
        );

        $this->assertSame(2, $candidate->fresh()->notifications()->where('type', CandidateLifecycleNotification::class)->count());
        Mail::assertQueued(ApplicationRejectedMail::class, 1);
    }

    public function test_guest_linked_application_without_user_still_receives_operational_email(): void
    {
        Mail::fake();
        $application = Application::factory()->create([
            'user_id' => null,
            'email' => 'guest-applicant@example.com',
            'status' => ApplicationStatus::New,
        ]);

        app(RecruitmentNotificationService::class)->notifyStatusChanged(
            $application,
            ApplicationStatus::New,
            ApplicationStatus::Rejected,
        );

        Mail::assertQueued(ApplicationRejectedMail::class, 1);
    }

    public function test_account_verification_is_not_blocked_by_system_opt_out(): void
    {
        Notification::fake();
        $candidate = $this->makeCandidate(['email' => 'com002-verify@example.com', 'email_verified_at' => null]);
        app(CommunicationPreferenceService::class)->sync($candidate, [
            CommunicationChannel::System->value => false,
        ], 'test');

        $this->actingAs($candidate)
            ->post(route('account.verification.send'))
            ->assertRedirect();

        Notification::assertSentTo($candidate, VerifyAccountEmailNotification::class);
    }

    public function test_preference_change_is_reflected_on_subsequent_notifications(): void
    {
        Mail::fake();
        $candidate = $this->makeCandidate(['email' => 'com002-toggle@example.com']);
        $preferences = app(CommunicationPreferenceService::class);
        $preferences->sync($candidate, [CommunicationChannel::System->value => true], 'test');

        $application = Application::factory()->create([
            'user_id' => $candidate->id,
            'email' => $candidate->email,
            'status' => ApplicationStatus::Shortlisted,
        ]);

        DB::transaction(function () use ($application) {
            app(RecruitmentNotificationService::class)->notifyStatusChanged(
                $application,
                ApplicationStatus::New,
                ApplicationStatus::Shortlisted,
            );
        });

        $this->assertSame(1, $candidate->fresh()->notifications()->count());

        $preferences->sync($candidate->fresh(), [CommunicationChannel::System->value => false], 'test');

        DB::transaction(function () use ($application) {
            app(RecruitmentNotificationService::class)->notifyStatusChanged(
                $application->fresh(),
                ApplicationStatus::Shortlisted,
                ApplicationStatus::Rejected,
            );
        });

        $this->assertSame(2, $candidate->fresh()->notifications()->count());
        Mail::assertNothingSent();
    }

    public function test_repeat_status_notification_remains_idempotent_when_opted_in(): void
    {
        $candidate = $this->makeCandidate(['email' => 'com002-idempotent@example.com']);
        app(CommunicationPreferenceService::class)->sync($candidate, [
            CommunicationChannel::System->value => true,
        ], 'test');

        $application = Application::factory()->create([
            'user_id' => $candidate->id,
            'email' => $candidate->email,
            'status' => ApplicationStatus::New,
        ]);

        $pipeline = app(ApplicationPipelineService::class);
        $pipeline->changeStatus($application, ApplicationStatus::Reviewing, $this->makeAdmin());
        $pipeline->changeStatus($application->fresh(), ApplicationStatus::Reviewing, $this->makeAdmin());

        $this->assertSame(1, $candidate->fresh()->notifications()->count());
    }
}
