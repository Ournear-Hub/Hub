<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Enums\AutomationExecutionStatus;
use App\Enums\EmployerSubmissionStatus;
use App\Enums\InterviewLifecycleStatus;
use App\Enums\InterviewOutcome;
use App\Enums\InvoiceStatus;
use App\Enums\JobPublishingStatus;
use App\Enums\PermissionName;
use App\Enums\PlacementStatus;
use App\Enums\ReplacementStatus;
use App\Enums\TaskStatus;
use App\Models\Application;
use App\Models\ApplicationInterview;
use App\Models\EmployerSubmission;
use App\Models\Invoice;
use App\Models\Job;
use App\Models\Placement;
use App\Models\RecruitmentAutomationExecution;
use App\Models\Replacement;
use App\Models\Task;
use App\Models\User;
use App\Support\ApplicationQuery;
use App\Support\EmployerSubmissionQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

class RecruiterHiringCommandCenterService
{
    public function __construct(
        private ApplicationQuery $applications,
        private EmployerSubmissionQuery $submissions,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function payload(User $user, string $line = 'all', ?int $jobId = null): array
    {
        $permissions = $this->permissions($user);
        $show = fn (string $section) => $line === 'all' || $line === $section;

        return [
            'generated_at' => now()->toIso8601String(),
            'filters' => [
                'line' => $line,
                'job_id' => $jobId ? (string) $jobId : '',
            ],
            'job_options' => $permissions['jobs'] ? $this->jobOptions() : [],
            'permissions' => $permissions,
            'ownership_note' => 'Application and job records have no recruiter-owner column. Workload uses existing task assignment (assigned_to) for the signed-in staff user only.',
            'ats' => $permissions['ats'] && $show('ats') ? $this->ats($user, $jobId) : null,
            'partnership' => $permissions['partnership'] && $show('partnership') ? $this->partnership($jobId) : null,
            'staffing' => $permissions['staffing'] && $show('staffing') ? $this->staffing($user, $jobId) : null,
            'jobs_board' => $permissions['jobs'] && $show('jobs') ? $this->jobsBoard($user, $jobId) : null,
            'attention' => $this->attention($user, $permissions, $line, $jobId),
            'activity' => $permissions['ats'] && $show('ats') ? $this->recentActivity($jobId) : [],
            'automation' => $permissions['automation'] ? $this->recentAutomation($jobId) : [],
            'workload' => $this->workload($user),
            'links' => [
                'jobs' => '/admin/jobs',
                'applications' => '/admin/applications',
                'screening' => '/admin/screening',
                'shortlisted' => '/admin/shortlisted',
                'interviews' => '/admin/interviews',
                'submissions' => '/admin/submissions',
                'placements' => '/admin/placements',
                'employees' => '/admin/employees',
                'replacements' => '/admin/replacements',
                'finance' => '/admin/finance',
                'work' => '/admin/work',
                'tasks' => '/admin/tasks',
                'automations' => '/admin/automations',
            ],
        ];
    }

    /**
     * @return array{
     *     ats: bool,
     *     partnership: bool,
     *     staffing: bool,
     *     jobs: bool,
     *     finance: bool,
     *     automation: bool
     * }
     */
    public function permissions(User $user): array
    {
        $ats = $user->hasPermission(PermissionName::ApplicationsView->value)
            || $user->hasPermission(PermissionName::ApplicationsManage->value);
        $jobs = $user->hasPermission(PermissionName::JobsView->value)
            || $user->hasPermission(PermissionName::JobsManage->value);

        return [
            'ats' => $ats,
            'partnership' => $user->hasPermission(PermissionName::SubmissionsView->value)
                || $user->hasPermission(PermissionName::SubmissionsManage->value),
            'staffing' => $user->can('viewAny', Placement::class)
                || $user->can('viewAny', \App\Models\Employee::class)
                || $user->can('viewAny', Replacement::class),
            'jobs' => $jobs || $ats,
            'finance' => $user->can('viewAny', Invoice::class),
            'automation' => $user->hasPermission(PermissionName::AutomationView->value)
                || $user->hasPermission(PermissionName::AutomationManage->value),
        ];
    }

    /**
     * @return list<array{id: int, title: string}>
     */
    private function jobOptions(): array
    {
        if (! Schema::hasTable('job_listings')) {
            return [];
        }

        return Job::query()
            ->orderBy('title')
            ->limit(80)
            ->get(['id', 'title'])
            ->map(fn (Job $job) => ['id' => $job->id, 'title' => $job->title])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function ats(User $user, ?int $jobId): array
    {
        $canJobs = $user->hasPermission(PermissionName::JobsView->value)
            || $user->hasPermission(PermissionName::JobsManage->value);

        return [
            'metrics' => [
                $this->card('Active requisitions', $canJobs ? $this->countJobs($jobId, publishedOnly: false, activeOnly: true) : null, '/admin/jobs?active=1', 'No active jobs'),
                $this->card('Needs screening', $this->countPendingScreening($jobId), '/admin/screening', 'No applications awaiting screening'),
                $this->card('Shortlisted', $this->countStatus($jobId, ApplicationStatus::Shortlisted), '/admin/shortlisted', 'No shortlisted candidates'),
                $this->card('Upcoming interviews', $this->countUpcomingInterviews($jobId), '/admin/interviews', 'No upcoming interviews'),
                $this->card('Selected', $this->countStatus($jobId, ApplicationStatus::Selected), '/admin/applications?status='.urlencode(ApplicationStatus::Selected->value), 'No selected candidates'),
            ],
            'queue' => [
                'screening' => $this->screeningItems($jobId),
                'interviews' => $this->interviewItems($jobId),
                'scorecards' => $this->scorecardItems($jobId),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function partnership(?int $jobId): array
    {
        return [
            'metrics' => [
                $this->card('Awaiting client review', $this->countPendingSubmissions($jobId), '/admin/submissions', 'No candidate submissions awaiting client review'),
                $this->card('Selected by client', $this->countSubmissions($jobId, EmployerSubmissionStatus::Selected), '/admin/submissions?status='.urlencode(EmployerSubmissionStatus::Selected->value), 'No client selections'),
                $this->card('Jobs with submissions', $this->countJobsWithSubmissions($jobId), '/admin/submissions', 'No partnership submissions on jobs'),
            ],
            'queue' => $this->submissionItems($jobId),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function staffing(User $user, ?int $jobId): array
    {
        $canPlacements = $user->can('viewAny', Placement::class);
        $canEmployees = $user->can('viewAny', \App\Models\Employee::class);
        $canReplacements = $user->can('viewAny', Replacement::class);
        $canFinance = $user->can('viewAny', Invoice::class);

        $metrics = [];
        if ($canPlacements) {
            $metrics[] = $this->card('Open / offered placements', $this->countOpenPlacements($jobId), '/admin/placements', 'No placements needing follow-up');
            $metrics[] = $this->card('Joining pending', $this->countJoiningPlacements($jobId), '/admin/placements', 'No joining dates pending');
        }
        if ($canEmployees) {
            $metrics[] = $this->card('Employees on record', $this->countIf('employees', $jobId, 'job_id'), '/admin/employees', 'No employee records');
        }
        if ($canReplacements) {
            $metrics[] = $this->card('Open replacements', $this->countOpenReplacements($jobId), '/admin/replacements', 'No open replacement cases');
        }
        if ($canFinance) {
            $metrics[] = $this->card('Open invoices', $this->countOpenInvoices($jobId), '/admin/finance', 'No invoices awaiting payment');
        }

        return [
            'metrics' => $metrics,
            'queue' => [
                'placements' => $canPlacements ? $this->placementItems($jobId) : [],
                'replacements' => $canReplacements ? $this->replacementItems($jobId) : [],
                'invoices' => $canFinance ? $this->invoiceItems($jobId) : [],
            ],
            'assignment_end_note' => 'Placement records have expected/actual joining dates. There is no assignment-end field; renewal-by-end-date is not shown.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function jobsBoard(User $user, ?int $jobId): array
    {
        $canApps = $user->hasPermission(PermissionName::ApplicationsView->value)
            || $user->hasPermission(PermissionName::ApplicationsManage->value);
        $canJobs = $user->hasPermission(PermissionName::JobsView->value)
            || $user->hasPermission(PermissionName::JobsManage->value);

        return [
            'metrics' => [
                $this->card('Published public jobs', $canJobs ? $this->countJobs($jobId, publishedOnly: true, activeOnly: true) : null, '/admin/jobs', 'No published public jobs'),
                $this->card('Guest applications', $canApps ? $this->countApplicationsByOrigin($jobId, guest: true) : null, '/admin/applications', 'No guest applications'),
                $this->card('Account applications', $canApps ? $this->countApplicationsByOrigin($jobId, guest: false) : null, '/admin/applications', 'No account applications'),
            ],
            'recent' => $canApps ? $this->recentJobApplications($jobId) : [],
        ];
    }

    /**
     * @param  array{ats: bool, partnership: bool, staffing: bool, jobs: bool, finance: bool, automation: bool}  $permissions
     * @return list<array<string, mixed>>
     */
    private function attention(User $user, array $permissions, string $line, ?int $jobId): array
    {
        $items = [];
        $include = fn (string $section) => $line === 'all' || $line === $section;

        if ($permissions['ats'] && $include('ats')) {
            $items[] = $this->attentionGroup('screening', 'Screening decisions', '/admin/screening', 'No applications awaiting screening', $this->screeningItems($jobId));
            $items[] = $this->attentionGroup('interviews', 'Upcoming interviews', '/admin/interviews', 'No upcoming interviews', $this->interviewItems($jobId));
            $items[] = $this->attentionGroup('scorecards', 'Interviews needing scorecards', '/admin/interviews', 'No completed interviews missing scorecards', $this->scorecardItems($jobId));
        }
        if ($permissions['partnership'] && $include('partnership')) {
            $items[] = $this->attentionGroup('client_reviews', 'Client reviews pending', '/admin/submissions', 'No candidate submissions awaiting client review', $this->submissionItems($jobId));
        }
        if ($permissions['staffing'] && $include('staffing')) {
            if ($user->can('viewAny', Placement::class)) {
                $items[] = $this->attentionGroup('placements', 'Placement follow-up', '/admin/placements', 'No placements requiring follow-up', $this->placementItems($jobId));
            }
            if ($user->can('viewAny', Replacement::class)) {
                $items[] = $this->attentionGroup('replacements', 'Replacement cases', '/admin/replacements', 'No open replacement cases', $this->replacementItems($jobId));
            }
            if ($permissions['finance'] && $user->can('viewAny', Invoice::class)) {
                $items[] = $this->attentionGroup('invoices', 'Invoice follow-up', '/admin/finance', 'No open invoices', $this->invoiceItems($jobId));
            }
        }

        $taskItems = $this->overdueTaskItems($user);
        if ($taskItems !== null && ($line === 'all' || $line === 'ats')) {
            $items[] = $this->attentionGroup('tasks', 'Your overdue tasks', '/admin/work', 'No overdue tasks assigned to you', $taskItems);
        }

        return $items;
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return array<string, mixed>
     */
    private function attentionGroup(string $key, string $label, string $href, string $empty, array $items): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'href' => $href,
            'count' => count($items) >= 8 ? 8 : count($items),
            'empty' => $empty,
            'items' => $items,
        ];
    }

    /**
     * @return array{label: string, value: int|null, href: string|null, empty: string}
     */
    private function card(string $label, ?int $value, string $href, string $empty): array
    {
        return [
            'label' => $label,
            'value' => $value,
            'href' => $value === null ? null : $href,
            'empty' => $empty,
        ];
    }

    private function countPendingScreening(?int $jobId): ?int
    {
        if (! Schema::hasTable('applications')) {
            return null;
        }

        $query = $this->applications->pendingScreeningQuery();
        $this->constrainJob($query, $jobId);

        return $query->count();
    }

    private function countStatus(?int $jobId, ApplicationStatus $status): ?int
    {
        if (! Schema::hasTable('applications')) {
            return null;
        }

        $query = Application::query()->where('status', $status->value);
        $this->constrainJob($query, $jobId);

        return $query->count();
    }

    private function countUpcomingInterviews(?int $jobId): ?int
    {
        if (! Schema::hasTable('application_interviews')) {
            return null;
        }

        $query = ApplicationInterview::query()->where('scheduled_at', '>=', now());
        $this->constrainOpenInterviews($query);
        if ($jobId !== null) {
            $query->whereHas('application', fn (Builder $application) => $application->where('job_id', $jobId));
        }

        return $query->count();
    }

    private function countPendingSubmissions(?int $jobId): ?int
    {
        if (! Schema::hasTable('employer_submissions')) {
            return null;
        }

        $query = $this->submissions->pendingResponseQuery();
        $this->constrainJob($query, $jobId);

        return $query->count();
    }

    private function countSubmissions(?int $jobId, EmployerSubmissionStatus $status): ?int
    {
        if (! Schema::hasTable('employer_submissions')) {
            return null;
        }

        $query = EmployerSubmission::query()->where('status', $status->value);
        $this->constrainJob($query, $jobId);

        return $query->count();
    }

    private function countJobsWithSubmissions(?int $jobId): ?int
    {
        if (! Schema::hasTable('employer_submissions')) {
            return null;
        }

        $query = EmployerSubmission::query()->whereNotNull('job_id');
        $this->constrainJob($query, $jobId);

        return (int) $query->distinct()->count('job_id');
    }

    private function countJobs(?int $jobId, bool $publishedOnly, bool $activeOnly): ?int
    {
        if (! Schema::hasTable('job_listings')) {
            return null;
        }

        $query = Job::query();
        if ($activeOnly) {
            $query->where('active', true);
        }
        if ($publishedOnly && Schema::hasColumn('job_listings', 'publishing_status')) {
            $query->where('publishing_status', JobPublishingStatus::Published);
        } elseif ($publishedOnly) {
            $query->where('active', true);
        }
        $this->constrainJob($query, $jobId, 'id');

        return $query->count();
    }

    private function countApplicationsByOrigin(?int $jobId, bool $guest): ?int
    {
        if (! Schema::hasTable('applications')) {
            return null;
        }

        $query = Application::query();
        if ($guest) {
            $query->whereNull('user_id');
        } else {
            $query->whereNotNull('user_id');
        }
        $this->constrainJob($query, $jobId);

        return $query->count();
    }

    private function countOpenPlacements(?int $jobId): ?int
    {
        if (! Schema::hasTable('placements')) {
            return null;
        }

        $query = Placement::query()->whereIn('status', [
            PlacementStatus::Open->value,
            PlacementStatus::Offered->value,
            PlacementStatus::Accepted->value,
            PlacementStatus::JoiningConfirmed->value,
        ]);
        $this->constrainJob($query, $jobId);

        return $query->count();
    }

    private function countJoiningPlacements(?int $jobId): ?int
    {
        if (! Schema::hasTable('placements')) {
            return null;
        }

        $query = Placement::query()->whereIn('status', [
            PlacementStatus::Accepted->value,
            PlacementStatus::JoiningConfirmed->value,
        ]);
        $this->constrainJob($query, $jobId);

        return $query->count();
    }

    private function countOpenReplacements(?int $jobId): ?int
    {
        if (! Schema::hasTable('replacements')) {
            return null;
        }

        $query = Replacement::query()->whereIn('status', [
            ReplacementStatus::Requested->value,
            ReplacementStatus::InProgress->value,
            ReplacementStatus::Placed->value,
        ]);
        $this->constrainJob($query, $jobId);

        return $query->count();
    }

    private function countOpenInvoices(?int $jobId): ?int
    {
        if (! Schema::hasTable('invoices')) {
            return null;
        }

        $query = Invoice::query()->whereIn('status', [
            InvoiceStatus::Issued->value,
            InvoiceStatus::PartiallyPaid->value,
        ]);
        $this->constrainJob($query, $jobId);

        return $query->count();
    }

    private function countIf(string $table, ?int $jobId, string $column): ?int
    {
        if (! Schema::hasTable($table)) {
            return null;
        }

        $query = \Illuminate\Support\Facades\DB::table($table);
        if ($jobId !== null && Schema::hasColumn($table, $column)) {
            $query->where($column, $jobId);
        }

        return (int) $query->count();
    }

    /**
     * @return list<array{title: string, subtitle: string|null, href: string}>
     */
    private function screeningItems(?int $jobId): array
    {
        $query = $this->applications->pendingScreeningQuery();
        $this->constrainJob($query, $jobId);

        return $query->limit(8)->get()->map(fn (Application $application) => [
            'title' => $application->full_name,
            'subtitle' => $application->job?->title,
            'href' => '/admin/screening?application='.$application->id,
        ])->all();
    }

    /**
     * @return list<array{title: string, subtitle: string|null, href: string}>
     */
    private function interviewItems(?int $jobId): array
    {
        if (! Schema::hasTable('application_interviews')) {
            return [];
        }

        $query = ApplicationInterview::query()
            ->with(['application.job:id,title'])
            ->where('scheduled_at', '>=', now())
            ->orderBy('scheduled_at');
        $this->constrainOpenInterviews($query);
        if ($jobId !== null) {
            $query->whereHas('application', fn (Builder $application) => $application->where('job_id', $jobId));
        }

        return $query->limit(8)->get()->map(fn (ApplicationInterview $interview) => [
            'title' => $interview->application?->full_name ?? 'Interview',
            'subtitle' => $interview->application?->job?->title,
            'href' => '/admin/interviews?interview='.$interview->id,
        ])->all();
    }

    /**
     * @return list<array{title: string, subtitle: string|null, href: string}>
     */
    private function scorecardItems(?int $jobId): array
    {
        if (! Schema::hasTable('application_interviews') || ! Schema::hasTable('interview_scorecards')) {
            return [];
        }

        $query = ApplicationInterview::query()
            ->with(['application.job:id,title'])
            ->whereDoesntHave('scorecard')
            ->latest('id');
        if (Schema::hasColumn('application_interviews', 'lifecycle_status')) {
            $query->where('lifecycle_status', InterviewLifecycleStatus::Completed->value);
        } else {
            $query->where('outcome', '!=', InterviewOutcome::Pending->value);
        }
        if ($jobId !== null) {
            $query->whereHas('application', fn (Builder $application) => $application->where('job_id', $jobId));
        }

        return $query->limit(8)->get()->map(fn (ApplicationInterview $interview) => [
            'title' => $interview->application?->full_name ?? 'Interview',
            'subtitle' => 'Scorecard not submitted',
            'href' => '/admin/interviews?interview='.$interview->id,
        ])->all();
    }

    /**
     * @return list<array{title: string, subtitle: string|null, href: string}>
     */
    private function submissionItems(?int $jobId): array
    {
        $query = $this->submissions->pendingResponseQuery();
        $this->constrainJob($query, $jobId);

        return $query->limit(8)->get()->map(fn (EmployerSubmission $submission) => [
            'title' => $submission->application?->full_name ?? 'Submission',
            'subtitle' => $submission->employer?->company_name,
            'href' => '/admin/submissions?submission='.$submission->id,
        ])->all();
    }

    /**
     * @return list<array{title: string, subtitle: string|null, href: string}>
     */
    private function placementItems(?int $jobId): array
    {
        if (! Schema::hasTable('placements')) {
            return [];
        }

        $query = Placement::query()
            ->with(['application:id,full_name', 'job:id,title'])
            ->whereIn('status', [
                PlacementStatus::Open->value,
                PlacementStatus::Offered->value,
                PlacementStatus::Accepted->value,
                PlacementStatus::JoiningConfirmed->value,
            ])
            ->latest('id');
        $this->constrainJob($query, $jobId);

        return $query->limit(8)->get()->map(fn (Placement $placement) => [
            'title' => $placement->application?->full_name ?? 'Placement',
            'subtitle' => ($placement->job?->title ?? '').' · '.$placement->status->value,
            'href' => '/admin/placements?placement='.$placement->id,
        ])->all();
    }

    /**
     * @return list<array{title: string, subtitle: string|null, href: string}>
     */
    private function replacementItems(?int $jobId): array
    {
        if (! Schema::hasTable('replacements')) {
            return [];
        }

        $query = Replacement::query()
            ->with(['job:id,title'])
            ->whereIn('status', [
                ReplacementStatus::Requested->value,
                ReplacementStatus::InProgress->value,
                ReplacementStatus::Placed->value,
            ])
            ->latest('id');
        $this->constrainJob($query, $jobId);

        return $query->limit(8)->get()->map(fn (Replacement $replacement) => [
            'title' => $replacement->business_id ?? 'Replacement',
            'subtitle' => ($replacement->job?->title ?? '').' · '.$replacement->status->value,
            'href' => '/admin/replacements?replacement='.$replacement->id,
        ])->all();
    }

    /**
     * @return list<array{title: string, subtitle: string|null, href: string}>
     */
    private function invoiceItems(?int $jobId): array
    {
        if (! Schema::hasTable('invoices')) {
            return [];
        }

        $query = Invoice::query()
            ->with(['employer:id,company_name'])
            ->whereIn('status', [
                InvoiceStatus::Issued->value,
                InvoiceStatus::PartiallyPaid->value,
            ])
            ->latest('id');
        $this->constrainJob($query, $jobId);

        return $query->limit(8)->get()->map(fn (Invoice $invoice) => [
            'title' => $invoice->business_id ?? 'Invoice',
            'subtitle' => $invoice->employer?->company_name,
            'href' => '/admin/finance?invoice='.$invoice->id,
        ])->all();
    }

    /**
     * @return list<array{title: string, subtitle: string|null, href: string}>|null
     */
    private function overdueTaskItems(User $user): ?array
    {
        if (! Schema::hasTable('tasks') || ! $user->can('viewAny', Task::class)) {
            return null;
        }

        return Task::query()
            ->where('assigned_to', $user->id)
            ->where('status', TaskStatus::Open)
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->orderBy('due_at')
            ->limit(8)
            ->get()
            ->map(fn (Task $task) => [
                'title' => $task->title,
                'subtitle' => $task->due_at?->toDateTimeString(),
                'href' => '/admin/tasks?task='.$task->id,
            ])
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function recentJobApplications(?int $jobId): array
    {
        $query = Application::query()
            ->with('job:id,title')
            ->latest('id');
        $this->constrainJob($query, $jobId);

        return $query->limit(8)->get()->map(fn (Application $application) => [
            'title' => $application->full_name,
            'subtitle' => ($application->job?->title ?? '').' · '.($application->user_id === null ? 'Guest' : 'Account'),
            'href' => '/admin/applications?application='.$application->id,
            'origin' => $application->user_id === null ? 'guest' : 'account',
        ])->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function recentActivity(?int $jobId): array
    {
        if (! Schema::hasTable('recruitment_activities')) {
            return [];
        }

        $query = \App\Models\RecruitmentActivity::query()
            ->with(['application:id,full_name,job_id', 'actor:id,name'])
            ->latest('id');
        if ($jobId !== null) {
            $query->whereHas('application', fn (Builder $application) => $application->where('job_id', $jobId));
        }

        return $query->limit(8)->get()->map(function ($item) {
            $type = $item->type instanceof \BackedEnum ? $item->type->value : (string) $item->type;

            return [
                'title' => filled($item->body) ? (string) $item->body : $type,
                'subtitle' => $item->actor?->name,
                'href' => $item->application_id ? '/admin/applications?application='.$item->application_id : '/admin/applications',
            ];
        })->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function recentAutomation(?int $jobId): array
    {
        if (! Schema::hasTable('recruitment_automation_executions')) {
            return [];
        }

        $query = RecruitmentAutomationExecution::query()
            ->with('automation:id,name')
            ->where('status', AutomationExecutionStatus::Executed->value)
            ->latest('id');
        $this->constrainJob($query, $jobId);

        return $query->limit(8)->get()->map(fn (RecruitmentAutomationExecution $execution) => [
            'title' => $execution->automation?->name ?? 'Automation',
            'subtitle' => $execution->match_percent !== null ? $execution->match_percent.'% match (advisory)' : 'Human decision remains required',
            'href' => '/admin/automations?automation='.$execution->recruitment_automation_id,
        ])->all();
    }

    /**
     * @return array{open_tasks: int|null, overdue_tasks: int|null}
     */
    private function workload(User $user): array
    {
        if (! Schema::hasTable('tasks') || ! $user->can('viewAny', Task::class)) {
            return ['open_tasks' => null, 'overdue_tasks' => null];
        }

        $open = Task::query()
            ->where('assigned_to', $user->id)
            ->where('status', TaskStatus::Open);

        return [
            'open_tasks' => (clone $open)->count(),
            'overdue_tasks' => (clone $open)->whereNotNull('due_at')->where('due_at', '<', now())->count(),
        ];
    }

    private function constrainJob(Builder $query, ?int $jobId, string $column = 'job_id'): void
    {
        if ($jobId !== null) {
            $query->where($column, $jobId);
        }
    }

    /**
     * @param  Builder<ApplicationInterview>  $query
     */
    private function constrainOpenInterviews(Builder $query): void
    {
        if (Schema::hasColumn('application_interviews', 'lifecycle_status')) {
            $query->whereIn('lifecycle_status', [
                InterviewLifecycleStatus::Scheduled->value,
                InterviewLifecycleStatus::Confirmed->value,
            ]);

            return;
        }

        $query->where('outcome', InterviewOutcome::Pending->value);
    }
}
