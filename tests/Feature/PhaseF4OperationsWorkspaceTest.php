<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\EmployerSubmissionStatus;
use App\Enums\PlacementStatus;
use App\Enums\RecruitmentActivityType;
use App\Enums\ReplacementReason;
use App\Enums\ReplacementStatus;
use App\Enums\StaffRoleSlug;
use App\Models\Application;
use App\Models\Employee;
use App\Models\EmployerSubmission;
use App\Models\Job;
use App\Models\Placement;
use App\Models\RecruitmentActivity;
use App\Models\Replacement;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PhaseF4OperationsWorkspaceTest extends TestCase
{
    public function test_employer_list_and_360_show_stored_relationships_without_invented_fields(): void
    {
        $admin = $this->makeAdmin();
        [$profile, $job, $application, $submission, $placement, $employee] = $this->joinedEmployee();
        $candidate = $application->user?->candidateProfile;
        $this->assertNotNull($candidate);

        $this->actingAs($admin)
            ->get(route('admin.employers.index', [
                'search' => $profile->company_name,
                'employer_id' => $profile->business_id,
                'employer' => $profile->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Employers/Index')
                ->has('employers.data', 1)
                ->where('employers.data.0.business_id', $profile->business_id)
                ->where('employers.data.0.location', null)
                ->where('employers.data.0.active_jobs_count', fn ($count) => $count >= 1)
                ->where('employers.data.0.applications_count', 1)
                ->where('employers.data.0.submissions_count', 1)
                ->where('employers.data.0.selections_count', 1)
                ->where('employers.data.0.placements_count', 1)
                ->where('employers.data.0.employees_count', 1)
                ->where('selected.id', $profile->id)
                ->where('selected.links.jobs', '/admin/jobs?employer_id='.$profile->id)
                ->where('selected.links.submissions', '/admin/submissions?employer_id='.$profile->id)
                ->where('selected.links.placements', '/admin/placements?employer_id='.$profile->id)
                ->where('selected.links.employees', '/admin/employees?employer_id='.$profile->id)
                ->where('selected.links.replacements', '/admin/replacements?employer_id='.$profile->id)
                ->has('jobs', 1)
                ->has('applications', 1)
                ->where('applications.0.business_id', $application->business_id)
                ->has('submissions', 1)
                ->has('selections', 1)
                ->has('placements', 1)
                ->has('employees', 1)
                ->where('employees.0.business_id', $employee->business_id));
    }

    public function test_submission_workspace_exposes_chain_and_existing_record_links(): void
    {
        $admin = $this->makeAdmin();
        [$profile, $job, $application, $submission] = $this->submittedApplication();
        $candidate = $application->user?->candidateProfile;
        $this->assertNotNull($candidate);

        $this->actingAs($admin)
            ->get(route('admin.submissions.index', [
                'employer_id' => $profile->id,
                'job_id' => $job->id,
                'candidate_id' => $candidate->id,
                'response_status' => 'Pending',
                'submission' => $submission->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Submissions/Index')
                ->has('submissions.data', 1)
                ->where('submissions.data.0.business_id', $submission->business_id)
                ->where('submissions.data.0.response_status', 'Awaiting employer response')
                ->where('selected.links.employer', '/admin/employers?employer='.$profile->id)
                ->where('selected.links.job', '/admin/jobs?job='.$job->slug)
                ->where('selected.links.candidate', '/admin/candidates?candidate='.$candidate->id)
                ->where('selected.links.application', '/admin/applications?application='.$application->id)
                ->where('selected.links.screening', '/admin/screening?job_id='.$job->id.'&application='.$application->id)
                ->where('selected.can_review', true)
                ->where('selected.interview_requested_available', false)
                ->has('selected.chain')
                ->where('selected.chain.0.key', 'candidate')
                ->where('selected.chain.4.key', 'submission')
                ->where('selected.chain.6.key', 'selection'));
    }

    public function test_employer_response_records_history_and_selection_is_the_existing_status(): void
    {
        $admin = $this->makeAdmin();
        [, , $application, $submission] = $this->submittedApplication();

        $this->actingAs($admin)
            ->post(route('admin.submissions.review', $submission), ['notes' => 'Client checking CV'])
            ->assertRedirect();

        $submission->refresh();
        $this->assertSame(EmployerSubmissionStatus::UnderReview, $submission->status);
        $this->assertSame($admin->id, $submission->decided_by);
        $this->assertNotNull($submission->decided_at);
        $this->assertTrue(RecruitmentActivity::query()
            ->where('employer_submission_id', $submission->id)
            ->where('type', RecruitmentActivityType::SubmissionStatusChanged->value)
            ->where('from_status', EmployerSubmissionStatus::Submitted->value)
            ->where('to_status', EmployerSubmissionStatus::UnderReview->value)
            ->exists());

        $this->actingAs($admin)
            ->post(route('admin.submissions.select', $submission), ['notes' => 'Proceed to placement'])
            ->assertRedirect();

        $submission->refresh();
        $this->assertSame(EmployerSubmissionStatus::Selected, $submission->status);
        $this->assertSame(ApplicationStatus::Shortlisted, $application->fresh()->status);
        $this->assertTrue(RecruitmentActivity::query()
            ->where('employer_submission_id', $submission->id)
            ->where('type', RecruitmentActivityType::EmployerSelected->value)
            ->where('from_status', EmployerSubmissionStatus::UnderReview->value)
            ->where('to_status', EmployerSubmissionStatus::Selected->value)
            ->exists());

        $this->actingAs($admin)
            ->get(route('admin.submissions.index', [
                'status' => EmployerSubmissionStatus::Selected->value,
                'submission' => $submission->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('selected.selection_outcome', 'Selected')
                ->where('selected.can_create_placement', true));
    }

    public function test_interview_requested_is_not_a_stored_v11_status(): void
    {
        $admin = $this->makeAdmin();
        $this->submittedApplication();

        $this->actingAs($admin)
            ->get(route('admin.submissions.index', ['response_status' => 'Interview Requested']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('submissions.data', 0));
    }

    public function test_placement_offer_joining_and_employee_workspaces_keep_lineage_links(): void
    {
        $admin = $this->makeAdmin();
        [$profile, $job, $application, $submission, $placement, $employee] = $this->joinedEmployee();
        $candidate = $application->user?->candidateProfile;
        $this->assertNotNull($candidate);

        $this->actingAs($admin)
            ->get(route('admin.placements.index', [
                'employer_id' => $profile->id,
                'candidate_id' => $candidate->id,
                'joining_status' => 'joined',
                'placement' => $placement->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Placements/Index')
                ->has('placements.data', 1)
                ->where('placements.data.0.joining_status', 'Joined')
                ->where('placements.data.0.offer_status', 'Accepted')
                ->where('selected.links.employer', '/admin/employers?employer='.$profile->id)
                ->where('selected.links.candidate', '/admin/candidates?candidate='.$candidate->id)
                ->where('selected.links.application', '/admin/applications?application='.$application->id)
                ->where('selected.links.job', '/admin/jobs?job='.$job->slug)
                ->where('selected.links.selection', '/admin/submissions?submission='.$submission->id)
                ->where('selected.links.employee', '/admin/employees?employee='.$employee->id)
                ->where('selected.offer.reference_available', false)
                ->where('selected.offer.document_available', false)
                ->where('selected.chain.5.key', 'placement')
                ->where('selected.chain.8.key', 'employee'));

        $this->actingAs($admin)
            ->get(route('admin.employees.index', [
                'employer_id' => $profile->id,
                'employee' => $employee->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Employees/Index')
                ->where('selected.employment_status', 'Joined')
                ->where('selected.candidate.id', $candidate->id)
                ->where('selected.links.candidate', '/admin/candidates?candidate='.$candidate->id)
                ->where('selected.links.employer', '/admin/employers?employer='.$profile->id)
                ->where('selected.links.placement', '/admin/placements?placement='.$placement->id)
                ->where('selected.chain.0.key', 'candidate'));
    }

    public function test_replacement_preserves_original_placement_and_exposes_history_links(): void
    {
        $admin = $this->makeAdmin();
        [$profile, $job, $originalApplication, $originalSubmission, $originalPlacement, $originalEmployee] = $this->joinedEmployee();
        $originalCandidate = $originalApplication->user?->candidateProfile;
        $this->assertNotNull($originalCandidate);

        $this->actingAs($admin)->post(route('admin.employees.replacements.store', $originalEmployee), [
            'reason' => ReplacementReason::Resigned->value,
        ]);
        $replacement = Replacement::query()->where('original_employee_id', $originalEmployee->id)->first();
        $this->assertNotNull($replacement);

        $replacementCandidateUser = $this->makeCandidate(['name' => 'Bala Replacement']);
        $replacementApplication = Application::factory()->create([
            'job_id' => $job->id,
            'user_id' => $replacementCandidateUser->id,
            'full_name' => 'Bala Replacement',
            'status' => ApplicationStatus::Shortlisted,
        ]);
        $this->actingAs($admin)->post(route('admin.replacements.identify', $replacement), [
            'application_id' => $replacementApplication->id,
        ]);

        $originalPlacement->refresh();
        $originalEmployee->refresh();
        $this->assertSame(PlacementStatus::Joined, $originalPlacement->status);
        $this->assertSame($originalApplication->id, $originalEmployee->application_id);
        $this->assertSame($originalPlacement->id, $originalEmployee->placement_id);

        $this->actingAs($admin)
            ->get(route('admin.replacements.index', ['replacement' => $replacement->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Replacements/Index')
                ->where('selected.original_placement.business_id', $originalPlacement->business_id)
                ->where('selected.original_candidate.business_id', $originalCandidate->business_id)
                ->where('selected.links.original_placement', '/admin/placements?placement='.$originalPlacement->id)
                ->where('selected.links.original_candidate', '/admin/candidates?candidate='.$originalCandidate->id)
                ->where('selected.links.employer', '/admin/employers?employer='.$profile->id)
                ->where('selected.links.job', '/admin/jobs?job='.$job->slug)
                ->where('selected.links.replacement_candidate', '/admin/candidates?candidate='.$replacementCandidateUser->candidateProfile?->id)
                ->where('selected.eligibility', 'Eligible')
                ->has('selected.chain')
                ->has('selected.workflow'));

        $this->actingAs($admin)
            ->get(route('admin.placements.index', ['placement' => $originalPlacement->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('selected.candidate.id', $originalCandidate->id)
                ->where('selected.replacement.as_original.id', $replacement->id));
    }

    public function test_rbac_view_and_manage_permissions_are_preserved(): void
    {
        $viewer = $this->makeStaff(StaffRoleSlug::Viewer->value);
        $finance = $this->makeStaff(StaffRoleSlug::Finance->value);
        $recruiter = $this->makeStaff(StaffRoleSlug::Recruiter->value);
        [, , , $submission] = $this->submittedApplication();

        $this->actingAs($viewer)->get(route('admin.employers.index'))->assertOk();
        $this->actingAs($viewer)->get(route('admin.submissions.index'))->assertOk();
        $this->actingAs($viewer)->get(route('admin.placements.index'))->assertOk();
        $this->actingAs($viewer)->get(route('admin.employees.index'))->assertOk();
        $this->actingAs($viewer)->get(route('admin.replacements.index'))->assertOk();
        $this->actingAs($viewer)
            ->post(route('admin.submissions.review', $submission))
            ->assertForbidden();
        $this->actingAs($viewer)
            ->post(route('admin.submissions.select', $submission))
            ->assertForbidden();

        $this->actingAs($finance)->get(route('admin.employers.index'))->assertForbidden();
        $this->actingAs($finance)->get(route('admin.submissions.index'))->assertForbidden();
        $this->actingAs($finance)->get(route('admin.employees.index'))->assertOk();

        $this->actingAs($recruiter)
            ->post(route('admin.submissions.review', $submission), ['notes' => 'Reviewing'])
            ->assertRedirect();
        $this->assertSame(EmployerSubmissionStatus::UnderReview, $submission->fresh()->status);
    }

    public function test_dashboard_pipeline_counts_stay_accurate_and_replacement_is_separate(): void
    {
        $admin = $this->makeAdmin();
        [$profile, $job, $application, $submission, $placement, $employee] = $this->joinedEmployee();
        $this->actingAs($admin)->post(route('admin.employees.replacements.store', $employee), [
            'reason' => ReplacementReason::Resigned->value,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('stats.recruitment_pipeline', 7)
                ->where('stats.recruitment_pipeline.0.label', 'Applications')
                ->where('stats.recruitment_pipeline.3.label', 'Employer Submission')
                ->where('stats.recruitment_pipeline.6.label', 'Joined')
                ->where('stats.recruitment.employer_submissions.value', 1)
                ->where('stats.recruitment.selected.value', 0)
                ->where('stats.recruitment.placements.value', 1)
                ->where('stats.recruitment.joined_employees.value', 1)
                ->where('stats.recruitment.replacement_cases.value', 1)
                ->where('stats.recruitment_pipeline.3.value', 1)
                ->where('stats.recruitment_pipeline.5.value', 1)
                ->where('stats.recruitment_pipeline.6.value', 1));

        $this->assertSame(ApplicationStatus::Shortlisted, $application->fresh()->status);
        $this->assertSame(EmployerSubmissionStatus::Selected, $submission->fresh()->status);
        $this->assertSame(PlacementStatus::Joined, $placement->fresh()->status);
        $this->assertSame($profile->id, $employee->fresh()->employer_profile_id);
        $this->assertSame($job->id, $employee->fresh()->job_id);
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
            'salary' => '850000',
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
        $profile->update(['company_name' => 'BuildCo Ltd', 'contact_person' => 'Sam Contact']);
        $job = Job::factory()->create(['employer_id' => $profile->id, 'title' => 'Site Engineer', 'active' => true]);
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
