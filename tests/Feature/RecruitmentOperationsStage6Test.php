<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\InterviewLifecycleStatus;
use App\Enums\InterviewMode;
use App\Enums\InterviewOutcome;
use App\Enums\InterviewType;
use App\Enums\PermissionName;
use App\Enums\RecruitmentActivityType;
use App\Enums\ScreeningDecision;
use App\Enums\ScreeningStage;
use App\Enums\TaskStatus;
use App\Models\Application;
use App\Models\ApplicationInterview;
use App\Models\ApplicationScreening;
use App\Models\InterviewScorecard;
use App\Models\Job;
use App\Models\Permission;
use App\Models\RecruitmentActivity;
use App\Models\Role;
use App\Models\ScorecardTemplate;
use App\Models\Task;
use App\Models\User;
use App\Support\AccessCatalog;
use App\Support\AtsStage;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RecruitmentOperationsStage6Test extends TestCase
{
    public function test_application_interviews_is_has_many_and_existing_row_is_interview_one(): void
    {
        $this->assertTrue(Schema::hasColumn('application_interviews', 'sort_order'));
        $this->assertTrue(Schema::hasColumn('application_interviews', 'lifecycle_status'));
        $this->assertTrue(Schema::hasColumn('application_interviews', 'interviewer_user_id'));
        $this->assertTrue(Schema::hasTable('scorecard_templates'));
        $this->assertTrue(Schema::hasTable('interview_scorecards'));
        $this->assertTrue(Schema::hasTable('tasks'));
        $this->assertTrue(Schema::hasColumn('recruitment_activities', 'interview_id'));
        $this->assertTrue(Schema::hasColumn('recruitment_activities', 'task_id'));

        $uniqueApplicationIndexes = collect(Schema::getIndexes('application_interviews'))
            ->filter(fn (array $index) => ($index['unique'] ?? false) && ($index['columns'] ?? []) === ['application_id'])
            ->count();
        $this->assertSame(0, $uniqueApplicationIndexes);

        $admin = $this->makeAdmin();
        $application = Application::factory()->create(['status' => ApplicationStatus::New]);
        ApplicationInterview::query()->create([
            'application_id' => $application->id,
            'sort_order' => 1,
            'scheduled_at' => now()->addDay(),
            'mode' => InterviewMode::Phone,
            'interviewer_name' => 'Alex Recruiter',
            'outcome' => InterviewOutcome::Pending,
            'created_by' => $admin->id,
        ]);

        $existing = $application->interviews()->first();
        $this->assertNotNull($existing);
        $this->assertSame(1, (int) $existing->sort_order);
        $this->assertSame(InterviewLifecycleStatus::Scheduled, $existing->lifecycle_status);

        Mail::fake();

        $this->actingAs($admin)
            ->put(route('admin.applications.interview', $application), $this->interviewPayload([
                'interviewer_name' => 'Second Interviewer',
                'type' => InterviewType::Technical->value,
            ]))
            ->assertRedirect();

        $rows = ApplicationInterview::query()
            ->where('application_id', $application->id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
        $this->assertCount(2, $rows);
        $this->assertSame(1, (int) $rows->first()?->sort_order);
        $this->assertSame('Alex Recruiter', $rows->first()?->interviewer_name);
        $this->assertSame(2, (int) $rows->last()?->sort_order);
        $this->assertSame('Second Interviewer', $rows->last()?->interviewer_name);
        $this->assertSame(ApplicationStatus::Interview, $application->fresh()->status);
        $this->assertTrue(
            RecruitmentActivity::query()
                ->where('application_id', $application->id)
                ->where('type', RecruitmentActivityType::InterviewScheduled)
                ->exists()
        );
    }

    public function test_confirm_reschedule_and_cancel_use_lifecycle_without_new_application_status(): void
    {
        $admin = $this->makeAdmin();
        $application = Application::factory()->create(['status' => ApplicationStatus::Reviewing]);
        Mail::fake();

        $this->actingAs($admin)
            ->put(route('admin.applications.interview', $application), $this->interviewPayload())
            ->assertRedirect();

        $first = $application->interviews()->orderBy('id')->first();
        $this->assertNotNull($first);
        $this->assertSame([
            'New',
            'Reviewing',
            'Shortlisted',
            'Interview',
            'Selected',
            'Placed',
            'Rejected',
            'Withdrawn',
        ], array_map(fn (ApplicationStatus $status) => $status->value, ApplicationStatus::cases()));

        $this->actingAs($admin)
            ->post(route('admin.interviews.confirm', $first))
            ->assertRedirect();
        $this->assertSame(InterviewLifecycleStatus::Confirmed, $first->fresh()->lifecycle_status);
        $this->assertNotNull($first->fresh()->confirmed_at);

        $this->actingAs($admin)
            ->post(route('admin.interviews.reschedule', $first), $this->interviewPayload([
                'scheduled_at' => now()->addDays(3)->format('Y-m-d\TH:i'),
                'interviewer_name' => 'Rescheduled Host',
            ]))
            ->assertRedirect();

        $first->refresh();
        $this->assertSame(InterviewLifecycleStatus::Rescheduled, $first->lifecycle_status);
        $replacement = ApplicationInterview::query()->where('rescheduled_from_id', $first->id)->first();
        $this->assertNotNull($replacement);
        $this->assertSame(InterviewLifecycleStatus::Scheduled, $replacement->lifecycle_status);
        $this->assertSame(2, $application->interviews()->count());
        $this->assertSame(ApplicationStatus::Interview, $application->fresh()->status);

        $this->actingAs($admin)
            ->post(route('admin.interviews.cancel', $replacement), ['cancelled_reason' => 'Candidate unavailable'])
            ->assertRedirect();
        $this->assertSame(InterviewLifecycleStatus::Cancelled, $replacement->fresh()->lifecycle_status);
        $this->assertSame('Candidate unavailable', $replacement->fresh()->cancelled_reason);
        $this->assertSame(ApplicationStatus::Interview, $application->fresh()->status);
        $this->assertTrue(
            RecruitmentActivity::query()
                ->where('application_id', $application->id)
                ->whereIn('type', [
                    RecruitmentActivityType::InterviewConfirmed,
                    RecruitmentActivityType::InterviewRescheduled,
                    RecruitmentActivityType::InterviewCancelled,
                ])
                ->count() >= 3
        );
    }

    public function test_scorecard_never_changes_hiring_or_ats_status(): void
    {
        $admin = $this->makeAdmin();
        $application = Application::factory()->create(['status' => ApplicationStatus::Shortlisted]);
        Mail::fake();

        $this->actingAs($admin)
            ->put(route('admin.applications.interview', $application), $this->interviewPayload())
            ->assertRedirect();

        $interview = $application->interviews()->first();
        $this->assertNotNull($interview);
        $statusBefore = $application->fresh()->status;
        $atsBefore = AtsStage::resolve($application->fresh());

        $template = ScorecardTemplate::query()->create([
            'name' => 'Technical screen',
            'criteria' => [['key' => 'overall', 'label' => 'Overall']],
            'is_active' => true,
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.interviews.scorecard', $interview), [
                'scorecard_template_id' => $template->id,
                'qualitative' => ['overall' => 'Strong communicator'],
                'numeric_score' => 92,
                'notes' => 'Recommend hire',
            ])
            ->assertRedirect();

        $application->refresh();
        $this->assertSame($statusBefore, $application->status);
        $this->assertSame($atsBefore, AtsStage::resolve($application->fresh(['interviews', 'placements', 'submissions', 'screening'])));
        $this->assertNull($application->screening);
        $this->assertSame(0, $application->submissions()->count());
        $this->assertSame(0, $application->placements()->count());
        $this->assertTrue(InterviewScorecard::query()->where('application_interview_id', $interview->id)->exists());
        $this->assertTrue(
            RecruitmentActivity::query()
                ->where('application_id', $application->id)
                ->where('type', RecruitmentActivityType::ScorecardSubmitted)
                ->exists()
        );
    }

    public function test_ats_stage_interview_uses_any_interview_row_and_keeps_four_layers(): void
    {
        $admin = $this->makeAdmin();
        $application = Application::factory()->create(['status' => ApplicationStatus::New]);
        ApplicationInterview::query()->create([
            'application_id' => $application->id,
            'sort_order' => 1,
            'scheduled_at' => now()->addDay(),
            'mode' => InterviewMode::Video,
            'interviewer_name' => 'Host',
            'outcome' => InterviewOutcome::Pending,
            'created_by' => $admin->id,
        ]);

        $this->assertSame(ApplicationStatus::New, $application->fresh()->status);
        $this->assertSame(AtsStage::Interview, AtsStage::resolve($application->fresh()));

        $held = Application::factory()->create(['status' => ApplicationStatus::Reviewing]);
        ApplicationScreening::query()->create([
            'application_id' => $held->id,
            'decision' => ScreeningDecision::Pending,
            'stage' => ScreeningStage::Held,
        ]);
        $this->assertSame(AtsStage::Closed, AtsStage::resolve($held->fresh(['screening'])));
    }

    public function test_tasks_assign_and_job_only_uses_jobs_manage(): void
    {
        $admin = $this->makeAdmin();
        $assignee = $this->makeStaffWithPermissions('operations_desk', [
            PermissionName::ApplicationsView->value,
            PermissionName::ApplicationsManage->value,
        ]);
        $jobsOnly = $this->makeStaffWithPermissions('jobs_desk', [
            PermissionName::JobsView->value,
            PermissionName::JobsManage->value,
        ]);
        $application = Application::factory()->create();
        $job = Job::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.tasks.store'), [
                'title' => 'Call candidate',
                'body' => 'Follow up',
                'assigned_to' => $assignee->id,
                'application_id' => $application->id,
            ])
            ->assertRedirect();

        $task = Task::query()->where('title', 'Call candidate')->first();
        $this->assertNotNull($task);
        $this->assertSame(TaskStatus::Open, $task->status);
        $this->assertSame($assignee->id, $task->assigned_to);

        $this->actingAs($assignee)
            ->post(route('admin.tasks.complete', $task))
            ->assertRedirect();
        $this->assertSame(TaskStatus::Done, $task->fresh()->status);

        $this->actingAs($jobsOnly)
            ->post(route('admin.tasks.store'), [
                'title' => 'Job-only follow-up',
                'job_id' => $job->id,
                'assigned_to' => $jobsOnly->id,
            ])
            ->assertRedirect();

        $this->actingAs($jobsOnly)
            ->post(route('admin.tasks.store'), [
                'title' => 'Should not create',
                'application_id' => $application->id,
            ])
            ->assertSessionHasErrors();

        $this->assertFalse(Task::query()->where('title', 'Should not create')->exists());
        $this->assertTrue(Task::query()->where('title', 'Job-only follow-up')->where('job_id', $job->id)->exists());
    }

    public function test_my_work_interviews_and_tasks_screens_are_current_user_and_admin_only(): void
    {
        $admin = $this->makeAdmin();
        $other = $this->makeStaffWithPermissions('other_ops', [
            PermissionName::ApplicationsView->value,
            PermissionName::ApplicationsManage->value,
        ]);
        $application = Application::factory()->create(['full_name' => 'My Work Candidate']);
        Mail::fake();

        $this->actingAs($admin)
            ->put(route('admin.applications.interview', $application), $this->interviewPayload([
                'interviewer_user_id' => $admin->id,
                'interviewer_name' => $admin->name,
            ]))
            ->assertRedirect();

        $this->actingAs($other)
            ->put(route('admin.applications.interview', $application), $this->interviewPayload([
                'interviewer_user_id' => $other->id,
                'interviewer_name' => $other->name,
                'scheduled_at' => now()->addDays(2)->format('Y-m-d\TH:i'),
            ]))
            ->assertRedirect();

        $this->actingAs($admin)
            ->post(route('admin.tasks.store'), [
                'title' => 'Admin task',
                'assigned_to' => $admin->id,
            ])
            ->assertRedirect();
        $this->actingAs($admin)
            ->post(route('admin.tasks.store'), [
                'title' => 'Other task',
                'assigned_to' => $other->id,
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->get(route('admin.work.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Work/Index')
                ->has('tasks', 1)
                ->where('tasks.0.title', 'Admin task')
                ->where('interviews.0.interviewer_user_id', $admin->id)
                ->missing('tasks.1'));

        $this->actingAs($admin)
            ->get(route('admin.interviews.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Interviews/Index')
                ->has('interviews.data', 2));

        $this->actingAs($admin)
            ->get(route('admin.tasks.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Tasks/Index')
                ->has('tasks.data', 2));

        $candidate = $this->makeCandidate();
        $employer = $this->makeEmployer();

        $this->actingAs($candidate)->get(route('admin.work.index'))->assertForbidden();
        $this->actingAs($candidate)->get(route('admin.interviews.index'))->assertForbidden();
        $this->actingAs($candidate)->get(route('admin.tasks.index'))->assertForbidden();
        $this->actingAs($employer)->get(route('admin.interviews.index'))->assertForbidden();
        $this->actingAs($employer)->post(route('admin.tasks.store'), ['title' => 'Employer task'])->assertForbidden();
        $this->actingAs($employer)->get(route('admin.scorecard-templates.index'))->assertForbidden();
        $this->actingAs($employer)->get(route('admin.candidates.index'))->assertForbidden();
    }

    public function test_reminders_mark_sent_and_permission_catalog_is_unchanged(): void
    {
        $admin = $this->makeAdmin();
        $application = Application::factory()->create();
        $interview = ApplicationInterview::query()->create([
            'application_id' => $application->id,
            'sort_order' => 1,
            'lifecycle_status' => InterviewLifecycleStatus::Scheduled,
            'scheduled_at' => now()->addHours(6),
            'mode' => InterviewMode::Phone,
            'interviewer_name' => $admin->name,
            'interviewer_user_id' => $admin->id,
            'outcome' => InterviewOutcome::Pending,
            'created_by' => $admin->id,
        ]);
        $task = Task::query()->create([
            'title' => 'Due today',
            'status' => TaskStatus::Open,
            'due_at' => now()->addHour(),
            'assigned_to' => $admin->id,
            'created_by' => $admin->id,
        ]);

        Mail::fake();
        $this->assertSame(0, Artisan::call('recruitment:send-reminders'));

        $this->assertNotNull($interview->fresh()->reminder_sent_at);
        $this->assertNotNull($task->fresh()->reminder_sent_at);

        $this->assertSame(0, Artisan::call('recruitment:send-reminders'));
        $this->assertCount(34, PermissionName::cases());
        $this->assertEqualsCanonicalizing(AccessCatalog::permissionNames(), array_map(
            fn (PermissionName $permission) => $permission->value,
            PermissionName::cases(),
        ));
        $this->assertStringNotContainsString('StaffRoleSlug::Recruiter', file_get_contents(app_path('Services/InterviewService.php')));
        $this->assertStringNotContainsString('StaffRoleSlug::Recruiter', file_get_contents(app_path('Services/MyWorkService.php')));
        $this->assertStringNotContainsString('StaffRoleSlug::Recruiter', file_get_contents(app_path('Services/TaskService.php')));
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

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function interviewPayload(array $overrides = []): array
    {
        return [
            'scheduled_at' => now()->addDay()->format('Y-m-d\TH:i'),
            'mode' => InterviewMode::Video->value,
            'location_or_link' => 'https://meet.example.com/stage-6',
            'interviewer_name' => 'Stage Six Host',
            'outcome' => InterviewOutcome::Pending->value,
            'notes' => 'Stage 6 interview',
            ...$overrides,
        ];
    }
}
