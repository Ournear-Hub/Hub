<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\AutomationActionType;
use App\Enums\AutomationExecutionStatus;
use App\Enums\AutomationScope;
use App\Enums\AutomationTrigger;
use App\Enums\EmployerSubmissionStatus;
use App\Enums\InterviewMode;
use App\Enums\InterviewOutcome;
use App\Enums\PermissionName;
use App\Enums\PlacementStatus;
use App\Enums\ReplacementReason;
use App\Enums\ScreeningDecision;
use App\Enums\StaffRoleSlug;
use App\Models\Application;
use App\Models\EmployerSubmission;
use App\Models\Job;
use App\Models\Placement;
use App\Models\RecruitmentAutomation;
use App\Models\RecruitmentAutomationExecution;
use App\Models\Replacement;
use App\Models\Task;
use App\Models\User;
use App\Services\ApplicationPipelineService;
use App\Services\EmployerSubmissionService;
use App\Services\InterviewService;
use App\Services\PlacementService;
use App\Services\RecruitmentAutomationDispatcher;
use App\Services\RecruitmentAutomationProcessor;
use App\Services\RecruitmentAutomationService;
use App\Services\ReplacementService;
use App\Services\ScreeningMatchService;
use App\Support\AccessCatalog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RecruitmentAutomationStage24Test extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_permission_catalog_includes_automation_view_and_manage(): void
    {
        $this->assertCount(34, PermissionName::cases());
        $this->assertSame(34, count(AccessCatalog::permissionNames()));
        $this->assertContains(PermissionName::AutomationView->value, AccessCatalog::permissionNames());
        $this->assertContains(PermissionName::AutomationManage->value, AccessCatalog::permissionNames());
        $this->assertContains(PermissionName::AutomationManage->value, AccessCatalog::rolePermissions()[StaffRoleSlug::Recruiter->value]);
        $this->assertContains(PermissionName::AutomationView->value, AccessCatalog::rolePermissions()[StaffRoleSlug::Viewer->value]);
        $this->assertNotContains(PermissionName::AutomationManage->value, AccessCatalog::rolePermissions()[StaffRoleSlug::Finance->value]);
        $this->assertFalse(str_contains(file_get_contents(app_path('Services/RecruitmentAutomationDispatcher.php')), '->shortlist('));
        $this->assertFalse(str_contains(file_get_contents(app_path('Services/RecruitmentAutomationDispatcher.php')), 'changeStatus('));
        $this->assertStringContainsString('ScreeningMatchService', file_get_contents(app_path('Services/RecruitmentAutomationScreeningAdvisor.php')));
        $this->assertStringContainsString('jobComparison', file_get_contents(app_path('Services/RecruitmentAutomationScreeningAdvisor.php')));
    }

    public function test_staff_can_open_automations_and_restricted_roles_cannot(): void
    {
        $admin = $this->makeAdmin();
        $finance = $this->makeStaff(StaffRoleSlug::Finance->value);

        $this->actingAs($admin)
            ->get(route('admin.automations.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Automations/Index'));

        $this->actingAs($this->makeStaff(StaffRoleSlug::Viewer->value))
            ->get(route('admin.automations.index'))
            ->assertOk();
        $this->actingAs($finance)->get(route('admin.automations.index'))->assertForbidden();
        $this->actingAs($this->makeCandidate())->get(route('admin.automations.index'))->assertForbidden();
        $this->actingAs($this->makeEmployer())->get(route('admin.automations.index'))->assertForbidden();
    }

    public function test_http_create_rejects_shortlist_action(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->post(route('admin.automations.store'), [
                'name' => 'Illegal auto shortlist',
                'trigger' => AutomationTrigger::ApplicationSubmitted->value,
                'scope' => AutomationScope::Global->value,
                'actions' => ['shortlist'],
            ])
            ->assertSessionHasErrors('actions.0');

        $this->assertSame(0, RecruitmentAutomation::query()->count());
    }

    public function test_application_submitted_reuses_screening_match_and_does_not_shortlist(): void
    {
        $admin = $this->makeAdmin();
        $job = Job::factory()->create([
            'location' => 'Manchester',
            'experience' => '1-3 years',
        ]);
        $this->makeRule($admin, [
            'trigger' => AutomationTrigger::ApplicationSubmitted,
            'conditions' => ['min_match_percent' => 50],
        ]);

        $application = Application::factory()->create([
            'job_id' => $job->id,
            'current_location' => 'Manchester',
            'experience' => '1-3 years',
            'status' => ApplicationStatus::New,
        ]);

        app(RecruitmentAutomationDispatcher::class)->dispatch(AutomationTrigger::ApplicationSubmitted, [
            'application' => $application->fresh(),
            'actor' => $admin,
        ]);

        $fresh = $application->fresh();
        $this->assertSame(ApplicationStatus::New, $fresh->status);
        $this->assertSame(ScreeningDecision::Pending, $fresh->screeningDecision());

        $expected = app(ScreeningMatchService::class)->evaluate($fresh);
        $execution = RecruitmentAutomationExecution::query()->first();
        $this->assertNotNull($execution);
        $this->assertSame(AutomationExecutionStatus::Executed, $execution->status);
        $this->assertSame($expected['percent'], $execution->match_percent);
        $this->assertSame($expected['state'], $execution->match_state);
        $this->assertSame($expected['disclaimer'], $execution->result['match']['disclaimer']);
        $this->assertFalse($execution->result['auto_shortlist']);
        $this->assertNotNull($execution->result['job_comparison']);
        $this->assertArrayHasKey('matching_factors', $execution->result['evidence']);
        $this->assertSame('guest', $execution->result['application_origin']);
        $this->assertSame(1, Task::query()->where('application_id', $fresh->id)->count());
        $this->assertStringContainsString('human decision remains final', strtolower((string) Task::query()->where('application_id', $fresh->id)->value('title')));

        app(RecruitmentAutomationDispatcher::class)->dispatch(AutomationTrigger::ApplicationSubmitted, [
            'application' => $fresh,
            'actor' => $admin,
        ]);
        $this->assertSame(1, RecruitmentAutomationExecution::query()->count());
        $this->assertSame(1, Task::query()->where('application_id', $fresh->id)->count());
    }

    public function test_pipeline_status_change_creates_review_task(): void
    {
        $admin = $this->makeAdmin();
        $job = Job::factory()->create();
        $application = Application::factory()->create([
            'job_id' => $job->id,
            'status' => ApplicationStatus::New,
        ]);
        $this->makeRule($admin, [
            'name' => 'Moved to reviewing',
            'trigger' => AutomationTrigger::ApplicationStatusChanged,
            'actions' => [AutomationActionType::CreateTask->value, AutomationActionType::NotifyAdmin->value],
            'conditions' => ['application_status' => ApplicationStatus::Reviewing->value],
        ]);

        app(ApplicationPipelineService::class)->changeStatus($application, ApplicationStatus::Reviewing, $admin);

        $this->assertSame(ApplicationStatus::Reviewing, $application->fresh()->status);
        $this->assertTrue(
            Task::query()->where('application_id', $application->id)->where('title', 'like', 'Follow up:%')->exists()
        );
        $this->assertSame(ScreeningDecision::Pending, $application->fresh()->screeningDecision());
    }

    public function test_interview_scheduled_hook_creates_task(): void
    {
        $admin = $this->makeAdmin();
        $job = Job::factory()->create();
        $application = Application::factory()->create(['job_id' => $job->id]);
        $this->makeRule($admin, [
            'name' => 'Interview prep',
            'trigger' => AutomationTrigger::InterviewScheduled,
            'actions' => [AutomationActionType::CreateTask->value],
        ]);

        app(InterviewService::class)->schedule($application, [
            'scheduled_at' => now()->addDay()->toDateTimeString(),
            'mode' => InterviewMode::Video,
            'interviewer_name' => 'Alex Recruiter',
            'outcome' => InterviewOutcome::Pending,
        ], $admin);

        $this->assertTrue(Task::query()->where('application_id', $application->id)->exists());
        $this->assertSame(ScreeningDecision::Pending, $application->fresh()->screeningDecision());
    }

    public function test_scheduler_processes_stale_new_applications(): void
    {
        $admin = $this->makeAdmin();
        Carbon::setTestNow(now()->subDays(3));
        $job = Job::factory()->create();
        $application = Application::factory()->create([
            'job_id' => $job->id,
            'status' => ApplicationStatus::New,
        ]);
        Carbon::setTestNow();

        $this->makeRule($admin, [
            'name' => 'Stale new applications',
            'trigger' => AutomationTrigger::ApplicationStale,
            'actions' => [AutomationActionType::CreateTask->value],
            'conditions' => ['delay_hours' => 48],
        ]);

        $this->assertSame(0, Artisan::call('recruitment:send-reminders'));
        $this->assertTrue(Task::query()->where('application_id', $application->id)->exists());
        $this->assertSame(ApplicationStatus::New, $application->fresh()->status);
    }

    public function test_processor_subjects_cover_scheduled_triggers(): void
    {
        $processor = app(RecruitmentAutomationProcessor::class);
        $this->assertSame(0, $processor->processDue());
        $this->assertTrue(class_exists(RecruitmentAutomationDispatcher::class));
    }

    public function test_live_apply_hook_creates_review_task_without_shortlisting(): void
    {
        $admin = $this->makeAdmin();
        $job = Job::factory()->create();
        $this->makeRule($admin, [
            'conditions' => ['allow_insufficient_data' => true],
        ]);

        $application = $this->applyAsCandidate($this->makeCandidate(), $job);

        $this->assertTrue(Task::query()->where('application_id', $application->id)->exists());
        $this->assertSame(ApplicationStatus::New, $application->fresh()->status);
        $this->assertSame(ScreeningDecision::Pending, $application->fresh()->screeningDecision());
    }

    public function test_employer_submission_trigger_creates_task_without_selecting(): void
    {
        $admin = $this->makeAdmin();
        $employer = $this->makeEmployer();
        $profile = $employer->employerProfile;
        $this->assertNotNull($profile);
        $job = Job::factory()->create(['employer_id' => $profile->id]);
        $application = Application::factory()->create(['job_id' => $job->id]);
        $this->makeRule($admin, [
            'name' => 'Client follow-up',
            'trigger' => AutomationTrigger::CandidateSubmittedToEmployer,
            'actions' => [AutomationActionType::CreateTask->value],
        ]);

        $submission = EmployerSubmission::query()->create([
            'employer_profile_id' => $profile->id,
            'job_id' => $job->id,
            'application_id' => $application->id,
            'status' => EmployerSubmissionStatus::Submitted,
            'submitted_by' => $admin->id,
            'submitted_at' => now(),
        ]);

        app(RecruitmentAutomationDispatcher::class)->dispatch(AutomationTrigger::CandidateSubmittedToEmployer, [
            'submission' => $submission,
            'application' => $application,
            'actor' => $admin,
            'employer_profile_id' => $profile->id,
            'job_id' => $job->id,
        ]);

        $this->assertTrue(Task::query()->where('application_id', $application->id)->exists());
        $this->assertSame(EmployerSubmissionStatus::Submitted, $submission->fresh()->status);
        $this->assertSame(ScreeningDecision::Pending, $application->fresh()->screeningDecision());
    }

    public function test_guest_origin_rule_skips_account_applications(): void
    {
        $admin = $this->makeAdmin();
        $job = Job::factory()->create();
        $this->makeRule($admin, [
            'conditions' => [
                'application_origin' => 'guest',
                'allow_insufficient_data' => true,
            ],
        ]);

        $account = Application::factory()->create([
            'job_id' => $job->id,
            'user_id' => $this->makeCandidate()->id,
        ]);
        $guest = Application::factory()->create([
            'job_id' => $job->id,
            'user_id' => null,
        ]);

        $dispatcher = app(RecruitmentAutomationDispatcher::class);
        $dispatcher->dispatch(AutomationTrigger::ApplicationSubmitted, ['application' => $account, 'actor' => $admin]);
        $dispatcher->dispatch(AutomationTrigger::ApplicationSubmitted, ['application' => $guest, 'actor' => $admin]);

        $accountExecution = RecruitmentAutomationExecution::query()->where('application_id', $account->id)->first();
        $guestExecution = RecruitmentAutomationExecution::query()->where('application_id', $guest->id)->first();
        $this->assertSame(AutomationExecutionStatus::Skipped, $accountExecution?->status);
        $this->assertSame('application_origin_mismatch', $accountExecution?->result['reason'] ?? null);
        $this->assertSame(AutomationExecutionStatus::Executed, $guestExecution?->status);
        $this->assertSame('guest', $guestExecution?->result['application_origin'] ?? null);
    }

    public function test_history_exposes_stored_match_evidence(): void
    {
        $admin = $this->makeAdmin();
        $job = Job::factory()->create(['location' => 'Manchester', 'experience' => '1-3 years']);
        $rule = $this->makeRule($admin, [
            'conditions' => ['min_match_percent' => 50],
        ]);
        $application = Application::factory()->create([
            'job_id' => $job->id,
            'current_location' => 'Manchester',
            'experience' => '1-3 years',
        ]);

        app(RecruitmentAutomationDispatcher::class)->dispatch(AutomationTrigger::ApplicationSubmitted, [
            'application' => $application,
            'actor' => $admin,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.automations.index', ['automation' => $rule->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Automations/Index')
                ->has('history.0.matching_factors')
                ->has('history.0.job_comparison')
                ->where('history.0.match_percent', fn ($value) => $value === null || is_int($value)));
    }

    public function test_placement_and_replacement_services_dispatch_without_hiring_decisions(): void
    {
        $admin = $this->makeAdmin();
        [$application, $placement] = $this->staffThroughJoin($admin, false);

        $this->makeRule($admin, [
            'name' => 'Joined follow-up',
            'trigger' => AutomationTrigger::PlacementJoined,
            'actions' => [AutomationActionType::CreateTask->value],
        ]);
        $this->makeRule($admin, [
            'name' => 'Replacement follow-up',
            'trigger' => AutomationTrigger::ReplacementRequested,
            'actions' => [AutomationActionType::CreateTask->value],
        ]);

        $before = Task::query()->where('application_id', $application->id)->count();
        $joined = app(PlacementService::class)->join($placement, $admin, now()->toDateString());
        $this->assertSame(PlacementStatus::Joined, $joined->status);
        $this->assertGreaterThan($before, Task::query()->where('application_id', $application->id)->count());

        $employee = $joined->employee;
        $this->assertNotNull($employee);

        $replacement = app(ReplacementService::class)->request($employee, $admin, ReplacementReason::EmployerRequested);
        $this->assertTrue(Replacement::query()->where('id', $replacement->id)->exists());
        $this->assertSame(1, RecruitmentAutomationExecution::query()->where('trigger', AutomationTrigger::PlacementJoined->value)->count());
        $this->assertSame(1, RecruitmentAutomationExecution::query()->where('trigger', AutomationTrigger::ReplacementRequested->value)->count());

        app(RecruitmentAutomationDispatcher::class)->dispatch(AutomationTrigger::ReplacementRequested, [
            'replacement' => $replacement->fresh(),
            'application' => $application->fresh(),
            'actor' => $admin,
            'employer_profile_id' => $replacement->employer_profile_id,
            'job_id' => $replacement->job_id,
        ]);
        $this->assertSame(1, RecruitmentAutomationExecution::query()->where('trigger', AutomationTrigger::ReplacementRequested->value)->count());
        $this->assertSame(1, Task::query()->where('title', 'like', 'Follow up: Replacement follow-up%')->count());
    }

    public function test_client_review_and_employer_selected_hooks(): void
    {
        $admin = $this->makeAdmin();
        $employer = $this->makeEmployer();
        $profile = $employer->employerProfile;
        $job = Job::factory()->create(['employer_id' => $profile->id]);
        $application = Application::factory()->create([
            'job_id' => $job->id,
            'status' => ApplicationStatus::Shortlisted,
        ]);
        $this->makeRule($admin, [
            'name' => 'Review pending',
            'trigger' => AutomationTrigger::ClientReviewPending,
            'actions' => [AutomationActionType::CreateTask->value],
        ]);
        $this->makeRule($admin, [
            'name' => 'Selected',
            'trigger' => AutomationTrigger::EmployerSelected,
            'actions' => [AutomationActionType::NotifyAdmin->value, AutomationActionType::CreateTask->value],
        ]);

        $submissions = app(EmployerSubmissionService::class);
        $submission = $submissions->submit($application, $admin);
        $submissions->review($submission, $admin);
        $submissions->select($submission->fresh(), $admin);

        $this->assertSame(EmployerSubmissionStatus::Selected, $submission->fresh()->status);
        $this->assertTrue(RecruitmentAutomationExecution::query()->where('trigger', AutomationTrigger::ClientReviewPending->value)->exists());
        $this->assertTrue(RecruitmentAutomationExecution::query()->where('trigger', AutomationTrigger::EmployerSelected->value)->exists());
        $this->assertSame(ScreeningDecision::Pending, $application->fresh()->screeningDecision());
    }

    public function test_scheduled_joining_replacement_and_client_followup_are_idempotent(): void
    {
        $admin = $this->makeAdmin();
        [$joiningApplication, $placement] = $this->staffThroughJoin($admin, false);

        $followupApplication = Application::factory()->create([
            'job_id' => $placement->job_id,
            'status' => ApplicationStatus::Shortlisted,
        ]);
        EmployerSubmission::query()->create([
            'employer_profile_id' => $placement->employer_profile_id,
            'job_id' => $placement->job_id,
            'application_id' => $followupApplication->id,
            'status' => EmployerSubmissionStatus::Submitted,
            'submitted_by' => $admin->id,
            'submitted_at' => now()->subDays(3),
        ]);

        $this->makeRule($admin, [
            'name' => 'Joining due',
            'trigger' => AutomationTrigger::JoiningDue,
            'actions' => [AutomationActionType::CreateTask->value],
            'conditions' => ['delay_hours' => 48],
        ]);
        $this->makeRule($admin, [
            'name' => 'Replacement ageing',
            'trigger' => AutomationTrigger::ReplacementAging,
            'actions' => [AutomationActionType::CreateTask->value],
            'conditions' => ['delay_hours' => 24],
        ]);
        $this->makeRule($admin, [
            'name' => 'Client follow-up due',
            'trigger' => AutomationTrigger::ClientFollowupDue,
            'actions' => [AutomationActionType::CreateTask->value],
            'conditions' => ['delay_hours' => 24],
        ]);

        $processor = app(RecruitmentAutomationProcessor::class);
        $this->assertGreaterThan(0, $processor->processDue());
        $executions = RecruitmentAutomationExecution::query()->count();
        $tasks = Task::query()->count();
        $processor->processDue();
        $this->assertSame($executions, RecruitmentAutomationExecution::query()->count());
        $this->assertSame($tasks, Task::query()->count());

        $joined = app(PlacementService::class)->join($placement, $admin, now()->toDateString());
        $replacement = app(ReplacementService::class)->request($joined->employee, $admin, ReplacementReason::EmployerRequested);
        $replacement->update(['requested_at' => now()->subDays(3)]);

        $processor->processDue();
        $aging = RecruitmentAutomationExecution::query()->where('trigger', AutomationTrigger::ReplacementAging->value)->count();
        $this->assertSame(1, $aging);
        $processor->processDue();
        $this->assertSame(1, RecruitmentAutomationExecution::query()->where('trigger', AutomationTrigger::ReplacementAging->value)->count());
        $this->assertNotNull($joiningApplication->id);
    }

    /**
     * @return array{0: Application, 1: Placement}
     */
    private function staffThroughJoin(User $admin, bool $join): array
    {
        $employer = $this->makeEmployer();
        $profile = $employer->employerProfile;
        $this->assertNotNull($profile);
        $job = Job::factory()->create(['employer_id' => $profile->id]);
        $application = Application::factory()->create([
            'job_id' => $job->id,
            'user_id' => $this->makeCandidate()->id,
            'status' => ApplicationStatus::Shortlisted,
        ]);

        $submissions = app(EmployerSubmissionService::class);
        $submission = $submissions->submit($application, $admin);
        $submission = $submissions->select($submission, $admin);

        $placements = app(PlacementService::class);
        $placement = $placements->createFromSubmission($submission, $admin);
        $placement = $placements->offer($placement, $admin, now()->toDateString());
        $placement = $placements->accept($placement, $admin);
        $placement = $placements->confirmJoining($placement, $admin, now()->addDay()->toDateString());

        if ($join) {
            $placement = $placements->join($placement, $admin, now()->toDateString());
        }

        return [$application->fresh(), $placement->fresh()];
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeRule(User $admin, array $overrides = []): RecruitmentAutomation
    {
        return app(RecruitmentAutomationService::class)->create([
            'name' => $overrides['name'] ?? 'Recommend screening review',
            'trigger' => $overrides['trigger'] ?? AutomationTrigger::ApplicationSubmitted,
            'scope' => $overrides['scope'] ?? AutomationScope::Global,
            'actions' => $overrides['actions'] ?? [
                AutomationActionType::RecommendScreening->value,
                AutomationActionType::CreateTask->value,
                AutomationActionType::NotifyAdmin->value,
            ],
            'conditions' => $overrides['conditions'] ?? ['allow_insufficient_data' => true],
            'is_enabled' => true,
        ], $admin);
    }
}
