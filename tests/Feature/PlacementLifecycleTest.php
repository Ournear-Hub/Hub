<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\EmployerSubmissionStatus;
use App\Enums\PlacementStatus;
use App\Enums\RecruitmentActivityType;
use App\Models\Application;
use App\Models\Employee;
use App\Models\EmployerSubmission;
use App\Models\Job;
use App\Models\Placement;
use App\Models\RecruitmentActivity;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PlacementLifecycleTest extends TestCase
{
    public function test_selected_submission_can_create_placement_with_lineage_and_business_id(): void
    {
        $admin = $this->makeAdmin();
        [$profile, $job, $application, $submission] = $this->selectedSubmission();

        $this->actingAs($admin)
            ->post(route('admin.submissions.place', $submission))
            ->assertRedirect();

        $placement = Placement::query()->where('employer_submission_id', $submission->id)->first();
        $this->assertNotNull($placement);
        $this->assertSame($profile->id, $placement->employer_profile_id);
        $this->assertSame($job->id, $placement->job_id);
        $this->assertSame($application->id, $placement->application_id);
        $this->assertSame($submission->id, $placement->employer_submission_id);
        $this->assertSame(PlacementStatus::Open, $placement->status);
        $this->assertStringStartsWith('ONT-P-', (string) $placement->business_id);
        $this->assertSame(ApplicationStatus::Shortlisted, $application->fresh()->status);
        $this->assertSame(EmployerSubmissionStatus::Selected, $submission->fresh()->status);
        $this->assertSame(0, Employee::query()->count());
        $this->assertTrue(RecruitmentActivity::query()
            ->where('placement_id', $placement->id)
            ->where('type', RecruitmentActivityType::PlacementCreated->value)
            ->exists());
    }

    public function test_non_selected_submissions_cannot_create_placement(): void
    {
        $admin = $this->makeAdmin();
        [, , , $submitted] = $this->submittedApplication();

        $this->actingAs($admin)
            ->from(route('admin.submissions.index'))
            ->post(route('admin.submissions.place', $submitted))
            ->assertSessionHasErrors('submission');

        $submitted->update(['status' => EmployerSubmissionStatus::UnderReview]);
        $this->actingAs($admin)
            ->from(route('admin.submissions.index'))
            ->post(route('admin.submissions.place', $submitted))
            ->assertSessionHasErrors('submission');

        $onHold = $this->submittedApplication()[3];
        $this->actingAs($admin)->post(route('admin.submissions.hold', $onHold));
        $this->actingAs($admin)
            ->from(route('admin.submissions.index'))
            ->post(route('admin.submissions.place', $onHold->fresh()))
            ->assertSessionHasErrors('submission');

        $rejected = $this->submittedApplication()[3];
        $this->actingAs($admin)->post(route('admin.submissions.reject', $rejected), ['notes' => 'No']);
        $this->actingAs($admin)
            ->from(route('admin.submissions.index'))
            ->post(route('admin.submissions.place', $rejected->fresh()))
            ->assertSessionHasErrors('submission');

        $this->assertSame(0, Placement::query()->count());
    }

    public function test_duplicate_placement_is_prevented(): void
    {
        $admin = $this->makeAdmin();
        [, , , $submission] = $this->selectedSubmission();

        $this->actingAs($admin)->post(route('admin.submissions.place', $submission))->assertRedirect();
        $this->actingAs($admin)
            ->from(route('admin.submissions.index'))
            ->post(route('admin.submissions.place', $submission))
            ->assertSessionHasErrors('submission');

        $this->assertSame(1, Placement::query()->where('employer_submission_id', $submission->id)->count());
    }

    public function test_offer_acceptance_joining_and_employee_retain_origin(): void
    {
        $admin = $this->makeAdmin();
        [$profile, $job, $application, $submission] = $this->selectedSubmission();
        $originalCandidateId = $application->user?->candidateProfile?->id;

        $this->actingAs($admin)->post(route('admin.submissions.place', $submission));
        $placement = Placement::query()->where('employer_submission_id', $submission->id)->first();

        $this->actingAs($admin)
            ->post(route('admin.placements.offer', $placement), [
                'offered_at' => now()->toDateString(),
                'salary' => '£42,000',
                'notes' => 'Site package',
            ])
            ->assertRedirect();
        $this->assertSame(PlacementStatus::Offered, $placement->fresh()->status);
        $this->assertSame('£42,000', $placement->fresh()->offer?->salary);

        $this->actingAs($admin)
            ->from(route('admin.placements.index'))
            ->post(route('admin.placements.join', $placement), [
                'actual_joining_date' => now()->toDateString(),
            ])
            ->assertSessionHasErrors('status');

        $this->actingAs($admin)->post(route('admin.placements.accept', $placement))->assertRedirect();
        $this->assertSame(PlacementStatus::Accepted, $placement->fresh()->status);

        $this->actingAs($admin)
            ->from(route('admin.placements.index'))
            ->post(route('admin.placements.confirm-joining', $placement))
            ->assertSessionHasErrors('expected_joining_date');

        $this->actingAs($admin)
            ->post(route('admin.placements.confirm-joining', $placement), [
                'expected_joining_date' => now()->addDays(7)->toDateString(),
            ])
            ->assertRedirect();
        $this->assertSame(PlacementStatus::JoiningConfirmed, $placement->fresh()->status);

        $this->actingAs($admin)
            ->post(route('admin.placements.join', $placement), [
                'actual_joining_date' => now()->addDays(7)->toDateString(),
            ])
            ->assertRedirect();

        $placement->refresh();
        $employee = $placement->employee;
        $this->assertNotNull($employee);
        $this->assertSame(PlacementStatus::Joined, $placement->status);
        $this->assertStringStartsWith('ONT-W-', (string) $employee->business_id);
        $this->assertSame($profile->id, $employee->employer_profile_id);
        $this->assertSame($job->id, $employee->job_id);
        $this->assertSame($application->id, $employee->application_id);
        $this->assertSame($submission->id, $employee->employer_submission_id);
        $this->assertSame($originalCandidateId, $employee->candidate_profile_id);
        $this->assertSame(ApplicationStatus::Shortlisted, $application->fresh()->status);
        $this->assertSame(EmployerSubmissionStatus::Selected, $submission->fresh()->status);
        $this->assertTrue(Application::query()->whereKey($application->id)->exists());
        $this->assertTrue(EmployerSubmission::query()->whereKey($submission->id)->exists());
        $this->assertSame(1, Employee::query()->count());

        $this->actingAs($admin)
            ->get(route('admin.employees.index', ['employee' => $employee->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Employees/Index')
                ->where('selected.business_id', $employee->business_id)
                ->where('selected.placement.business_id', $placement->business_id));
    }

    public function test_declined_and_withdrawn_transitions_are_blocked_from_joining(): void
    {
        $admin = $this->makeAdmin();
        [, , , $declinedSubmission] = $this->selectedSubmission();
        $this->actingAs($admin)->post(route('admin.submissions.place', $declinedSubmission));
        $declined = Placement::query()->where('employer_submission_id', $declinedSubmission->id)->first();
        $this->actingAs($admin)->post(route('admin.placements.offer', $declined), [
            'offered_at' => now()->toDateString(),
        ]);
        $this->actingAs($admin)->post(route('admin.placements.decline', $declined), ['notes' => 'Client changed plan']);
        $this->assertSame(PlacementStatus::Declined, $declined->fresh()->status);
        $this->actingAs($admin)
            ->from(route('admin.placements.index'))
            ->post(route('admin.placements.accept', $declined))
            ->assertSessionHasErrors('status');

        [, , , $withdrawnSubmission] = $this->selectedSubmission();
        $this->actingAs($admin)->post(route('admin.submissions.place', $withdrawnSubmission));
        $withdrawn = Placement::query()->where('employer_submission_id', $withdrawnSubmission->id)->first();
        $this->actingAs($admin)->post(route('admin.placements.withdraw', $withdrawn));
        $this->assertSame(PlacementStatus::Withdrawn, $withdrawn->fresh()->status);
        $this->actingAs($admin)
            ->from(route('admin.placements.index'))
            ->post(route('admin.placements.offer', $withdrawn), ['offered_at' => now()->toDateString()])
            ->assertSessionHasErrors('status');

        $this->assertSame(0, Employee::query()->count());
    }

    public function test_guest_application_can_complete_joining_without_candidate_profile(): void
    {
        $admin = $this->makeAdmin();
        $employer = $this->makeEmployer();
        $profile = $employer->employerProfile;
        $job = Job::factory()->create(['employer_id' => $profile->id]);
        $application = Application::factory()->create([
            'job_id' => $job->id,
            'user_id' => null,
            'status' => ApplicationStatus::Shortlisted,
        ]);
        $this->actingAs($admin)->post(route('admin.applications.submit', $application));
        $submission = EmployerSubmission::query()->where('application_id', $application->id)->first();
        $this->actingAs($admin)->post(route('admin.submissions.select', $submission));
        $this->actingAs($admin)->post(route('admin.submissions.place', $submission->fresh()));
        $placement = Placement::query()->where('application_id', $application->id)->first();
        $this->actingAs($admin)->post(route('admin.placements.offer', $placement), ['offered_at' => now()->toDateString()]);
        $this->actingAs($admin)->post(route('admin.placements.accept', $placement));
        $this->actingAs($admin)->post(route('admin.placements.confirm-joining', $placement), [
            'expected_joining_date' => now()->toDateString(),
        ]);
        $this->actingAs($admin)->post(route('admin.placements.join', $placement), [
            'actual_joining_date' => now()->toDateString(),
        ]);

        $employee = Employee::query()->where('application_id', $application->id)->first();
        $this->assertNotNull($employee);
        $this->assertNull($employee->candidate_profile_id);
        $this->assertNull($application->fresh()->user_id);
    }

    public function test_candidate_and_employer_cannot_manage_placements(): void
    {
        [, , , $submission] = $this->selectedSubmission();

        $this->actingAs($this->makeCandidate())
            ->post(route('admin.submissions.place', $submission))
            ->assertForbidden();

        $this->actingAs($this->makeEmployer())
            ->get(route('admin.placements.index'))
            ->assertForbidden();

        $this->actingAs($this->makeEmployer())
            ->get(route('admin.employees.index'))
            ->assertForbidden();
    }

    /**
     * @return array{0: \App\Models\EmployerProfile, 1: Job, 2: Application, 3: EmployerSubmission}
     */
    private function selectedSubmission(): array
    {
        [$profile, $job, $application, $submission] = $this->submittedApplication();
        $this->actingAs($this->makeAdmin())->post(route('admin.submissions.select', $submission));

        return [$profile, $job, $application, $submission->fresh()];
    }

    /**
     * @return array{0: \App\Models\EmployerProfile, 1: Job, 2: Application, 3: EmployerSubmission}
     */
    private function submittedApplication(): array
    {
        $employer = $this->makeEmployer();
        $profile = $employer->employerProfile;
        $this->assertNotNull($profile);
        $job = Job::factory()->create(['employer_id' => $profile->id, 'title' => 'Site Engineer']);
        $candidate = $this->makeCandidate(['name' => 'Asha Candidate']);
        $application = Application::factory()->create([
            'job_id' => $job->id,
            'user_id' => $candidate->id,
            'full_name' => 'Asha Candidate',
            'status' => ApplicationStatus::Shortlisted,
        ]);

        $this->actingAs($this->makeAdmin())->post(route('admin.applications.submit', $application));
        $submission = EmployerSubmission::query()->where('application_id', $application->id)->first();
        $this->assertNotNull($submission);

        return [$profile, $job, $application, $submission];
    }
}
