<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\RecruitmentActivityType;
use App\Models\Application;
use App\Models\Job;
use App\Support\EmployerVisibleCandidate;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Candidate application lifecycle — status parity, withdrawal, and idempotency.
 */
class CandidateApplicationLifecycleConsistencyTest extends TestCase
{
    /**
     * @return array<string, ApplicationStatus>
     */
    public static function trackedStatusesProvider(): array
    {
        return [
            'new' => [ApplicationStatus::New],
            'reviewing' => [ApplicationStatus::Reviewing],
            'shortlisted' => [ApplicationStatus::Shortlisted],
            'interview' => [ApplicationStatus::Interview],
            'selected' => [ApplicationStatus::Selected],
            'placed' => [ApplicationStatus::Placed],
            'rejected' => [ApplicationStatus::Rejected],
            'withdrawn' => [ApplicationStatus::Withdrawn],
        ];
    }

    /**
     * @dataProvider trackedStatusesProvider
     */
    public function test_employer_and_candidate_share_the_same_status_label(ApplicationStatus $status): void
    {
        $employer = $this->makeEmployer();
        $candidate = $this->makeCandidate();
        $job = Job::factory()->create(['employer_id' => $employer->employerProfile->id]);
        $application = Application::factory()->create([
            'job_id' => $job->id,
            'user_id' => $candidate->id,
            'email' => $candidate->email,
            'status' => $status,
        ]);

        $expected = $status->toCandidateStatusLabel();

        $employerPayload = EmployerVisibleCandidate::fromApplication($application);
        $this->assertSame($expected, $employerPayload['status_label']);
        $this->assertSame($expected, $employerPayload['pipeline_stage_label']);

        $this->actingAs($candidate)
            ->get(route('account.applications.show', $application))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('application.status', $expected));
    }

    public function test_repeat_withdraw_does_not_duplicate_status_change_activity(): void
    {
        $candidate = $this->makeCandidate();
        $application = Application::factory()->create([
            'user_id' => $candidate->id,
            'email' => $candidate->email,
            'status' => ApplicationStatus::Reviewing,
        ]);

        $this->actingAs($candidate)
            ->patch(route('account.applications.withdraw', $application))
            ->assertRedirect();

        $afterFirst = $application->fresh();
        $this->assertSame(ApplicationStatus::Withdrawn, $afterFirst?->status);
        $activityCount = $afterFirst?->activities()
            ->where('type', RecruitmentActivityType::StatusChanged)
            ->count();

        $this->actingAs($candidate)
            ->from(route('account.applications.show', $application))
            ->patch(route('account.applications.withdraw', $application))
            ->assertRedirect()
            ->assertSessionHasErrors('application');

        $this->assertSame(
            $activityCount,
            $application->fresh()?->activities()
                ->where('type', RecruitmentActivityType::StatusChanged)
                ->count()
        );
    }

    public function test_closed_job_rejects_new_submission(): void
    {
        $candidate = $this->makeCandidate(['email' => 'closed-job@example.com']);
        $job = Job::factory()->create(['closing_date' => now()->subDay()]);
        $this->assertFalse($job->fresh()->acceptsApplications());
        $this->giveCandidateResume($candidate);

        $this->get(route('jobs.show', $job))->assertNotFound();

        $this->actingAs($candidate)
            ->post(route('jobs.apply.store', $job))
            ->assertNotFound();

        $this->assertSame(0, Application::query()->where('job_id', $job->id)->count());
    }

    public function test_employer_applicant_view_reflects_withdrawn_status(): void
    {
        $employer = $this->makeEmployer();
        $candidate = $this->makeCandidate();
        $job = Job::factory()->create(['employer_id' => $employer->employerProfile->id]);
        $application = Application::factory()->create([
            'job_id' => $job->id,
            'user_id' => $candidate->id,
            'email' => $candidate->email,
            'status' => ApplicationStatus::Shortlisted,
        ]);

        $this->actingAs($candidate)
            ->patch(route('account.applications.withdraw', $application))
            ->assertRedirect();

        $this->actingAs($employer)
            ->get(route('account.employer.jobs.applicants', $job))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('applications.data', 1)
                ->where('applications.data.0.status_label', 'Withdrawn')
                ->where('applications.data.0.status', ApplicationStatus::Withdrawn->value));
    }
}
