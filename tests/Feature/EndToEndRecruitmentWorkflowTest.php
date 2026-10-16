<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\CandidateNotificationEvent;
use App\Enums\EmployerNotificationEvent;
use App\Enums\RecruiterTalentPoolAssignmentStatus;
use App\Enums\StaffRoleSlug;
use App\Models\Application;
use App\Models\Job;
use App\Models\RecruiterTalentPoolAssignment;
use App\Models\RejectionReason;
use App\Models\User;
use App\Enums\JobPublishingStatus;
use App\Support\QaHiringFixtures;
use Illuminate\Support\Facades\Artisan;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Connected recruitment lifecycle: admin assignment, job publish, multi-candidate/job matrix,
 * ATS paths, and notification targeting. Complements granular stage tests; uses qa:hiring-fixtures.
 */
class EndToEndRecruitmentWorkflowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('qa:hiring-fixtures');
    }

    private function employerA(): User
    {
        return User::query()->where('email', QaHiringFixtures::EMPLOYER_A_EMAIL)->firstOrFail();
    }

    private function employerB(): User
    {
        return User::query()->where('email', QaHiringFixtures::EMPLOYER_B_EMAIL)->firstOrFail();
    }

    private function job1(): Job
    {
        return Job::query()->where('slug', QaHiringFixtures::SLUG_PUBLISHED_A)->firstOrFail();
    }

    private function job2(): Job
    {
        return Job::query()->where('slug', 'qa-fixture-published-a-01')->firstOrFail();
    }

    private function job3(): Job
    {
        return Job::query()->where('slug', 'qa-fixture-published-a-02')->firstOrFail();
    }

    private function matrixCandidate(string $letter): User
    {
        $email = 'qa-matrix-candidate-'.strtolower($letter).'@example.test';

        return User::query()->where('email', $email)->first()
            ?? $this->makeCandidate(['email' => $email, 'name' => 'QA Matrix Candidate '.$letter]);
    }

    public function test_workflow_a_admin_assignment_appears_for_recruiter_not_for_other_company(): void
    {
        $admin = $this->makeStaff(StaffRoleSlug::SuperAdmin->value);
        $recruiter = $this->makeStaff(StaffRoleSlug::Recruiter->value, ['email' => 'qa-e2e-recruiter-a@example.test']);
        $otherRecruiter = $this->makeStaff(StaffRoleSlug::Recruiter->value, ['email' => 'qa-e2e-recruiter-b@example.test']);
        $candidate = $this->matrixCandidate('x');
        $job = $this->job1();

        $application = Application::factory()->create([
            'user_id' => $candidate->id,
            'job_id' => $job->id,
            'status' => ApplicationStatus::Shortlisted,
        ]);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.talent-pool-assignments.store'), [
                'candidate_user_id' => $candidate->id,
                'assigned_recruiter_user_id' => $recruiter->id,
                'assignment_type' => 'job',
                'application_id' => $application->id,
                'job_id' => $job->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $assignment = RecruiterTalentPoolAssignment::query()->latest('id')->first();
        $this->assertNotNull($assignment);
        $this->assertSame($recruiter->id, $assignment->assigned_recruiter_user_id);

        $this->actingAs($recruiter, 'admin')
            ->get(route('admin.talent-pool-assignments.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('assignments.data', 1)
                ->where('assignments.data.0.candidate_name', $candidate->name));

        $this->actingAs($otherRecruiter, 'admin')
            ->get(route('admin.talent-pool-assignments.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('assignments.data', 0));
    }

    public function test_workflow_b_employer_can_publish_draft_and_job_is_public(): void
    {
        $employer = $this->employerA();
        $draft = Job::query()->where('slug', QaHiringFixtures::SLUG_DRAFT_A)->firstOrFail();
        $this->assertFalse($draft->isPubliclyVisible());

        $this->actingAs($employer)
            ->patch(route('account.employer.jobs.lifecycle', $draft), ['action' => 'publish'])
            ->assertRedirect();

        $draft->refresh();
        $this->assertTrue($draft->isPubliclyVisible());

        $this->get(route('jobs.show', $draft))->assertOk();
        $this->assertSame(JobPublishingStatus::Published, $draft->fresh()->publishing_status);
    }

    public function test_workflow_c_six_scenario_application_matrix_is_independent(): void
    {
        $employer = $this->employerA();
        $job1 = $this->job1();
        $job2 = $this->job2();
        $job3 = $this->job3();

        $candidates = [
            'a' => $this->matrixCandidate('a'),
            'b' => $this->matrixCandidate('b'),
            'c' => $this->matrixCandidate('c'),
            'd' => $this->matrixCandidate('d'),
            'e' => $this->matrixCandidate('e'),
            'f' => $this->matrixCandidate('f'),
        ];

        $appA1 = $this->applyAsCandidate($candidates['a'], $job1);
        $appB1 = $this->applyAsCandidate($candidates['b'], $job1);
        $appC1 = $this->applyAsCandidate($candidates['c'], $job1);
        $appD2 = $this->applyAsCandidate($candidates['d'], $job2);
        $appE3 = $this->applyAsCandidate($candidates['e'], $job3);
        $appF3 = $this->applyAsCandidate($candidates['f'], $job3);

        $this->assertNotSame($appA1->id, $appB1->id);
        $this->assertSame($job1->id, $appA1->job_id);
        $this->assertSame($job2->id, $appD2->job_id);
        $this->assertSame(3, Application::query()->where('job_id', $job1->id)->whereIn('user_id', [
            $candidates['a']->id,
            $candidates['b']->id,
            $candidates['c']->id,
        ])->count());

        $this->actingAs($employer)
            ->post(route('account.employer.jobs.application.shortlist', [$job1, $appA1]))
            ->assertRedirect();

        $this->assertSame(ApplicationStatus::Shortlisted, $appA1->fresh()->status);
        $this->assertSame(ApplicationStatus::New, $appB1->fresh()->status);
        $this->assertSame(ApplicationStatus::New, $appC1->fresh()->status);
        $this->assertSame(ApplicationStatus::New, $appD2->fresh()->status);

        $this->actingAs($candidates['a'])
            ->post(route('jobs.apply.store', $job1))
            ->assertSessionHasErrors();

        $this->actingAs($employer)
            ->get(route('account.employer.jobs.applicants', $job3))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('applications.data', 2));
    }

    public function test_workflow_d_three_candidate_paths_on_job_one(): void
    {
        $employer = $this->employerA();
        $job = $this->job2();
        $pathA = $this->applyAsCandidate($this->matrixCandidate('pa'), $job);
        $pathB = $this->applyAsCandidate($this->matrixCandidate('pb'), $job);
        $pathC = $this->applyAsCandidate($this->matrixCandidate('pc'), $job);

        $reason = RejectionReason::query()->where('is_active', true)->firstOrFail();

        // Candidate A — success path through interview
        $this->actingAs($employer)
            ->post(route('account.employer.jobs.application.shortlist', [$job, $pathA]))
            ->assertRedirect();
        $this->actingAs($employer)
            ->post(route('account.employer.jobs.application.interview', [$job, $pathA]), [
                'scheduled_at' => now()->addDays(4)->format('Y-m-d\TH:i'),
                'mode' => 'video',
                'location_or_link' => 'https://meet.example.test/e2e',
            ])
            ->assertRedirect();
        $this->assertSame(ApplicationStatus::Interview, $pathA->fresh()->status);

        // Candidate B — rejected
        $this->actingAs($employer)
            ->post(route('account.employer.jobs.application.reject', [$job, $pathB]), [
                'rejection_reason_id' => $reason->id,
            ])
            ->assertRedirect();
        $this->assertSame(ApplicationStatus::Rejected, $pathB->fresh()->status);

        // Candidate C — stays pending/new
        $this->assertSame(ApplicationStatus::New, $pathC->fresh()->status);
        $this->assertSame(ApplicationStatus::Interview, $pathA->fresh()->status);
    }

    public function test_workflow_e_candidate_notifications_target_correct_application(): void
    {
        $employer = $this->employerA();
        $job = $this->job3();
        $candidate = $this->matrixCandidate('n');
        $application = $this->applyAsCandidate($candidate, $job);

        $this->assertTrue(
            $candidate->fresh()->notifications()
                ->where('data->event', CandidateNotificationEvent::ApplicationReceived->value)
                ->where('data->context->application_id', $application->id)
                ->exists()
        );

        $this->assertTrue(
            $employer->fresh()->notifications()
                ->where('data->event', EmployerNotificationEvent::ApplicationReceived->value)
                ->where('data->context->application_id', $application->id)
                ->exists()
        );

        $this->actingAs($employer)
            ->post(route('account.employer.jobs.application.shortlist', [$job, $application]))
            ->assertRedirect();

        $this->assertTrue(
            $candidate->fresh()->notifications()
                ->where('data->event', CandidateNotificationEvent::ApplicationShortlisted->value)
                ->where('data->context->application_id', $application->id)
                ->exists()
        );

        $other = $this->matrixCandidate('n2');
        $this->assertFalse(
            $other->fresh()->notifications()
                ->where('data->context->application_id', $application->id)
                ->exists()
        );
    }

    public function test_talent_pool_assignment_is_distinct_from_ats_application_list(): void
    {
        $employer = $this->employerA();
        $internalRecruiter = $this->makeStaff(StaffRoleSlug::Recruiter->value, ['email' => 'qa-e2e-recruiter-dist@example.test']);
        $job = $this->job1();
        $candidate = $this->matrixCandidate('dist');

        $application = $this->applyAsCandidate($candidate, $job);

        $this->actingAs($employer)
            ->get(route('account.hiring.candidates'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('applications.data'));

        $this->actingAs($employer)
            ->get(route('account.talent-pool.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('assignments.data', 0));

        $admin = $this->makeStaff(StaffRoleSlug::SuperAdmin->value);
        Application::query()->whereKey($application->id)->update(['status' => ApplicationStatus::Shortlisted]);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.talent-pool-assignments.store'), [
                'candidate_user_id' => $candidate->id,
                'assigned_recruiter_user_id' => $internalRecruiter->id,
                'assignment_type' => 'job',
                'application_id' => $application->id,
                'job_id' => $job->id,
            ])
            ->assertRedirect();

        $this->actingAs($employer)
            ->get(route('account.talent-pool.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('assignments.data', 1));

        $this->actingAs($internalRecruiter, 'admin')
            ->get(route('admin.talent-pool-assignments.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('assignments.data', 1));

        $this->assertSame(
            RecruiterTalentPoolAssignmentStatus::PendingReview,
            RecruiterTalentPoolAssignment::query()->latest('id')->first()?->status
        );
    }
}
