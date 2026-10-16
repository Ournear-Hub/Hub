<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Job;
use App\Enums\CandidateNotificationEvent;
use App\Notifications\CandidateLifecycleNotification;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EmployerHiringStage21Test extends TestCase
{
    public function test_employer_can_open_account_notifications(): void
    {
        $employer = $this->makeEmployer();

        $this->actingAs($employer)
            ->get(route('account.notifications.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Account/Notifications'));
    }

    public function test_employer_application_contact_is_exposed_from_snapshot_only(): void
    {
        $employer = $this->makeEmployer([
            'email_verified_at' => now(),
            'mobile_verified_at' => now(),
        ]);
        $job = Job::factory()->create(['employer_id' => $employer->employerProfile->id]);
        $application = Application::factory()->create([
            'job_id' => $job->id,
            'email' => 'applicant@example.com',
            'mobile' => '9876543210',
        ]);

        $this->actingAs($employer)
            ->get(route('account.employer.jobs.application', [$job, $application]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('application.application_contact.email', 'applicant@example.com')
                ->where('application.application_contact.mobile', '9876543210')
                ->where('application.application_contact.available', true));
    }

    public function test_candidate_with_hiring_sees_employer_nav_on_hiring_routes_only(): void
    {
        $candidate = $this->makeCandidate([
            'email_verified_at' => now(),
            'mobile_verified_at' => now(),
        ]);

        $this->actingAs($candidate)
            ->post(route('account.hiring.activate'), $this->hiringActivationPayload([
                'company_name' => 'Dual Role Co',
                'company_email' => $candidate->email,
            ]))
            ->assertRedirect();

        $this->actingAs($candidate->fresh())
            ->get(route('account.home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('employerNav', null));

        $this->actingAs($candidate->fresh())
            ->get(route('account.hiring'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('employerNav'));
    }

    public function test_unrelated_employer_cannot_view_application(): void
    {
        $owner = $this->makeEmployer([
            'email_verified_at' => now(),
            'mobile_verified_at' => now(),
        ]);
        $other = $this->makeEmployer([
            'email_verified_at' => now(),
            'mobile_verified_at' => now(),
        ]);
        $job = Job::factory()->create(['employer_id' => $owner->employerProfile->id]);
        $application = Application::factory()->create(['job_id' => $job->id]);

        $this->actingAs($other)
            ->get(route('account.employer.jobs.application', [$job, $application]))
            ->assertForbidden();
    }

    public function test_account_alerts_include_unread_for_hiring_employer(): void
    {
        $employer = $this->makeEmployer();
        $employer->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => CandidateLifecycleNotification::class,
            'data' => [
                'event' => CandidateNotificationEvent::ApplicationReceived->value,
                'title' => 'Hiring update',
                'body' => 'Example employer notification.',
                'url' => '/account/hiring',
                'fingerprint' => 'stage21-test',
            ],
        ]);

        $this->actingAs($employer)
            ->get(route('account.hiring'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('accountAlerts.unread', 1));
    }
}
