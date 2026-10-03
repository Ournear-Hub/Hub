<?php

namespace Tests\Feature;

use App\Enums\EmployerNotificationEvent;
use App\Models\Application;
use App\Models\Job;
use App\Models\User;
use App\Services\RecruitmentNotificationService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EmployerHiringStage22Test extends TestCase
{
    public function test_application_submission_notifies_job_owner_employer(): void
    {
        $employer = $this->makeEmployer([
            'email_verified_at' => now(),
            'mobile_verified_at' => now(),
        ]);
        $employer->employerProfile->update(['company_name' => 'Notify Co']);
        $candidate = $this->makeCandidate([
            'email_verified_at' => now(),
            'mobile_verified_at' => now(),
            'name' => 'Applicant One',
        ]);
        $this->giveCandidateResume($candidate);

        $job = Job::factory()->create([
            'employer_id' => $employer->employerProfile->id,
            'title' => 'QA Engineer',
        ]);

        $this->applyAsCandidate($candidate, $job);

        $employer->refresh();
        $this->assertSame(1, $employer->unreadNotifications()->count());
        $notification = $employer->notifications()->first();
        $this->assertSame(EmployerNotificationEvent::ApplicationReceived->value, $notification->data['event']);
        $this->assertStringContainsString('QA Engineer', (string) $notification->data['body']);
        $this->assertStringContainsString('Applicant One', (string) $notification->data['body']);

        $application = Application::query()->where('job_id', $job->id)->firstOrFail();
        $expectedUrl = route('account.employer.jobs.application', [$job, $application], false);
        $this->assertSame($expectedUrl, $notification->data['url']);
    }

    public function test_unrelated_employer_does_not_receive_application_notification(): void
    {
        $owner = $this->makeEmployer(['email_verified_at' => now(), 'mobile_verified_at' => now()]);
        $other = $this->makeEmployer(['email_verified_at' => now(), 'mobile_verified_at' => now()]);
        $candidate = $this->makeCandidate(['email_verified_at' => now(), 'mobile_verified_at' => now()]);
        $this->giveCandidateResume($candidate);

        $job = Job::factory()->create(['employer_id' => $owner->employerProfile->id]);
        $this->applyAsCandidate($candidate, $job);

        $this->assertSame(1, $owner->fresh()->unreadNotifications()->count());
        $this->assertSame(0, $other->fresh()->unreadNotifications()->count());
    }

    public function test_duplicate_application_submission_does_not_create_duplicate_employer_notification(): void
    {
        $employer = $this->makeEmployer(['email_verified_at' => now(), 'mobile_verified_at' => now()]);
        $candidate = $this->makeCandidate(['email_verified_at' => now(), 'mobile_verified_at' => now()]);
        $this->giveCandidateResume($candidate);
        $job = Job::factory()->create(['employer_id' => $employer->employerProfile->id]);

        $this->applyAsCandidate($candidate, $job);
        $this->actingAs($candidate)
            ->post(route('jobs.apply.store', $job))
            ->assertSessionHasErrors();

        $this->assertSame(1, $employer->fresh()->unreadNotifications()->count());
    }

    public function test_employer_notification_service_deduplicates_by_fingerprint(): void
    {
        $employer = $this->makeEmployer();
        $job = Job::factory()->create(['employer_id' => $employer->employerProfile->id]);
        $application = Application::factory()->create([
            'job_id' => $job->id,
            'full_name' => 'Retry Applicant',
        ]);

        $service = app(RecruitmentNotificationService::class);
        DB::transaction(function () use ($service, $application) {
            $service->notifyApplicationSubmitted($application);
            $service->notifyApplicationSubmitted($application);
        });

        $this->assertSame(1, $employer->fresh()->notifications()->count());
    }

    public function test_candidate_still_receives_application_received_notification(): void
    {
        $employer = $this->makeEmployer(['email_verified_at' => now(), 'mobile_verified_at' => now()]);
        $candidate = $this->makeCandidate(['email_verified_at' => now(), 'mobile_verified_at' => now()]);
        $this->giveCandidateResume($candidate);
        $job = Job::factory()->create(['employer_id' => $employer->employerProfile->id]);

        $this->applyAsCandidate($candidate, $job);

        $this->assertSame(1, $candidate->fresh()->unreadNotifications()->count());
        $this->assertSame(1, $employer->fresh()->unreadNotifications()->count());
    }

    public function test_employer_cannot_read_other_employer_notification(): void
    {
        $owner = $this->makeEmployer();
        $other = $this->makeEmployer();
        $notification = $owner->notifications()->create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'type' => \App\Notifications\EmployerLifecycleNotification::class,
            'data' => [
                'event' => EmployerNotificationEvent::ApplicationReceived->value,
                'title' => 'Private',
                'body' => 'Private application',
                'url' => '/account/hiring',
                'fingerprint' => 'private-test',
            ],
        ]);

        $this->actingAs($other)
            ->post(route('account.notifications.read', $notification))
            ->assertForbidden();
    }
}
