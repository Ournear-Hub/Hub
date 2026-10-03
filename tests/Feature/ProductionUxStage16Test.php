<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\BusinessIdPrefix;
use App\Enums\EmployerSubmissionStatus;
use App\Enums\InterviewLifecycleStatus;
use App\Enums\InterviewMode;
use App\Enums\InterviewOutcome;
use App\Enums\PermissionName;
use App\Enums\ScreeningDecision;
use App\Enums\TaskStatus;
use App\Models\Application;
use App\Models\ApplicationInterview;
use App\Models\EmployerSubmission;
use App\Models\Job;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use App\Services\ScorecardService;
use App\Services\ScreeningMatchService;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProductionUxStage16Test extends TestCase
{
    public function test_task_assignment_is_limited_to_active_admin_staff(): void
    {
        $admin = $this->makeAdmin();
        $assignee = $this->makeAdmin(['email' => 'staff-assignee@example.com']);
        $inactive = $this->makeAdmin(['email' => 'inactive-staff@example.com']);
        $inactive->forceFill(['is_active' => false])->save();
        $candidate = $this->makeCandidate();
        $employer = $this->makeEmployer();

        $this->actingAs($admin)
            ->post(route('admin.tasks.store'), [
                'title' => 'Staff follow-up',
                'assigned_to' => $assignee->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('tasks', [
            'title' => 'Staff follow-up',
            'assigned_to' => $assignee->id,
            'created_by' => $admin->id,
            'status' => TaskStatus::Open->value,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.tasks.store'), [
                'title' => 'Unassigned follow-up',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('tasks', [
            'title' => 'Unassigned follow-up',
            'assigned_to' => null,
        ]);

        $this->actingAs($admin)
            ->from(route('admin.tasks.index'))
            ->post(route('admin.tasks.store'), [
                'title' => 'Candidate assignment',
                'assigned_to' => $candidate->id,
            ])
            ->assertRedirect(route('admin.tasks.index'))
            ->assertSessionHasErrors('assigned_to');

        $this->actingAs($admin)
            ->from(route('admin.tasks.index'))
            ->post(route('admin.tasks.store'), [
                'title' => 'Employer assignment',
                'assigned_to' => $employer->id,
            ])
            ->assertRedirect(route('admin.tasks.index'))
            ->assertSessionHasErrors('assigned_to');

        $this->actingAs($admin)
            ->from(route('admin.tasks.index'))
            ->post(route('admin.tasks.store'), [
                'title' => 'Inactive assignment',
                'assigned_to' => $inactive->id,
            ])
            ->assertRedirect(route('admin.tasks.index'))
            ->assertSessionHasErrors('assigned_to');

        $this->assertDatabaseMissing('tasks', ['title' => 'Candidate assignment']);
        $this->assertDatabaseMissing('tasks', ['title' => 'Employer assignment']);
        $this->assertDatabaseMissing('tasks', ['title' => 'Inactive assignment']);
    }

    public function test_task_reassign_rejects_non_staff_and_accepts_staff(): void
    {
        $admin = $this->makeAdmin();
        $otherStaff = $this->makeAdmin(['email' => 'reassign-staff@example.com']);
        $candidate = $this->makeCandidate();

        $this->actingAs($admin)
            ->post(route('admin.tasks.store'), [
                'title' => 'Reassign me',
                'assigned_to' => $admin->id,
            ])
            ->assertRedirect();

        $task = Task::query()->where('title', 'Reassign me')->first();
        $this->assertNotNull($task);

        $this->actingAs($admin)
            ->put(route('admin.tasks.update', $task), [
                'title' => 'Reassign me',
                'assigned_to' => $otherStaff->id,
            ])
            ->assertRedirect();

        $this->assertSame($otherStaff->id, $task->fresh()->assigned_to);

        $this->actingAs($admin)
            ->from(route('admin.tasks.index', ['task' => $task->id]))
            ->put(route('admin.tasks.update', $task), [
                'title' => 'Reassign me',
                'assigned_to' => $candidate->id,
            ])
            ->assertRedirect(route('admin.tasks.index', ['task' => $task->id]))
            ->assertSessionHasErrors('assigned_to');

        $this->assertSame($otherStaff->id, $task->fresh()->assigned_to);
    }

    public function test_my_work_is_current_user_scoped_and_includes_operational_queues(): void
    {
        $admin = $this->makeAdmin();
        $other = $this->makeStaffWithPermissions('stage16_other', [
            PermissionName::ApplicationsView->value,
            PermissionName::ApplicationsManage->value,
        ]);
        $mineApplication = Application::factory()->create(['full_name' => 'Mine Screening']);
        $job = Job::factory()->create(['title' => 'Response Role']);
        $employer = $this->makeEmployer();

        Task::query()->create([
            'title' => 'Mine overdue',
            'status' => TaskStatus::Open,
            'due_at' => now()->subDay(),
            'assigned_to' => $admin->id,
            'created_by' => $admin->id,
        ]);
        Task::query()->create([
            'title' => 'Other overdue',
            'status' => TaskStatus::Open,
            'due_at' => now()->subDay(),
            'assigned_to' => $other->id,
            'created_by' => $other->id,
        ]);
        Task::query()->create([
            'title' => 'Mine completed overdue',
            'status' => TaskStatus::Done,
            'due_at' => now()->subDay(),
            'assigned_to' => $admin->id,
            'created_by' => $admin->id,
            'completed_at' => now(),
        ]);
        Task::query()->create([
            'title' => 'Mine cancelled overdue',
            'status' => TaskStatus::Cancelled,
            'due_at' => now()->subDay(),
            'assigned_to' => $admin->id,
            'created_by' => $admin->id,
        ]);

        ApplicationInterview::query()->create([
            'application_id' => $mineApplication->id,
            'sort_order' => 1,
            'lifecycle_status' => InterviewLifecycleStatus::Scheduled,
            'scheduled_at' => now()->addMinutes(20),
            'mode' => InterviewMode::Phone,
            'interviewer_name' => $admin->name,
            'interviewer_user_id' => $admin->id,
            'outcome' => InterviewOutcome::Pending,
            'created_by' => $admin->id,
        ]);
        ApplicationInterview::query()->create([
            'application_id' => $mineApplication->id,
            'sort_order' => 2,
            'lifecycle_status' => InterviewLifecycleStatus::Scheduled,
            'scheduled_at' => now()->addMinutes(20),
            'mode' => InterviewMode::Phone,
            'interviewer_name' => $other->name,
            'interviewer_user_id' => $other->id,
            'outcome' => InterviewOutcome::Pending,
            'created_by' => $other->id,
        ]);
        ApplicationInterview::query()->create([
            'application_id' => $mineApplication->id,
            'sort_order' => 3,
            'lifecycle_status' => InterviewLifecycleStatus::Cancelled,
            'scheduled_at' => now()->addMinutes(20),
            'mode' => InterviewMode::Phone,
            'interviewer_name' => $admin->name,
            'interviewer_user_id' => $admin->id,
            'outcome' => InterviewOutcome::Pending,
            'created_by' => $admin->id,
        ]);

        EmployerSubmission::query()->create([
            'employer_profile_id' => $employer->employerProfile?->id,
            'job_id' => $job->id,
            'application_id' => $mineApplication->id,
            'status' => EmployerSubmissionStatus::Submitted,
            'submitted_by' => $admin->id,
            'submitted_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.work.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Work/Index')
                ->has('tasks', 1)
                ->where('tasks.0.title', 'Mine overdue')
                ->has('overdue_tasks', 1)
                ->where('overdue_tasks.0.title', 'Mine overdue')
                ->has('todays_interviews', 1)
                ->where('todays_interviews.0.interviewer_user_id', $admin->id)
                ->where('counts.overdue_tasks', 1)
                ->where('counts.todays_interviews', 1)
                ->where('can_view_screening_queue', true)
                ->where('can_view_submission_queue', true)
                ->where('pending_screening.0.full_name', 'Mine Screening')
                ->missing('pending_screening.0.match_percent')
                ->missing('pending_screening.0.internal_notes')
                ->has('pending_employer_responses', 1)
                ->where('pending_employer_responses.0.status', EmployerSubmissionStatus::Submitted->value)
                ->missing('pending_employer_responses.0.notes')
                ->missing('pending_employer_responses.0.internal_notes'));
    }

    public function test_task_overdue_and_today_filters(): void
    {
        $admin = $this->makeAdmin();
        $other = $this->makeAdmin(['email' => 'task-filter-other@example.com']);

        Task::query()->create([
            'title' => 'Overdue open',
            'status' => TaskStatus::Open,
            'due_at' => now()->subDay(),
            'assigned_to' => $admin->id,
            'created_by' => $admin->id,
        ]);
        Task::query()->create([
            'title' => 'Due today',
            'status' => TaskStatus::Open,
            'due_at' => now()->endOfDay(),
            'assigned_to' => $admin->id,
            'created_by' => $admin->id,
        ]);
        Task::query()->create([
            'title' => 'Overdue done',
            'status' => TaskStatus::Done,
            'due_at' => now()->subDay(),
            'assigned_to' => $admin->id,
            'created_by' => $admin->id,
            'completed_at' => now(),
        ]);
        Task::query()->create([
            'title' => 'Other overdue',
            'status' => TaskStatus::Open,
            'due_at' => now()->subDay(),
            'assigned_to' => $other->id,
            'created_by' => $other->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.tasks.index', ['due' => 'overdue']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Tasks/Index')
                ->has('tasks.data', 2)
                ->where('filters.due', 'overdue'));

        $overdueTitles = Task::query()
            ->where('status', TaskStatus::Open)
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->orderByDesc('id')
            ->pluck('title')
            ->all();
        $this->assertEqualsCanonicalizing(['Overdue open', 'Other overdue'], $overdueTitles);

        $this->actingAs($admin)
            ->get(route('admin.tasks.index', ['due' => 'today', 'assigned_to' => $admin->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Tasks/Index')
                ->has('tasks.data', 1)
                ->where('tasks.data.0.title', 'Due today')
                ->where('filters.due', 'today'));
    }

    public function test_interview_mine_today_and_upcoming_filters(): void
    {
        $admin = $this->makeAdmin();
        $other = $this->makeAdmin(['email' => 'interview-other@example.com']);
        $mine = Application::factory()->create(['full_name' => 'Mine Interview']);
        $theirs = Application::factory()->create(['full_name' => 'Their Interview']);

        ApplicationInterview::query()->create([
            'application_id' => $mine->id,
            'sort_order' => 1,
            'lifecycle_status' => InterviewLifecycleStatus::Scheduled,
            'scheduled_at' => now()->addMinutes(20),
            'mode' => InterviewMode::Phone,
            'interviewer_name' => $admin->name,
            'interviewer_user_id' => $admin->id,
            'outcome' => InterviewOutcome::Pending,
            'created_by' => $admin->id,
        ]);
        ApplicationInterview::query()->create([
            'application_id' => $theirs->id,
            'sort_order' => 1,
            'lifecycle_status' => InterviewLifecycleStatus::Scheduled,
            'scheduled_at' => now()->addDays(3),
            'mode' => InterviewMode::Phone,
            'interviewer_name' => $other->name,
            'interviewer_user_id' => $other->id,
            'outcome' => InterviewOutcome::Pending,
            'created_by' => $other->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.interviews.index', ['scope' => 'mine']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Interviews/Index')
                ->has('interviews.data', 1)
                ->where('interviews.data.0.interviewer_user_id', $admin->id)
                ->where('filters.scope', 'mine'));

        $this->actingAs($admin)
            ->get(route('admin.interviews.index', ['scope' => 'today']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Interviews/Index')
                ->has('interviews.data', 1)
                ->where('interviews.data.0.application.full_name', 'Mine Interview')
                ->where('filters.scope', 'today'));

        $this->actingAs($admin)
            ->get(route('admin.interviews.index', ['scope' => 'upcoming']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Interviews/Index')
                ->has('interviews.data', 2)
                ->where('filters.scope', 'upcoming'));
    }

    public function test_pending_screening_and_employer_response_queues_respect_permissions(): void
    {
        $admin = $this->makeAdmin();
        $jobsOnly = $this->makeStaffWithPermissions('stage16_jobs', [
            PermissionName::JobsView->value,
            PermissionName::JobsManage->value,
        ]);
        $application = Application::factory()->create(['full_name' => 'Needs Screening']);
        $job = Job::factory()->create();
        $employer = $this->makeEmployer();
        EmployerSubmission::query()->create([
            'employer_profile_id' => $employer->employerProfile?->id,
            'job_id' => $job->id,
            'application_id' => $application->id,
            'status' => EmployerSubmissionStatus::UnderReview,
            'submitted_by' => $admin->id,
            'submitted_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.screening.index', ['scope' => 'pending']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Screening/Index')
                ->where('filters.scope', 'pending')
                ->where('applications.data.0.full_name', 'Needs Screening')
                ->where('applications.data.0.screening_decision', ScreeningDecision::Pending->value));

        $this->actingAs($jobsOnly)
            ->get(route('admin.work.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Work/Index')
                ->where('can_view_screening_queue', false)
                ->where('can_view_submission_queue', false)
                ->has('pending_screening', 0)
                ->has('pending_employer_responses', 0)
                ->where('counts.pending_screening', 0)
                ->where('counts.pending_employer_responses', 0));

        $this->actingAs($jobsOnly)->get(route('admin.screening.index'))->assertForbidden();
        $this->actingAs($jobsOnly)->get(route('admin.submissions.index'))->assertForbidden();
    }

    public function test_follow_up_tasks_link_application_candidate_and_job(): void
    {
        $admin = $this->makeAdmin();
        $candidate = $this->makeCandidate(['name' => 'Follow Candidate']);
        $job = Job::factory()->create(['title' => 'Follow Job']);
        $application = Application::factory()->create([
            'job_id' => $job->id,
            'user_id' => $candidate->id,
            'full_name' => 'Follow Candidate',
        ]);
        $profileId = $candidate->fresh()->candidateProfile?->id;
        $this->assertNotNull($profileId);

        $this->actingAs($admin)
            ->post(route('admin.tasks.store'), [
                'title' => 'Follow up: Follow Candidate',
                'application_id' => $application->id,
                'job_id' => $job->id,
                'candidate_profile_id' => $profileId,
                'assigned_to' => $admin->id,
            ])
            ->assertRedirect();

        $applicationTask = Task::query()->where('title', 'Follow up: Follow Candidate')->first();
        $this->assertNotNull($applicationTask);
        $this->assertSame($application->id, $applicationTask->application_id);
        $this->assertSame($job->id, $applicationTask->job_id);
        $this->assertSame($profileId, $applicationTask->candidate_profile_id);

        $this->actingAs($admin)
            ->post(route('admin.tasks.store'), [
                'title' => 'Follow up: candidate only',
                'candidate_profile_id' => $profileId,
            ])
            ->assertRedirect();

        $candidateTask = Task::query()->where('title', 'Follow up: candidate only')->first();
        $this->assertNotNull($candidateTask);
        $this->assertSame($profileId, $candidateTask->candidate_profile_id);
        $this->assertNull($candidateTask->application_id);

        $this->actingAs($admin)
            ->post(route('admin.tasks.store'), [
                'title' => 'Follow up: Follow Job',
                'job_id' => $job->id,
            ])
            ->assertRedirect();

        $jobTask = Task::query()->where('title', 'Follow up: Follow Job')->first();
        $this->assertNotNull($jobTask);
        $this->assertSame($job->id, $jobTask->job_id);
        $this->assertNull($jobTask->application_id);
        $this->assertNull($jobTask->candidate_profile_id);
    }

    public function test_employer_and_candidate_remain_denied_internal_workspace(): void
    {
        $employer = $this->makeEmployer([
            'email_verified_at' => now(),
            'mobile_verified_at' => now(),
        ]);
        $candidate = $this->makeCandidate();
        $job = Job::factory()->create(['employer_id' => $employer->employerProfile?->id]);
        $application = Application::factory()->create([
            'job_id' => $job->id,
            'user_id' => $candidate->id,
            'full_name' => $candidate->name,
            'internal_notes' => [[
                'body' => 'Recruiter only note',
                'author_id' => 1,
                'author_name' => 'Admin',
                'created_at' => now()->toDateTimeString(),
            ]],
        ]);

        $this->actingAs($employer)->get(route('admin.work.index'))->assertForbidden();
        $this->actingAs($employer)->get(route('admin.tasks.index'))->assertForbidden();
        $this->actingAs($employer)->post(route('admin.tasks.store'), ['title' => 'Employer task'])->assertForbidden();
        $this->actingAs($employer)->get(route('admin.screening.index'))->assertForbidden();
        $this->actingAs($employer)->get(route('admin.interviews.index'))->assertForbidden();
        $this->actingAs($employer)->get(route('admin.scorecard-templates.index'))->assertForbidden();

        $this->actingAs($employer)
            ->get(route('account.employer.jobs.applicants', $job))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->missing('applications.data.0.match_percent')
                ->missing('applications.data.0.internal_notes')
                ->missing('applications.data.0.screening_notes')
                ->missing('applications.data.0.scorecards')
                ->missing('applications.data.0.assigned_to'));

        $this->actingAs($candidate)->get(route('admin.work.index'))->assertForbidden();
        $this->actingAs($candidate)->get(route('admin.tasks.index'))->assertForbidden();
        $this->actingAs($candidate)->post(route('admin.tasks.store'), ['title' => 'Candidate task'])->assertForbidden();
        $this->actingAs($candidate)->get(route('admin.screening.index'))->assertForbidden();
        $this->actingAs($candidate)->get(route('admin.interviews.index'))->assertForbidden();

        $this->actingAs($candidate)
            ->get(route('account.home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Home')
                ->missing('tasks')
                ->missing('overdue_tasks')
                ->missing('pending_screening')
                ->missing('dashboard.match_percent')
                ->missing('dashboard.internal_notes'));

        $this->assertSame(ApplicationStatus::New, $application->fresh()->status);
    }

    public function test_permission_catalog_ids_and_scorecard_behavior_are_unchanged(): void
    {
        $this->assertCount(34, PermissionName::cases());
        $names = array_map(fn (PermissionName $permission) => $permission->value, PermissionName::cases());
        $this->assertNotContains('tasks.view', $names);
        $this->assertNotContains('tasks.manage', $names);

        $this->assertSame('ONT-U', BusinessIdPrefix::User->value);
        $this->assertSame('ONT-C', BusinessIdPrefix::Candidate->value);
        $this->assertSame('ONT-E', BusinessIdPrefix::Employer->value);
        $this->assertSame('ONT-J', BusinessIdPrefix::Job->value);
        $this->assertSame('ONT-A', BusinessIdPrefix::Application->value);
        $this->assertSame('ONT-S', BusinessIdPrefix::Submission->value);

        $scorecard = file_get_contents(app_path('Services/ScorecardService.php'));
        $this->assertIsString($scorecard);
        $this->assertStringContainsString('never change application, screening, submission, or placement status', $scorecard);
        $this->assertSame(ScorecardService::class, ScorecardService::class);

        $match = file_get_contents(app_path('Services/ScreeningMatchService.php'));
        $this->assertIsString($match);
        $this->assertStringContainsString(ScreeningMatchService::DISCLAIMER, $match);
        $this->assertStringNotContainsString('StaffRoleSlug::Recruiter', file_get_contents(app_path('Services/MyWorkService.php')));
        $this->assertStringNotContainsString('StaffRoleSlug::Recruiter', file_get_contents(app_path('Services/TaskService.php')));
        $this->assertStringNotContainsString('StaffRoleSlug::Recruiter', file_get_contents(app_path('Support/StaffOptions.php')));
    }

    /**
     * @param  list<string>  $permissions
     */
    private function makeStaffWithPermissions(string $slug, array $permissions): User
    {
        $role = Role::query()->create([
            'name' => str_replace('_', ' ', $slug),
            'slug' => $slug,
            'is_locked' => false,
        ]);
        $role->permissions()->sync(
            Permission::query()->whereIn('name', $permissions)->pluck('id')->all()
        );

        $user = User::factory()->create([
            'name' => ucfirst($slug),
            'email' => $slug.'@example.com',
        ]);
        $user->roles()->sync([$role->id]);
        $user->forgetPermissionCache();

        return $user->fresh() ?? $user;
    }
}
