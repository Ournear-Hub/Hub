<?php

namespace App\Services;

use App\Enums\RecruitmentActivityType;
use App\Enums\TaskStatus;
use App\Enums\PermissionName;
use App\Models\Application;
use App\Models\CandidateProfile;
use App\Models\ChannelPartner;
use App\Models\CommissionEntry;
use App\Models\CrmLead;
use App\Models\CrmOpportunity;
use App\Models\EmployerEnquiry;
use App\Models\EmployerProfile;
use App\Models\InvestmentOpportunity;
use App\Models\Investor;
use App\Models\InvestorInterest;
use App\Models\Campaign;
use App\Models\Referral;
use App\Models\ReferralLink;
use App\Models\Job;
use App\Models\Task;
use App\Models\User;
use App\Support\StaffOptions;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TaskService
{
    public function __construct(
        private RecruitmentActivityService $activities,
        private AdminAlertService $alerts,
    ) {}

    /**
     * @param  array{
     *     title: string,
     *     body?: string|null,
     *     due_at?: string|null,
     *     assigned_to?: int|null,
     *     application_id?: int|null,
     *     job_id?: int|null,
     *     candidate_profile_id?: int|null,
     *     employer_profile_id?: int|null,
     *     employer_enquiry_id?: int|null,
     *     crm_lead_id?: int|null,
     *     crm_opportunity_id?: int|null,
     *     channel_partner_id?: int|null,
     *     commission_entry_id?: int|null,
     *     investor_id?: int|null,
     *     investment_opportunity_id?: int|null,
     *     investor_interest_id?: int|null
     * }  $data
     */
    public function create(array $data, User $actor): Task
    {
        $this->assertCreateAuthorization($data, $actor);
        $this->assertAssignable($data['assigned_to'] ?? null);

        $task = DB::transaction(function () use ($data, $actor) {
            $task = Task::query()->create([
                'title' => $data['title'],
                'body' => $data['body'] ?? null,
                'status' => TaskStatus::Open,
                'due_at' => $data['due_at'] ?? null,
                'assigned_to' => $data['assigned_to'] ?? null,
                'created_by' => $actor->id,
                'application_id' => $data['application_id'] ?? null,
                'job_id' => $data['job_id'] ?? null,
                'candidate_profile_id' => $data['candidate_profile_id'] ?? null,
                'employer_profile_id' => $data['employer_profile_id'] ?? null,
                'employer_enquiry_id' => $data['employer_enquiry_id'] ?? null,
                'crm_lead_id' => $data['crm_lead_id'] ?? null,
                'crm_opportunity_id' => $data['crm_opportunity_id'] ?? null,
                'channel_partner_id' => $data['channel_partner_id'] ?? null,
                'commission_entry_id' => $data['commission_entry_id'] ?? null,
                'investor_id' => $data['investor_id'] ?? null,
                'investment_opportunity_id' => $data['investment_opportunity_id'] ?? null,
                'investor_interest_id' => $data['investor_interest_id'] ?? null,
                'campaign_id' => $data['campaign_id'] ?? null,
                'referral_link_id' => $data['referral_link_id'] ?? null,
                'referral_id' => $data['referral_id'] ?? null,
            ]);

            $application = $task->application_id
                ? Application::query()->find($task->application_id)
                : null;

            $this->activities->record(
                actor: $actor,
                type: RecruitmentActivityType::TaskCreated,
                application: $application,
                task: $task,
                body: 'Task created: '.$task->title,
            );

            return $task->fresh() ?? $task;
        });

        if ($task->assigned_to) {
            $this->alerts->taskAssigned($task);
        }

        return $task;
    }

    /**
     * @param  array{
     *     title?: string,
     *     body?: string|null,
     *     due_at?: string|null,
     *     assigned_to?: int|null
     * }  $data
     */
    public function update(Task $task, array $data, User $actor): Task
    {
        $previousAssignee = $task->assigned_to;
        if (array_key_exists('assigned_to', $data)) {
            $this->assertAssignable($data['assigned_to']);
        }

        $task->update([
            'title' => $data['title'] ?? $task->title,
            'body' => array_key_exists('body', $data) ? $data['body'] : $task->body,
            'due_at' => array_key_exists('due_at', $data) ? $data['due_at'] : $task->due_at,
            'assigned_to' => array_key_exists('assigned_to', $data) ? $data['assigned_to'] : $task->assigned_to,
        ]);

        $fresh = $task->fresh() ?? $task;

        if ($fresh->assigned_to && (int) $fresh->assigned_to !== (int) $previousAssignee) {
            $this->alerts->taskAssigned($fresh);
        }

        return $fresh;
    }

    public function complete(Task $task, User $actor): Task
    {
        if (! $task->status->isOpen()) {
            throw ValidationException::withMessages([
                'task' => 'Only open tasks can be completed.',
            ]);
        }

        return DB::transaction(function () use ($task, $actor) {
            $task->update([
                'status' => TaskStatus::Done,
                'completed_at' => now(),
            ]);

            $this->activities->record(
                actor: $actor,
                type: RecruitmentActivityType::TaskCompleted,
                application: $task->application,
                task: $task,
                body: 'Task completed: '.$task->title,
            );

            return $task->fresh() ?? $task;
        });
    }

    public function cancel(Task $task, User $actor): Task
    {
        if (! $task->status->isOpen()) {
            throw ValidationException::withMessages([
                'task' => 'Only open tasks can be cancelled.',
            ]);
        }

        return DB::transaction(function () use ($task, $actor) {
            $task->update([
                'status' => TaskStatus::Cancelled,
            ]);

            $this->activities->record(
                actor: $actor,
                type: RecruitmentActivityType::TaskCancelled,
                application: $task->application,
                task: $task,
                body: 'Task cancelled: '.$task->title,
            );

            return $task->fresh() ?? $task;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function assertCreateAuthorization(array $data, User $actor): void
    {
        $applicationId = $data['application_id'] ?? null;
        $jobId = $data['job_id'] ?? null;
        $candidateId = $data['candidate_profile_id'] ?? null;
        $employerId = $data['employer_profile_id'] ?? null;
        $enquiryId = $data['employer_enquiry_id'] ?? null;
        $leadId = $data['crm_lead_id'] ?? null;
        $opportunityId = $data['crm_opportunity_id'] ?? null;
        $partnerId = $data['channel_partner_id'] ?? null;
        $commissionId = $data['commission_entry_id'] ?? null;
        $investorId = $data['investor_id'] ?? null;
        $investmentOpportunityId = $data['investment_opportunity_id'] ?? null;
        $investorInterestId = $data['investor_interest_id'] ?? null;
        $campaignId = $data['campaign_id'] ?? null;
        $referralLinkId = $data['referral_link_id'] ?? null;
        $referralId = $data['referral_id'] ?? null;

        $isInvestorLinked = $investorId || $investmentOpportunityId || $investorInterestId;
        $isCampaignLinked = $campaignId || $referralLinkId || $referralId;
        $isEmployerLinked = $employerId || $enquiryId || $leadId || $opportunityId || $partnerId;
        $isCommissionOnly = $commissionId && ! $isEmployerLinked && ! $isInvestorLinked && ! $isCampaignLinked && ! $applicationId && ! $candidateId && ! $jobId;
        $isJobOnly = $jobId && ! $applicationId && ! $candidateId && ! $isEmployerLinked && ! $isInvestorLinked && ! $isCampaignLinked && ! $commissionId;

        if ($isJobOnly && ! $actor->can('update', Job::query()->findOrFail($jobId))) {
            throw ValidationException::withMessages([
                'job_id' => 'You cannot assign a task against this job.',
            ]);
        }

        if ($isCampaignLinked && ! $applicationId && ! $candidateId && ! $isEmployerLinked && ! $isInvestorLinked) {
            if (! $actor->hasPermission(PermissionName::CampaignsManage->value)) {
                throw ValidationException::withMessages([
                    'title' => 'You cannot create a campaign task.',
                ]);
            }
        } elseif ($isInvestorLinked && ! $applicationId && ! $candidateId && ! $isEmployerLinked) {
            if (! $actor->hasPermission(PermissionName::InvestorsManage->value)) {
                throw ValidationException::withMessages([
                    'title' => 'You cannot create an investor task.',
                ]);
            }
        } elseif ($isCommissionOnly) {
            if (
                ! $actor->hasPermission(PermissionName::FinanceManage->value)
                && ! $actor->hasPermission(PermissionName::EmployersManage->value)
            ) {
                throw ValidationException::withMessages([
                    'title' => 'You cannot create a commission task.',
                ]);
            }
        } elseif ($isEmployerLinked && ! $applicationId && ! $candidateId) {
            if (! $actor->hasPermission(PermissionName::EmployersManage->value)) {
                throw ValidationException::withMessages([
                    'title' => 'You cannot create an employer or CRM task.',
                ]);
            }
        } elseif (! $isJobOnly && ! $actor->hasPermission(PermissionName::ApplicationsManage->value)) {
            throw ValidationException::withMessages([
                'title' => 'You cannot create this task.',
            ]);
        }

        if ($applicationId && Application::query()->whereKey($applicationId)->doesntExist()) {
            throw ValidationException::withMessages([
                'application_id' => 'The selected application is invalid.',
            ]);
        }

        if ($candidateId && CandidateProfile::query()->whereKey($candidateId)->doesntExist()) {
            throw ValidationException::withMessages([
                'candidate_profile_id' => 'The selected candidate is invalid.',
            ]);
        }

        if ($employerId && EmployerProfile::query()->whereKey($employerId)->doesntExist()) {
            throw ValidationException::withMessages([
                'employer_profile_id' => 'The selected employer is invalid.',
            ]);
        }

        if ($enquiryId && EmployerEnquiry::query()->whereKey($enquiryId)->doesntExist()) {
            throw ValidationException::withMessages([
                'employer_enquiry_id' => 'The selected employer enquiry is invalid.',
            ]);
        }

        if ($leadId && CrmLead::query()->whereKey($leadId)->doesntExist()) {
            throw ValidationException::withMessages([
                'crm_lead_id' => 'The selected CRM lead is invalid.',
            ]);
        }

        if ($opportunityId && CrmOpportunity::query()->whereKey($opportunityId)->doesntExist()) {
            throw ValidationException::withMessages([
                'crm_opportunity_id' => 'The selected CRM opportunity is invalid.',
            ]);
        }

        if ($partnerId && ChannelPartner::query()->whereKey($partnerId)->doesntExist()) {
            throw ValidationException::withMessages([
                'channel_partner_id' => 'The selected channel partner is invalid.',
            ]);
        }

        if ($commissionId && CommissionEntry::query()->whereKey($commissionId)->doesntExist()) {
            throw ValidationException::withMessages([
                'commission_entry_id' => 'The selected commission entry is invalid.',
            ]);
        }

        if ($investorId && Investor::query()->whereKey($investorId)->doesntExist()) {
            throw ValidationException::withMessages([
                'investor_id' => 'The selected investor is invalid.',
            ]);
        }

        if ($investmentOpportunityId && InvestmentOpportunity::query()->whereKey($investmentOpportunityId)->doesntExist()) {
            throw ValidationException::withMessages([
                'investment_opportunity_id' => 'The selected investment opportunity is invalid.',
            ]);
        }

        if ($investorInterestId && InvestorInterest::query()->whereKey($investorInterestId)->doesntExist()) {
            throw ValidationException::withMessages([
                'investor_interest_id' => 'The selected investor interest is invalid.',
            ]);
        }

        if ($campaignId && Campaign::query()->whereKey($campaignId)->doesntExist()) {
            throw ValidationException::withMessages([
                'campaign_id' => 'The selected campaign is invalid.',
            ]);
        }

        if ($referralLinkId && ReferralLink::query()->whereKey($referralLinkId)->doesntExist()) {
            throw ValidationException::withMessages([
                'referral_link_id' => 'The selected referral link is invalid.',
            ]);
        }

        if ($referralId && Referral::query()->whereKey($referralId)->doesntExist()) {
            throw ValidationException::withMessages([
                'referral_id' => 'The selected referral is invalid.',
            ]);
        }
    }

    private function assertAssignable(mixed $assignedTo): void
    {
        if ($assignedTo === null || $assignedTo === '') {
            return;
        }

        if (! StaffOptions::isAssignable((int) $assignedTo)) {
            throw ValidationException::withMessages([
                'assigned_to' => 'Tasks can only be assigned to an active Admin staff user.',
            ]);
        }
    }
}
