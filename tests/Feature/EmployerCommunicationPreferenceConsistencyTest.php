<?php

namespace Tests\Feature;

use App\Enums\CommunicationChannel;
use App\Mail\EmployerRecruitmentMail;
use App\Models\Application;
use App\Models\Job;
use App\Notifications\EmployerLifecycleNotification;
use App\Services\CommunicationPreferenceService;
use App\Services\EmployerNotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * REG-COM-003 — employer in-app (Push) vs operational email (System) preferences.
 */
class EmployerCommunicationPreferenceConsistencyTest extends TestCase
{
    public function test_system_opt_out_suppresses_email_but_keeps_in_app_notification(): void
    {
        Mail::fake();
        $employer = $this->makeEmployer([
            'email' => 'com003-employer@example.com',
            'email_verified_at' => now(),
            'mobile_verified_at' => now(),
        ]);
        app(CommunicationPreferenceService::class)->sync($employer, [
            CommunicationChannel::System->value => false,
            CommunicationChannel::Push->value => true,
        ], 'test');

        $job = Job::factory()->create(['employer_id' => $employer->employerProfile?->id]);
        $application = Application::factory()->create(['job_id' => $job->id, 'full_name' => 'Split Channel Applicant']);

        DB::transaction(function () use ($application) {
            app(EmployerNotificationService::class)->notifyApplicationReceived($application);
        });

        Mail::assertNothingQueued();
        $this->assertSame(1, $employer->fresh()->unreadNotifications()->count());
    }

    public function test_push_opt_out_suppresses_in_app_but_keeps_operational_email(): void
    {
        Mail::fake();
        $employer = $this->makeEmployer([
            'email' => 'com003-push-off@example.com',
            'email_verified_at' => now(),
            'mobile_verified_at' => now(),
        ]);
        app(CommunicationPreferenceService::class)->sync($employer, [
            CommunicationChannel::Push->value => false,
            CommunicationChannel::System->value => true,
        ], 'test');

        $job = Job::factory()->create(['employer_id' => $employer->employerProfile?->id]);
        $application = Application::factory()->create(['job_id' => $job->id, 'full_name' => 'Email Only Applicant']);

        DB::transaction(function () use ($application) {
            app(EmployerNotificationService::class)->notifyApplicationReceived($application);
        });

        Mail::assertQueued(EmployerRecruitmentMail::class, 1);
        $this->assertSame(0, $employer->fresh()->unreadNotifications()->count());
    }

    public function test_repeat_employer_notification_remains_idempotent_when_channels_enabled(): void
    {
        Mail::fake();
        $employer = $this->makeEmployer([
            'email_verified_at' => now(),
            'mobile_verified_at' => now(),
        ]);
        $job = Job::factory()->create(['employer_id' => $employer->employerProfile?->id]);
        $application = Application::factory()->create(['job_id' => $job->id]);

        $service = app(EmployerNotificationService::class);
        DB::transaction(fn () => $service->notifyApplicationReceived($application));
        DB::transaction(fn () => $service->notifyApplicationReceived($application));

        $this->assertSame(1, $employer->fresh()->notifications()->where('type', EmployerLifecycleNotification::class)->count());
        Mail::assertQueued(EmployerRecruitmentMail::class, 1);
    }
}
