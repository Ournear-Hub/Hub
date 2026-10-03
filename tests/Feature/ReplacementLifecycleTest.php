<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\PlacementStatus;
use App\Enums\RecruitmentActivityType;
use App\Enums\ReplacementReason;
use App\Enums\ReplacementStatus;
use App\Models\Application;
use App\Models\Employee;
use App\Models\EmployerSubmission;
use App\Models\Job;
use App\Models\Placement;
use App\Models\RecruitmentActivity;
use App\Models\Replacement;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ReplacementLifecycleTest extends TestCase
{
    public function test_joined_employee_can_create_replacement_that_retains_original_lineage(): void
    {
        $admin = $this->makeAdmin();
        [$profile, $job, $application, $submission, $placement, $employee] = $this->joinedEmployee();
        $originalEmployeePayload = $employee->only(['id', 'application_id', 'placement_id', 'employer_profile_id', 'job_id', 'employer_submission_id']);

        $this->actingAs($admin)
            ->post(route('admin.employees.replacements.store', $employee), [
                'reason' => ReplacementReason::Resigned->value,
                'notes' => 'Left after probation',
            ])
            ->assertRedirect();

        $replacement = Replacement::query()->where('original_employee_id', $employee->id)->first();
        $this->assertNotNull($replacement);
        $this->assertStringStartsWith('ONT-R-', (string) $replacement->business_id);
        $this->assertSame(ReplacementStatus::Requested, $replacement->status);
        $this->assertSame(ReplacementReason::Resigned, $replacement->reason);
        $this->assertSame('Left after probation', $replacement->notes);
        $this->assertSame($employee->id, $replacement->original_employee_id);
        $this->assertSame($placement->id, $replacement->original_placement_id);
        $this->assertSame($profile->id, $replacement->employer_profile_id);
        $this->assertSame($job->id, $replacement->job_id);
        $this->assertSame($application->id, $replacement->original_application_id);
        $this->assertSame($submission->id, $replacement->original_submission_id);
        $this->assertNull($replacement->replacement_application_id);
        $this->assertNull($replacement->replacement_employee_id);

        $employee->refresh();
        $this->assertSame($originalEmployeePayload['application_id'], $employee->application_id);
        $this->assertSame($originalEmployeePayload['placement_id'], $employee->placement_id);
        $this->assertSame(1, Employee::query()->count());
        $this->assertSame(PlacementStatus::Joined, $placement->fresh()->status);
        $this->assertTrue(RecruitmentActivity::query()
            ->where('replacement_id', $replacement->id)
            ->where('type', RecruitmentActivityType::ReplacementRequested->value)
            ->exists());

        $this->actingAs($admin)
            ->get(route('admin.replacements.index', ['replacement' => $replacement->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Replacements/Index')
                ->where('selected.business_id', $replacement->business_id)
                ->where('selected.original_employee.business_id', $employee->business_id)
                ->where('selected.original_placement.business_id', $placement->business_id)
                ->where('selected.can_identify_candidate', true));
    }

    public function test_non_joined_employee_and_arbitrary_candidate_cannot_create_replacement(): void
    {
        $admin = $this->makeAdmin();
        [, , $application, $submission] = $this->selectedSubmission();
        $this->actingAs($admin)->post(route('admin.submissions.place', $submission));
        $placement = Placement::query()->where('employer_submission_id', $submission->id)->first();

        $orphan = Employee::query()->create([
            'placement_id' => $placement->id,
            'employer_profile_id' => $placement->employer_profile_id,
            'job_id' => $placement->job_id,
            'application_id' => $placement->application_id,
            'employer_submission_id' => $placement->employer_submission_id,
            'joined_at' => now(),
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->from(route('admin.employees.index'))
            ->post(route('admin.employees.replacements.store', $orphan), [
                'reason' => ReplacementReason::Left->value,
            ])
            ->assertSessionHasErrors('employee');

        $this->assertSame(0, Replacement::query()->count());
        $this->assertSame($application->id, $orphan->fresh()->application_id);

        $this->actingAs($admin)
            ->post('/admin/employees/99999/replacements', [
                'reason' => ReplacementReason::Left->value,
            ])
            ->assertNotFound();
    }

    public function test_replacement_candidate_uses_existing_application_and_submission_rules(): void
    {
        $admin = $this->makeAdmin();
        [, $job, $originalApplication, , , $employee] = $this->joinedEmployee();
        $this->actingAs($admin)->post(route('admin.employees.replacements.store', $employee), [
            'reason' => ReplacementReason::EmployerRequested->value,
        ]);
        $replacement = Replacement::query()->where('original_employee_id', $employee->id)->first();

        $newApplication = Application::factory()->create([
            'job_id' => $job->id,
            'full_name' => 'Not Shortlisted',
            'status' => ApplicationStatus::New,
        ]);
        $this->actingAs($admin)
            ->from(route('admin.replacements.index'))
            ->post(route('admin.replacements.identify', $replacement), [
                'application_id' => $newApplication->id,
            ])
            ->assertSessionHasErrors('application');

        $this->actingAs($admin)
            ->from(route('admin.replacements.index'))
            ->post(route('admin.replacements.identify', $replacement), [
                'application_id' => $originalApplication->id,
            ])
            ->assertSessionHasErrors('application');

        $otherJob = Job::factory()->create(['employer_id' => $job->employer_id]);
        $otherApplication = Application::factory()->create([
            'job_id' => $otherJob->id,
            'full_name' => 'Other Job',
            'status' => ApplicationStatus::Shortlisted,
        ]);
        $this->actingAs($admin)
            ->from(route('admin.replacements.index'))
            ->post(route('admin.replacements.identify', $replacement), [
                'application_id' => $otherApplication->id,
            ])
            ->assertSessionHasErrors('application');

        $candidateApplication = $this->shortlistedApplicationFor($job, 'Bala Replacement');
        $this->actingAs($admin)
            ->post(route('admin.replacements.identify', $replacement), [
                'application_id' => $candidateApplication->id,
            ])
            ->assertRedirect();

        $replacement->refresh();
        $this->assertSame(ReplacementStatus::InProgress, $replacement->status);
        $this->assertSame($candidateApplication->id, $replacement->replacement_application_id);
        $this->assertSame($originalApplication->id, $replacement->original_application_id);

        $this->actingAs($admin)->post(route('admin.applications.submit', $candidateApplication))->assertRedirect();
        $submission = EmployerSubmission::query()->where('application_id', $candidateApplication->id)->first();
        $this->assertNotNull($submission);
        $this->assertSame($submission->id, $replacement->fresh()->replacement_submission_id);
        $this->assertSame(ReplacementStatus::InProgress, $replacement->fresh()->status);
        $this->assertNull($replacement->fresh()->completed_at);
    }

    public function test_replacement_creates_new_placement_and_employee_without_overwriting_original(): void
    {
        $admin = $this->makeAdmin();
        [$profile, $job, $originalApplication, $originalSubmission, $originalPlacement, $originalEmployee] = $this->joinedEmployee();
        $this->actingAs($admin)->post(route('admin.employees.replacements.store', $originalEmployee), [
            'reason' => ReplacementReason::Performance->value,
        ]);
        $replacement = Replacement::query()->where('original_employee_id', $originalEmployee->id)->first();
        $candidateApplication = $this->shortlistedApplicationFor($job, 'Bala Replacement');
        $this->actingAs($admin)->post(route('admin.replacements.identify', $replacement), [
            'application_id' => $candidateApplication->id,
        ]);
        $this->actingAs($admin)->post(route('admin.applications.submit', $candidateApplication));
        $candidateSubmission = EmployerSubmission::query()->where('application_id', $candidateApplication->id)->first();
        $this->actingAs($admin)->post(route('admin.submissions.select', $candidateSubmission));
        $this->actingAs($admin)->post(route('admin.submissions.place', $candidateSubmission->fresh()));

        $replacementPlacement = Placement::query()->where('application_id', $candidateApplication->id)->first();
        $this->assertNotNull($replacementPlacement);
        $this->assertNotSame($originalPlacement->id, $replacementPlacement->id);
        $replacement->refresh();
        $this->assertSame($replacementPlacement->id, $replacement->replacement_placement_id);
        $this->assertSame(ReplacementStatus::Placed, $replacement->status);
        $this->assertNull($replacement->completed_at);
        $this->assertNull($replacement->replacement_employee_id);

        $this->actingAs($admin)->post(route('admin.placements.offer', $replacementPlacement), [
            'offered_at' => now()->toDateString(),
        ]);
        $this->actingAs($admin)->post(route('admin.placements.accept', $replacementPlacement));
        $this->actingAs($admin)->post(route('admin.placements.confirm-joining', $replacementPlacement), [
            'expected_joining_date' => now()->toDateString(),
        ]);
        $this->actingAs($admin)->post(route('admin.placements.join', $replacementPlacement), [
            'actual_joining_date' => now()->toDateString(),
        ]);

        $replacementEmployee = Employee::query()->where('application_id', $candidateApplication->id)->first();
        $this->assertNotNull($replacementEmployee);
        $this->assertNotSame($originalEmployee->id, $replacementEmployee->id);
        $this->assertSame($candidateApplication->id, $replacementEmployee->application_id);
        $this->assertSame($candidateSubmission->id, $replacementEmployee->employer_submission_id);
        $this->assertSame($replacementPlacement->id, $replacementEmployee->placement_id);
        $this->assertSame($profile->id, $replacementEmployee->employer_profile_id);
        $this->assertSame($job->id, $replacementEmployee->job_id);

        $originalEmployee->refresh();
        $originalPlacement->refresh();
        $this->assertSame($originalApplication->id, $originalEmployee->application_id);
        $this->assertSame($originalPlacement->id, $originalEmployee->placement_id);
        $this->assertSame($originalSubmission->id, $originalEmployee->employer_submission_id);
        $this->assertSame(PlacementStatus::Joined, $originalPlacement->status);
        $this->assertSame(2, Employee::query()->count());
        $this->assertSame(2, Placement::query()->count());

        $replacement->refresh();
        $this->assertSame(ReplacementStatus::Completed, $replacement->status);
        $this->assertSame($replacementEmployee->id, $replacement->replacement_employee_id);
        $this->assertNotNull($replacement->completed_at);

        $this->actingAs($admin)
            ->get(route('admin.employees.index', ['employee' => $originalEmployee->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('selected.can_request_replacement', false)
                ->where('selected.replacement.originating.business_id', $replacement->business_id));

        $this->actingAs($admin)
            ->from(route('admin.employees.index'))
            ->post(route('admin.employees.replacements.store', $originalEmployee), [
                'reason' => ReplacementReason::Left->value,
            ])
            ->assertSessionHasErrors('employee');
    }

    public function test_failed_or_cancelled_replacement_does_not_corrupt_original_employee(): void
    {
        $admin = $this->makeAdmin();
        [, $job, $originalApplication, , $originalPlacement, $originalEmployee] = $this->joinedEmployee();
        $this->actingAs($admin)->post(route('admin.employees.replacements.store', $originalEmployee), [
            'reason' => ReplacementReason::Absence->value,
        ]);
        $replacement = Replacement::query()->where('original_employee_id', $originalEmployee->id)->first();
        $firstCandidate = $this->shortlistedApplicationFor($job, 'First Attempt');
        $this->actingAs($admin)->post(route('admin.replacements.identify', $replacement), [
            'application_id' => $firstCandidate->id,
        ]);
        $this->actingAs($admin)->post(route('admin.applications.submit', $firstCandidate));
        $firstSubmission = EmployerSubmission::query()->where('application_id', $firstCandidate->id)->first();
        $this->actingAs($admin)->post(route('admin.submissions.select', $firstSubmission));
        $this->actingAs($admin)->post(route('admin.submissions.place', $firstSubmission->fresh()));
        $failedPlacement = Placement::query()->where('application_id', $firstCandidate->id)->first();
        $this->actingAs($admin)->post(route('admin.placements.offer', $failedPlacement), [
            'offered_at' => now()->toDateString(),
        ]);
        $this->actingAs($admin)->post(route('admin.placements.decline', $failedPlacement), ['notes' => 'Did not continue']);

        $this->assertSame(PlacementStatus::Declined, $failedPlacement->fresh()->status);
        $this->assertSame($originalApplication->id, $originalEmployee->fresh()->application_id);
        $this->assertSame(PlacementStatus::Joined, $originalPlacement->fresh()->status);
        $this->assertSame(ReplacementStatus::Placed, $replacement->fresh()->status);
        $this->assertNull($replacement->fresh()->completed_at);

        $secondCandidate = $this->shortlistedApplicationFor($job, 'Second Attempt');
        $this->actingAs($admin)
            ->post(route('admin.replacements.identify', $replacement), [
                'application_id' => $secondCandidate->id,
            ])
            ->assertRedirect();
        $this->assertSame($secondCandidate->id, $replacement->fresh()->replacement_application_id);
        $this->assertNull($replacement->fresh()->replacement_placement_id);
        $this->assertSame($originalEmployee->id, $replacement->fresh()->original_employee_id);

        $this->actingAs($admin)
            ->post(route('admin.replacements.cancel', $replacement), ['notes' => 'Employer paused hiring'])
            ->assertRedirect();
        $this->assertSame(ReplacementStatus::Cancelled, $replacement->fresh()->status);
        $this->assertSame($originalApplication->id, $originalEmployee->fresh()->application_id);
        $this->assertSame(1, Employee::query()->count());
        $this->assertTrue(Employee::query()->whereKey($originalEmployee->id)->exists());
    }

    public function test_open_replacement_blocks_a_second_case_and_candidate_employer_cannot_manage(): void
    {
        $admin = $this->makeAdmin();
        [, , , , , $employee] = $this->joinedEmployee();
        $this->actingAs($admin)->post(route('admin.employees.replacements.store', $employee), [
            'reason' => ReplacementReason::Other->value,
        ]);
        $this->actingAs($admin)
            ->from(route('admin.employees.index'))
            ->post(route('admin.employees.replacements.store', $employee), [
                'reason' => ReplacementReason::Left->value,
            ])
            ->assertSessionHasErrors('employee');
        $this->assertSame(1, Replacement::query()->count());

        $this->actingAs($this->makeCandidate())
            ->get(route('admin.replacements.index'))
            ->assertForbidden();
        $this->actingAs($this->makeEmployer())
            ->get(route('admin.replacements.index'))
            ->assertForbidden();
    }

    /**
     * @return array{0: \App\Models\EmployerProfile, 1: Job, 2: Application, 3: EmployerSubmission, 4: Placement, 5: Employee}
     */
    private function joinedEmployee(): array
    {
        $admin = $this->makeAdmin();
        [$profile, $job, $application, $submission] = $this->selectedSubmission();
        $this->actingAs($admin)->post(route('admin.submissions.place', $submission));
        $placement = Placement::query()->where('employer_submission_id', $submission->id)->first();
        $this->actingAs($admin)->post(route('admin.placements.offer', $placement), [
            'offered_at' => now()->toDateString(),
        ]);
        $this->actingAs($admin)->post(route('admin.placements.accept', $placement));
        $this->actingAs($admin)->post(route('admin.placements.confirm-joining', $placement), [
            'expected_joining_date' => now()->toDateString(),
        ]);
        $this->actingAs($admin)->post(route('admin.placements.join', $placement), [
            'actual_joining_date' => now()->toDateString(),
        ]);

        $placement->refresh();
        $employee = $placement->employee;
        $this->assertNotNull($employee);

        return [$profile, $job, $application, $submission, $placement, $employee];
    }

    /**
     * @return array{0: \App\Models\EmployerProfile, 1: Job, 2: Application, 3: EmployerSubmission}
     */
    private function selectedSubmission(): array
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
        $this->actingAs($this->makeAdmin())->post(route('admin.submissions.select', $submission));

        return [$profile, $job, $application, $submission->fresh()];
    }

    private function shortlistedApplicationFor(Job $job, string $name): Application
    {
        $candidate = $this->makeCandidate(['name' => $name]);

        return Application::factory()->create([
            'job_id' => $job->id,
            'user_id' => $candidate->id,
            'full_name' => $name,
            'status' => ApplicationStatus::Shortlisted,
        ]);
    }
}
