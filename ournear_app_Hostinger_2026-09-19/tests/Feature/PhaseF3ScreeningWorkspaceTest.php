<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\RecruitmentActivityType;
use App\Enums\ScreeningAction;
use App\Enums\ScreeningDecision;
use App\Enums\ScreeningStage;
use App\Models\Application;
use App\Models\EmployerSubmission;
use App\Models\Job;
use App\Models\RecruitmentActivity;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PhaseF3ScreeningWorkspaceTest extends TestCase
{
    public function test_screening_workspace_loads_and_lists_existing_applications(): void
    {
        $admin = $this->makeAdmin();
        Application::factory()->create(['full_name' => 'Screening Workspace Candidate']);

        $this->actingAs($admin)
            ->get(route('admin.screening.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Screening/Index')
                ->has('applications.data', 1)
                ->where('applications.data.0.full_name', 'Screening Workspace Candidate')
                ->where('applications.data.0.screening_decision_label', 'Pending')
                ->where('applications.data.0.skills', null)
                ->where('jobSummary', null));
    }

    public function test_job_filtering_and_job_summary_use_real_counts(): void
    {
        $admin = $this->makeAdmin();
        $employer = $this->makeEmployer();
        $profile = $employer->employerProfile;
        $this->assertNotNull($profile);
        $profile->update(['company_name' => 'North Logistics']);

        $job = Job::factory()->create([
            'title' => 'Warehouse Operative',
            'employer_id' => $profile->id,
            'location' => 'Leeds',
            'experience' => '1-3 years',
            'vacancy' => 4,
        ]);
        $other = Job::factory()->create(['title' => 'Chef']);

        $matching = Application::factory()->create([
            'job_id' => $job->id,
            'full_name' => 'Leeds Applicant',
            'current_location' => 'Leeds',
            'experience' => '1-3 years',
        ]);
        Application::factory()->create([
            'job_id' => $other->id,
            'full_name' => 'Other Applicant',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.screening.index', ['job_id' => $job->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Screening/Index')
                ->has('applications.data', 1)
                ->where('applications.data.0.id', $matching->id)
                ->where('filters.job_id', (string) $job->id)
                ->where('jobSummary.business_id', $job->business_id)
                ->where('jobSummary.title', 'Warehouse Operative')
                ->where('jobSummary.employer.company_name', 'North Logistics')
                ->where('jobSummary.applications_count', 1)
                ->where('jobSummary.pending_screening_count', 1)
                ->where('jobSummary.shortlisted_count', 0)
                ->where('jobSummary.rejected_count', 0)
                ->where('jobSummary.on_hold_count', 0)
                ->has('jobSummary.pipeline', 8)
                ->where('jobSummary.pipeline.0.label', 'Applications')
                ->where('jobSummary.pipeline.7.label', 'Joined'));
    }

    public function test_screening_records_display_match_and_unrecorded_fields(): void
    {
        $admin = $this->makeAdmin();
        $job = Job::factory()->create([
            'location' => 'Birmingham',
            'experience' => '3-5 years',
        ]);
        $application = Application::factory()->create([
            'job_id' => $job->id,
            'current_location' => 'Birmingham',
            'experience' => '3-5 years',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.screening.index', [
                'job_id' => $job->id,
                'application' => $application->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('applications.data.0.match.state', 'scored')
                ->where('applications.data.0.match.percent', 100)
                ->where('applications.data.0.current_company', null)
                ->where('applications.data.0.notice_period', null)
                ->where('selected.match.state', 'scored')
                ->where('selected.employer_submission_state', 'not_shortlisted')
                ->has('selected.screening_stages')
                ->has('selected.screening_timeline')
                ->where('selected.screening_timeline.0.action', 'Application received'));
    }

    public function test_candidate_comparison_payload_is_available_for_the_same_job(): void
    {
        $admin = $this->makeAdmin();
        $job = Job::factory()->create([
            'location' => 'London',
            'experience' => '1-3 years',
        ]);
        Application::factory()->create([
            'job_id' => $job->id,
            'full_name' => 'First Compare',
            'current_location' => 'London',
            'experience' => '1-3 years',
        ]);
        Application::factory()->create([
            'job_id' => $job->id,
            'full_name' => 'Second Compare',
            'current_location' => 'Manchester',
            'experience' => '1-3 years',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.screening.index', ['job_id' => $job->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('applications.data', 2)
                ->where('applications.data.0.match.state', 'scored')
                ->where('applications.data.1.match.state', 'scored'));
    }

    public function test_screening_actions_progress_status_without_changing_on_view(): void
    {
        $admin = $this->makeAdmin();
        $application = Application::factory()->create(['status' => ApplicationStatus::New]);

        $this->actingAs($admin)
            ->get(route('admin.screening.index', ['application' => $application->id]))
            ->assertOk();

        $application->refresh();
        $this->assertSame(ApplicationStatus::New, $application->status);
        $this->assertNull($application->screening);
        $this->assertSame(0, RecruitmentActivity::query()->where('application_id', $application->id)->count());

        $this->actingAs($admin)
            ->post(route('admin.applications.screening.actions', $application), [
                'action' => ScreeningAction::Start->value,
                'remarks' => 'Opening the file.',
            ])
            ->assertRedirect();

        $application->refresh();
        $this->assertSame(ApplicationStatus::Reviewing, $application->status);
        $this->assertSame(ScreeningStage::Started, $application->screening?->stage);
        $this->assertSame(ScreeningDecision::Pending, $application->screening?->decision);

        $this->actingAs($admin)
            ->post(route('admin.applications.screening.actions', $application), [
                'action' => ScreeningAction::Match->value,
                'remarks' => 'Location and experience line up.',
            ])
            ->assertRedirect();

        $application->refresh();
        $this->assertSame(ScreeningDecision::Suitable, $application->screening?->decision);
        $this->assertSame(ScreeningStage::Matched, $application->screening?->stage);

        $this->actingAs($admin)
            ->post(route('admin.applications.screening.actions', $application), [
                'action' => ScreeningAction::Contact->value,
                'remarks' => 'Reached on mobile.',
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->post(route('admin.applications.screening.actions', $application), [
                'action' => ScreeningAction::Called->value,
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->post(route('admin.applications.screening.actions', $application), [
                'action' => ScreeningAction::Interviewed->value,
                'remarks' => 'Interview completed by phone.',
            ])
            ->assertRedirect();

        $application->refresh();
        $this->assertSame(ScreeningStage::Interviewed, $application->screening?->stage);
        $this->assertSame(ApplicationStatus::Reviewing, $application->status);
        $this->assertTrue(RecruitmentActivity::query()
            ->where('application_id', $application->id)
            ->where('type', RecruitmentActivityType::CandidateContacted->value)
            ->exists());
        $this->assertTrue(RecruitmentActivity::query()
            ->where('application_id', $application->id)
            ->where('type', RecruitmentActivityType::CandidateCalled->value)
            ->exists());
        $this->assertTrue(RecruitmentActivity::query()
            ->where('application_id', $application->id)
            ->where('type', RecruitmentActivityType::CandidateInterviewed->value)
            ->exists());
    }

    public function test_shortlist_and_hold_connect_to_existing_workspaces(): void
    {
        $admin = $this->makeAdmin();
        $application = Application::factory()->create(['status' => ApplicationStatus::New]);

        $this->actingAs($admin)
            ->post(route('admin.applications.screening.actions', $application), [
                'action' => ScreeningAction::Hold->value,
                'remarks' => 'Waiting for availability.',
            ])
            ->assertRedirect();

        $application->refresh();
        $this->assertSame(ScreeningStage::Held, $application->screening?->stage);
        $this->assertSame(ApplicationStatus::New, $application->status);
        $this->assertSame($admin->id, $application->screening?->held_by);

        $this->actingAs($admin)
            ->post(route('admin.applications.shortlist', $application), [
                'notes' => 'Ready for the client.',
            ])
            ->assertRedirect();

        $application->refresh();
        $this->assertSame(ApplicationStatus::Shortlisted, $application->status);
        $this->assertSame(ScreeningDecision::Shortlisted, $application->screening?->decision);
        $this->assertSame(ScreeningStage::Shortlisted, $application->screening?->stage);
        $this->assertSame($application->job_id, $application->job_id);
        $this->assertSame($application->id, $application->id);

        $this->actingAs($admin)
            ->get(route('admin.shortlisted.index', ['application' => $application->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Shortlisted/Index')
                ->has('applications.data', 1)
                ->where('applications.data.0.id', $application->id)
                ->where('applications.data.0.job.id', $application->job_id)
                ->where('selected.screening.decision', ScreeningDecision::Shortlisted->value));
    }

    public function test_employer_submission_connection_uses_existing_records(): void
    {
        $admin = $this->makeAdmin();
        $employer = $this->makeEmployer();
        $profile = $employer->employerProfile;
        $this->assertNotNull($profile);
        $job = Job::factory()->create(['employer_id' => $profile->id]);
        $application = Application::factory()->create([
            'job_id' => $job->id,
            'status' => ApplicationStatus::New,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.applications.shortlist', $application))
            ->assertRedirect();

        $this->actingAs($admin)
            ->get(route('admin.screening.index', [
                'job_id' => $job->id,
                'application' => $application->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('selected.employer_submission_state', 'ready_for_submission')
                ->where('selected.can_submit_to_employer', true));

        $this->actingAs($admin)
            ->post(route('admin.applications.submit', $application))
            ->assertRedirect();

        $this->assertSame(1, EmployerSubmission::query()->where('application_id', $application->id)->count());
        $application->refresh();
        $this->assertSame(ScreeningStage::Submitted, $application->screening?->stage);

        $this->actingAs($admin)
            ->get(route('admin.screening.index', [
                'job_id' => $job->id,
                'application' => $application->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('selected.employer_submission_state', 'submitted')
                ->where('selected.can_submit_to_employer', false));
    }

    public function test_screening_timeline_records_meaningful_activity_only(): void
    {
        $admin = $this->makeAdmin();
        $application = Application::factory()->create(['status' => ApplicationStatus::New]);

        $this->actingAs($admin)
            ->get(route('admin.screening.index', ['application' => $application->id]))
            ->assertOk();

        $this->assertSame(0, RecruitmentActivity::query()->where('application_id', $application->id)->count());

        $this->actingAs($admin)
            ->post(route('admin.applications.screening.actions', $application), [
                'action' => ScreeningAction::Start->value,
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->get(route('admin.screening.index', ['application' => $application->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('selected.screening_timeline.0.action', 'Application received')
                ->where('selected.screening_timeline', fn ($timeline) => collect($timeline)->contains(
                    fn ($item) => ($item['action'] ?? null) === 'Screening started'
                )));
    }

    public function test_unauthorized_users_cannot_perform_restricted_screening_actions(): void
    {
        $application = Application::factory()->create(['status' => ApplicationStatus::New]);
        $viewer = $this->makeStaff('viewer');

        $this->actingAs($viewer)
            ->get(route('admin.screening.index'))
            ->assertOk();

        $this->actingAs($viewer)
            ->post(route('admin.applications.screening.actions', $application), [
                'action' => ScreeningAction::Start->value,
            ])
            ->assertForbidden();

        $this->actingAs($this->makeStaff('finance'))
            ->post(route('admin.applications.shortlist', $application))
            ->assertForbidden();

        $this->actingAs($this->makeCandidate())
            ->post(route('admin.applications.screening.actions', $application), [
                'action' => ScreeningAction::Hold->value,
            ])
            ->assertForbidden();

        $application->refresh();
        $this->assertNull($application->screening);
        $this->assertSame(ApplicationStatus::New, $application->status);

        $this->actingAs($this->makeStaff('recruiter'))
            ->post(route('admin.applications.screening.actions', $application), [
                'action' => ScreeningAction::Start->value,
            ])
            ->assertRedirect();

        $this->assertNotNull($application->fresh()->screening);
    }
}
