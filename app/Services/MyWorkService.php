<?php

namespace App\Services;

use App\Enums\CrmOpportunityStatus;
use App\Enums\EmployerAttributionStatus;
use App\Enums\InterviewLifecycleStatus;
use App\Enums\InvestorInterestStatus;
use App\Enums\TaskStatus;
use App\Models\Application;
use App\Models\ApplicationInterview;
use App\Models\CrmLead;
use App\Models\CrmOpportunity;
use App\Models\EmployerAttribution;
use App\Models\EmployerSubmission;
use App\Models\InvestorInterest;
use App\Models\Referral;
use App\Models\RecruitmentActivity;
use App\Models\Task;
use App\Models\User;
use App\Support\ApplicationQuery;
use App\Support\EmployerSubmissionQuery;
use App\Support\RecruitmentWorkspace;

class MyWorkService
{
    public function __construct(
        private ApplicationQuery $applications,
        private EmployerSubmissionQuery $submissions,
    ) {}

    /**
     * Current-user work only. Not scoped by staff role slug.
     *
     * @return array<string, mixed>
     */
    public function payload(User $user): array
    {
        $openTaskQuery = Task::query()
            ->with($this->taskRelations())
            ->where('assigned_to', $user->id)
            ->where('status', TaskStatus::Open);

        $tasks = (clone $openTaskQuery)
            ->orderByRaw('due_at is null')
            ->orderBy('due_at')
            ->limit(25)
            ->get()
            ->map(fn (Task $task) => $task->toAdminArray())
            ->values()
            ->all();

        $overdueTaskQuery = (clone $openTaskQuery)
            ->whereNotNull('due_at')
            ->where('due_at', '<', now());

        $overdueTasks = (clone $overdueTaskQuery)
            ->orderBy('due_at')
            ->limit(25)
            ->get()
            ->map(fn (Task $task) => $task->toAdminArray())
            ->values()
            ->all();

        $interviewQuery = ApplicationInterview::query()
            ->with(['application.job:id,business_id,title', 'interviewer:id,name'])
            ->where(function ($query) use ($user) {
                $query->where('created_by', $user->id)
                    ->orWhere('interviewer_user_id', $user->id);
            })
            ->whereIn('lifecycle_status', [
                InterviewLifecycleStatus::Scheduled->value,
                InterviewLifecycleStatus::Confirmed->value,
            ]);

        $interviews = (clone $interviewQuery)
            ->orderBy('scheduled_at')
            ->limit(25)
            ->get()
            ->map(fn (ApplicationInterview $interview) => $interview->toAdminListArray())
            ->values()
            ->all();

        $todaysInterviews = (clone $interviewQuery)
            ->whereDate('scheduled_at', now()->toDateString())
            ->orderBy('scheduled_at')
            ->limit(25)
            ->get()
            ->map(fn (ApplicationInterview $interview) => $interview->toAdminListArray())
            ->values()
            ->all();

        $canViewScreening = $user->can('viewAny', Application::class);
        $pendingScreening = [];
        $pendingScreeningCount = 0;
        if ($canViewScreening) {
            $pendingScreeningQuery = $this->applications->pendingScreeningQuery();
            $pendingScreeningCount = (clone $pendingScreeningQuery)->count();
            $pendingScreening = $pendingScreeningQuery
                ->limit(8)
                ->get()
                ->map(fn (Application $application) => $this->screeningWorkItem($application))
                ->values()
                ->all();
        }

        $canViewSubmissions = $user->can('viewAny', EmployerSubmission::class);
        $pendingEmployerResponses = [];
        $pendingEmployerResponseCount = 0;
        if ($canViewSubmissions) {
            $pendingSubmissionQuery = $this->submissions->pendingResponseQuery();
            $pendingEmployerResponseCount = (clone $pendingSubmissionQuery)->count();
            $pendingEmployerResponses = $pendingSubmissionQuery
                ->limit(8)
                ->get()
                ->map(fn (EmployerSubmission $submission) => $this->submissionWorkItem($submission))
                ->values()
                ->all();
        }

        $activity = RecruitmentActivity::query()
            ->with(['application:id,business_id,full_name', 'actor:id,name'])
            ->where('actor_id', $user->id)
            ->latest('id')
            ->limit(25)
            ->get()
            ->map(fn (RecruitmentActivity $item) => array_merge($item->toAdminArray(), [
                'application' => $item->application ? [
                    'id' => $item->application->id,
                    'business_id' => $item->application->business_id,
                    'full_name' => $item->application->full_name,
                    'workspace_url' => route('admin.applications.index', ['application' => $item->application->id], false),
                ] : null,
            ]))
            ->values()
            ->all();

        $leads = CrmLead::query()
            ->with(['assignee:id,name', 'opportunities'])
            ->where('assigned_to', $user->id)
            ->latest('id')
            ->limit(25)
            ->get()
            ->map(fn (CrmLead $lead) => $lead->toAdminArray())
            ->values()
            ->all();

        $openStatuses = array_map(
            fn (CrmOpportunityStatus $status) => $status->value,
            array_filter(CrmOpportunityStatus::cases(), fn (CrmOpportunityStatus $status) => $status->isOpen()),
        );

        $opportunities = CrmOpportunity::query()
            ->with(['lead', 'assignee:id,name', 'employer:id,business_id,company_name'])
            ->where('assigned_to', $user->id)
            ->whereIn('status', $openStatuses)
            ->latest('id')
            ->limit(25)
            ->get()
            ->map(fn (CrmOpportunity $opportunity) => $opportunity->toAdminArray())
            ->values()
            ->all();

        $attributions = EmployerAttribution::query()
            ->with(['partner:id,display_name', 'employer:id,company_name'])
            ->where('created_by', $user->id)
            ->where('status', EmployerAttributionStatus::Pending->value)
            ->latest('id')
            ->limit(25)
            ->get()
            ->map(fn (EmployerAttribution $attribution) => $attribution->toAdminArray())
            ->values()
            ->all();

        $interests = InvestorInterest::query()
            ->with(['investor:id,display_name', 'opportunity:id,title'])
            ->where('created_by', $user->id)
            ->whereIn('status', [
                InvestorInterestStatus::Interested->value,
                InvestorInterestStatus::Reviewing->value,
                InvestorInterestStatus::Discussing->value,
                InvestorInterestStatus::Diligence->value,
            ])
            ->latest('id')
            ->limit(25)
            ->get()
            ->map(fn (InvestorInterest $interest) => $interest->toAdminArray())
            ->values()
            ->all();

        return [
            'tasks' => $tasks,
            'overdue_tasks' => $overdueTasks,
            'interviews' => $interviews,
            'todays_interviews' => $todaysInterviews,
            'pending_screening' => $pendingScreening,
            'pending_employer_responses' => $pendingEmployerResponses,
            'activity' => $activity,
            'crm_leads' => $leads,
            'crm_opportunities' => $opportunities,
            'pending_attributions' => $attributions,
            'pending_interests' => $interests,
            'recent_referrals' => Referral::query()
                ->with(['referralLink:id,code', 'referredUser:id,name', 'application:id,full_name'])
                ->where('created_by', $user->id)
                ->latest('id')
                ->limit(25)
                ->get()
                ->map(fn (Referral $referral) => $referral->toAdminArray())
                ->values()
                ->all(),
            'can_view_screening_queue' => $canViewScreening,
            'can_view_submission_queue' => $canViewSubmissions,
            'counts' => [
                'overdue_tasks' => (clone $overdueTaskQuery)->count(),
                'open_tasks' => (clone $openTaskQuery)->count(),
                'todays_interviews' => (clone $interviewQuery)->whereDate('scheduled_at', now()->toDateString())->count(),
                'pending_screening' => $pendingScreeningCount,
                'pending_employer_responses' => $pendingEmployerResponseCount,
            ],
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private function taskRelations(): array
    {
        return [
            'application:id,business_id,full_name',
            'job:id,business_id,title',
            'candidate.user:id,name',
            'assignee:id,name',
            'employer:id,business_id,company_name',
            'crmLead:id,company_name',
            'crmOpportunity:id,title',
            'partner:id,display_name',
            'investor:id,display_name',
            'investmentOpportunity:id,title',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function screeningWorkItem(Application $application): array
    {
        $profile = $application->user?->candidateProfile;

        return [
            'id' => $application->id,
            'business_id' => $application->business_id,
            'full_name' => $application->full_name,
            'status' => $application->status->value,
            'applied_at' => $application->created_at?->toDateTimeString(),
            'candidate' => [
                'id' => $profile?->id,
                'name' => $application->full_name,
            ],
            'job' => $application->job ? [
                'id' => $application->job->id,
                'title' => $application->job->title,
            ] : null,
            'workspace_url' => route('admin.screening.index', ['application' => $application->id], false),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function submissionWorkItem(EmployerSubmission $submission): array
    {
        $application = $submission->application;
        $profile = $application?->user?->candidateProfile;

        return [
            'id' => $submission->id,
            'business_id' => $submission->business_id,
            'status' => $submission->status->value,
            'response_status' => RecruitmentWorkspace::responseLabel($submission->status),
            'submitted_at' => $submission->submitted_at?->toDateTimeString(),
            'candidate' => [
                'id' => $profile?->id,
                'name' => $application?->full_name,
            ],
            'job' => $submission->job ? [
                'id' => $submission->job->id,
                'title' => $submission->job->title,
            ] : null,
            'employer' => $submission->employer ? [
                'id' => $submission->employer->id,
                'company_name' => $submission->employer->company_name,
            ] : null,
            'workspace_url' => RecruitmentWorkspace::submissionUrl($submission),
        ];
    }
}
