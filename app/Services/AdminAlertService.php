<?php

namespace App\Services;

use App\Enums\AdminNotificationEvent;
use App\Enums\PermissionName;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\ApplicationInterview;
use App\Models\ChannelPartnerAgreement;
use App\Models\CommissionEntry;
use App\Models\CommissionPayment;
use App\Models\CrmLead;
use App\Models\EmployerAttribution;
use App\Models\CrmOpportunity;
use App\Models\Employee;
use App\Models\EmployerSubmission;
use App\Models\Freelancer;
use App\Models\InvestmentOpportunity;
use App\Models\Investor;
use App\Models\InvestorInterest;
use App\Models\Campaign;
use App\Models\Referral;
use App\Models\RecruitmentAutomation;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Placement;
use App\Models\Replacement;
use App\Models\Task;
use App\Models\User;
use App\Notifications\LifecycleNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class AdminAlertService
{
    public function applicationReceived(Application $application): void
    {
        $application->loadMissing('job');

        $this->send(
            AdminNotificationEvent::ApplicationReceived,
            'Application received',
            trim($application->full_name.' applied for '.($application->job?->title ?? 'a job').'.'),
            '/admin/applications?application='.$application->id,
            ['application_id' => $application->id],
        );
    }

    public function freelancerReceived(Freelancer $freelancer): void
    {
        $this->send(
            AdminNotificationEvent::FreelancerReceived,
            'New freelancer enquiry',
            trim($freelancer->name.' applied as a freelancer. Freelancer ID '.($freelancer->business_id ?? '').'.'),
            '/admin/freelancers/'.$freelancer->id,
            ['freelancer_id' => $freelancer->id],
        );
    }

    public function candidateShortlisted(Application $application): void
    {
        $application->loadMissing('job');

        $this->send(
            AdminNotificationEvent::CandidateShortlisted,
            'Candidate shortlisted',
            trim($application->full_name.' was shortlisted for '.($application->job?->title ?? 'a job').'.'),
            '/admin/applications?application='.$application->id,
            ['application_id' => $application->id],
        );
    }

    public function submissionCreated(EmployerSubmission $submission): void
    {
        $submission->loadMissing(['application', 'employer', 'job']);

        $this->send(
            AdminNotificationEvent::SubmissionCreated,
            'Employer submission created',
            trim(($submission->application?->full_name ?? 'A candidate').' was submitted to '.($submission->employer?->displayCompany() ?? 'an employer').'.'),
            '/admin/submissions?submission='.$submission->id,
            ['submission_id' => $submission->id, 'application_id' => $submission->application_id],
        );
    }

    public function employerSelected(EmployerSubmission $submission): void
    {
        $submission->loadMissing(['application', 'job']);

        $this->send(
            AdminNotificationEvent::EmployerSelected,
            'Employer selected a candidate',
            trim(($submission->application?->full_name ?? 'A candidate').' was selected for '.($submission->job?->title ?? 'a job').'.'),
            '/admin/submissions?submission='.$submission->id,
            ['submission_id' => $submission->id],
        );
    }

    public function employerRejected(EmployerSubmission $submission): void
    {
        $submission->loadMissing(['application', 'job']);

        $this->send(
            AdminNotificationEvent::EmployerRejected,
            'Employer rejected a submission',
            trim(($submission->application?->full_name ?? 'A candidate').' was rejected for '.($submission->job?->title ?? 'a job').'.'),
            '/admin/submissions?submission='.$submission->id,
            ['submission_id' => $submission->id],
        );
    }

    public function placementCreated(Placement $placement): void
    {
        $this->send(
            AdminNotificationEvent::PlacementCreated,
            'Placement created',
            'A placement was created from an employer-selected submission.',
            '/admin/placements?placement='.$placement->id,
            ['placement_id' => $placement->id],
        );
    }

    public function offerCreated(Placement $placement): void
    {
        $this->send(
            AdminNotificationEvent::OfferCreated,
            'Offer created',
            'An offer was created for a placement.',
            '/admin/placements?placement='.$placement->id,
            ['placement_id' => $placement->id],
        );
    }

    public function offerAccepted(Placement $placement): void
    {
        $this->send(
            AdminNotificationEvent::OfferAccepted,
            'Offer accepted',
            'An offer was accepted.',
            '/admin/placements?placement='.$placement->id,
            ['placement_id' => $placement->id],
        );
    }

    public function offerDeclined(Placement $placement): void
    {
        $this->send(
            AdminNotificationEvent::OfferDeclined,
            'Offer declined',
            'An offer was declined.',
            '/admin/placements?placement='.$placement->id,
            ['placement_id' => $placement->id],
        );
    }

    public function joiningConfirmed(Placement $placement): void
    {
        $this->send(
            AdminNotificationEvent::JoiningConfirmed,
            'Joining confirmed',
            'Joining was confirmed for a placement.',
            '/admin/placements?placement='.$placement->id,
            ['placement_id' => $placement->id],
        );
    }

    public function employeeJoined(Placement $placement, Employee $employee): void
    {
        $this->send(
            AdminNotificationEvent::EmployeeJoined,
            'Employee joined',
            'A candidate joined and an employee record was created.',
            '/admin/employees?employee='.$employee->id,
            ['employee_id' => $employee->id, 'placement_id' => $placement->id],
        );
    }

    public function replacementRequested(Replacement $replacement): void
    {
        $this->send(
            AdminNotificationEvent::ReplacementRequested,
            'Replacement requested',
            'A replacement case was requested.',
            '/admin/replacements?replacement='.$replacement->id,
            ['replacement_id' => $replacement->id],
        );
    }

    public function replacementCandidateIdentified(Replacement $replacement): void
    {
        $this->send(
            AdminNotificationEvent::ReplacementCandidateIdentified,
            'Replacement candidate identified',
            'A replacement candidate was identified.',
            '/admin/replacements?replacement='.$replacement->id,
            ['replacement_id' => $replacement->id],
        );
    }

    public function replacementCompleted(Replacement $replacement): void
    {
        $this->send(
            AdminNotificationEvent::ReplacementCompleted,
            'Replacement completed',
            'A replacement employee joined. The original employee remains historical.',
            '/admin/replacements?replacement='.$replacement->id,
            ['replacement_id' => $replacement->id],
        );
    }

    public function replacementCancelled(Replacement $replacement): void
    {
        $this->send(
            AdminNotificationEvent::ReplacementCancelled,
            'Replacement cancelled',
            'A replacement case was cancelled.',
            '/admin/replacements?replacement='.$replacement->id,
            ['replacement_id' => $replacement->id],
        );
    }

    public function invoiceIssued(Invoice $invoice): void
    {
        $this->send(
            AdminNotificationEvent::InvoiceIssued,
            'Invoice issued',
            'Invoice '.($invoice->business_id ?? '').' was issued.',
            '/admin/finance?invoice='.$invoice->id,
            ['invoice_id' => $invoice->id],
        );
    }

    public function paymentReceived(Invoice $invoice, Payment $payment): void
    {
        $this->send(
            AdminNotificationEvent::PaymentReceived,
            'Payment recorded',
            'Payment '.($payment->business_id ?? '').' was recorded against invoice '.($invoice->business_id ?? '').'.',
            '/admin/finance?invoice='.$invoice->id,
            ['invoice_id' => $invoice->id, 'payment_id' => $payment->id],
        );
    }

    public function invoiceCancelled(Invoice $invoice): void
    {
        $this->send(
            AdminNotificationEvent::InvoiceCancelled,
            'Invoice cancelled',
            'Invoice '.($invoice->business_id ?? '').' was cancelled.',
            '/admin/finance?invoice='.$invoice->id,
            ['invoice_id' => $invoice->id],
        );
    }

    public function interviewScheduled(ApplicationInterview $interview): void
    {
        $interview->loadMissing(['application.job', 'interviewer', 'creator']);
        $application = $interview->application;

        $this->sendTo(
            $this->interviewStaff($interview),
            AdminNotificationEvent::InterviewScheduled,
            'Interview scheduled',
            trim(($application?->full_name ?? 'A candidate').' has an interview for '.($application?->job?->title ?? 'a job').'.'),
            '/admin/interviews?interview='.$interview->id,
            ['interview_id' => $interview->id, 'application_id' => $interview->application_id],
        );
    }

    public function interviewReminder(ApplicationInterview $interview): void
    {
        $interview->loadMissing(['application.job', 'interviewer', 'creator']);
        $application = $interview->application;

        $this->sendTo(
            $this->interviewStaff($interview),
            AdminNotificationEvent::InterviewReminder,
            'Interview reminder',
            trim(($application?->full_name ?? 'A candidate').' has an upcoming interview for '.($application?->job?->title ?? 'a job').'.'),
            '/admin/interviews?interview='.$interview->id,
            ['interview_id' => $interview->id, 'application_id' => $interview->application_id],
        );
    }

    public function taskAssigned(Task $task): void
    {
        $task->loadMissing('assignee');
        if (! $task->assignee instanceof User) {
            return;
        }

        $this->sendTo(
            collect([$task->assignee]),
            AdminNotificationEvent::TaskAssigned,
            'Task assigned',
            'You were assigned: '.$task->title,
            '/admin/work',
            ['task_id' => $task->id],
        );
    }

    public function crmLeadAssigned(CrmLead $lead): void
    {
        $lead->loadMissing('assignee');
        if (! $lead->assignee instanceof User) {
            return;
        }

        $this->sendTo(
            collect([$lead->assignee]),
            AdminNotificationEvent::CrmLeadAssigned,
            'CRM lead assigned',
            'You were assigned CRM lead: '.$lead->company_name,
            '/admin/crm?lead='.$lead->id,
            ['crm_lead_id' => $lead->id],
        );
    }

    public function crmOpportunityAssigned(CrmOpportunity $opportunity): void
    {
        $opportunity->loadMissing(['assignee', 'lead']);
        if (! $opportunity->assignee instanceof User) {
            return;
        }

        $this->sendTo(
            collect([$opportunity->assignee]),
            AdminNotificationEvent::CrmLeadAssigned,
            'CRM opportunity assigned',
            'You were assigned CRM opportunity: '.$opportunity->title,
            '/admin/crm?opportunity='.$opportunity->id,
            ['crm_opportunity_id' => $opportunity->id],
        );
    }

    public function crmOpportunityStageChanged(CrmOpportunity $opportunity): void
    {
        $opportunity->loadMissing(['assignee', 'lead']);
        $recipients = collect([$opportunity->assignee])
            ->filter(fn ($user) => $user instanceof User);

        if ($recipients->isEmpty()) {
            $this->send(
                AdminNotificationEvent::CrmOpportunityStageChanged,
                'CRM opportunity stage changed',
                $opportunity->title.' is now '.$opportunity->status->label().'.',
                '/admin/crm?opportunity='.$opportunity->id,
                ['crm_opportunity_id' => $opportunity->id, 'status' => $opportunity->status->value],
            );

            return;
        }

        $this->sendTo(
            $recipients,
            AdminNotificationEvent::CrmOpportunityStageChanged,
            'CRM opportunity stage changed',
            $opportunity->title.' is now '.$opportunity->status->label().'.',
            '/admin/crm?opportunity='.$opportunity->id,
            ['crm_opportunity_id' => $opportunity->id, 'status' => $opportunity->status->value],
        );
    }

    public function partnerAgreementConfirmed(ChannelPartnerAgreement $agreement): void
    {
        $agreement->loadMissing('partner');

        $this->send(
            AdminNotificationEvent::PartnerAgreementConfirmed,
            'Partner agreement activated',
            trim(($agreement->partner?->display_name ?? 'A partner').' agreement was activated at '.$agreement->commission_percent.'%.'),
            '/admin/partners?tab=agreements&agreement='.$agreement->id,
            ['channel_partner_agreement_id' => $agreement->id, 'channel_partner_id' => $agreement->channel_partner_id],
        );
    }

    public function attributionDisputed(EmployerAttribution $attribution): void
    {
        $attribution->loadMissing(['partner', 'employer']);

        $this->send(
            AdminNotificationEvent::AttributionDisputed,
            'Employer attribution disputed',
            trim(($attribution->employer?->displayCompany() ?? 'An employer').' attribution for '.($attribution->partner?->display_name ?? 'a partner').' was disputed.'),
            '/admin/partners?tab=attributions&attribution='.$attribution->id,
            ['employer_attribution_id' => $attribution->id],
        );
    }

    public function commissionApproved(CommissionEntry $entry): void
    {
        $entry->loadMissing('partner');

        $this->send(
            AdminNotificationEvent::CommissionApproved,
            'Commission approved',
            'Commission '.($entry->business_id ?? '').' was approved for '.($entry->partner?->display_name ?? 'a partner').'.',
            '/admin/commission?entry='.$entry->id,
            ['commission_entry_id' => $entry->id],
        );
    }

    public function commissionPaid(CommissionPayment $payout): void
    {
        $payout->loadMissing('partner');

        $this->send(
            AdminNotificationEvent::CommissionPaid,
            'Commission payout recorded',
            'Payout '.($payout->business_id ?? '').' was recorded for '.($payout->partner?->display_name ?? 'a partner').'.',
            '/admin/commission?view=payouts&payout='.$payout->id,
            ['commission_payment_id' => $payout->id],
        );
    }

    public function investorVerified(Investor $investor): void
    {
        $this->send(
            AdminNotificationEvent::InvestorVerified,
            'Investor verified',
            $investor->display_name.' was verified.',
            '/admin/investors?investor='.$investor->id,
            ['investor_id' => $investor->id],
        );
    }

    public function opportunityInterestReceived(InvestorInterest $interest): void
    {
        $interest->loadMissing(['investor', 'opportunity']);

        $this->send(
            AdminNotificationEvent::OpportunityInterestReceived,
            'Investment interest received',
            trim(($interest->investor?->display_name ?? 'An investor').' expressed interest in '.($interest->opportunity?->title ?? 'an opportunity').'.'),
            '/admin/investors?tab=interests&investor='.$interest->investor_id.'&opportunity='.$interest->investment_opportunity_id,
            ['investor_interest_id' => $interest->id, 'investor_id' => $interest->investor_id],
        );
    }

    public function investmentOpportunityStageChanged(InvestmentOpportunity $opportunity): void
    {
        $this->send(
            AdminNotificationEvent::OpportunityStageChanged,
            'Investment opportunity stage changed',
            $opportunity->title.' is now '.$opportunity->status->label().'.',
            '/admin/investors?tab=opportunities&opportunity='.$opportunity->id,
            ['investment_opportunity_id' => $opportunity->id, 'status' => $opportunity->status->value],
        );
    }

    public function campaignActivated(Campaign $campaign): void
    {
        $this->send(
            AdminNotificationEvent::CampaignActivated,
            'Campaign activated',
            $campaign->name.' is now active.',
            '/admin/campaigns?campaign='.$campaign->id,
            ['campaign_id' => $campaign->id],
        );
    }

    public function referralAttributed(Referral $referral): void
    {
        $referral->loadMissing(['referralLink', 'referredUser', 'application']);

        $this->send(
            AdminNotificationEvent::ReferralAttributed,
            'Referral conversion recorded',
            trim(($referral->referredUser?->name ?? $referral->application?->full_name ?? 'A visitor').' was attributed to '.($referral->referralLink?->code ?? 'a referral link').'.'),
            '/admin/campaigns?tab=conversions&referral='.$referral->id,
            ['referral_id' => $referral->id],
        );
    }

    /**
     * @param  array<string, mixed>|null  $match
     */
    public function automationRecommended(
        RecruitmentAutomation $automation,
        ?Application $application,
        ?array $match,
        ?Task $task,
    ): void {
        $label = is_array($match) ? ($match['label'] ?? 'Not scored') : 'Not scored';
        $name = $application?->full_name ?? 'a candidate';

        $this->send(
            AdminNotificationEvent::AutomationRecommended,
            'Automation recommended recruiter review',
            trim($automation->name.': review '.$name.' ('.$label.'). Human decision remains final.'),
            $application
                ? '/admin/applications?application='.$application->id
                : '/admin/automations?automation='.$automation->id,
            [
                'automation_id' => $automation->id,
                'application_id' => $application?->id,
                'task_id' => $task?->id,
            ],
        );
    }

    public function crmConverted(CrmOpportunity $opportunity): void
    {
        $opportunity->loadMissing(['lead', 'employer', 'assignee']);

        $this->send(
            AdminNotificationEvent::CrmConverted,
            'CRM converted to employer',
            trim(($opportunity->lead?->company_name ?? $opportunity->title).' was converted to '.($opportunity->employer?->displayCompany() ?? 'an employer').'.'),
            '/admin/crm?opportunity='.$opportunity->id,
            [
                'crm_opportunity_id' => $opportunity->id,
                'crm_lead_id' => $opportunity->crm_lead_id,
                'employer_profile_id' => $opportunity->employer_profile_id,
            ],
        );
    }

    public function taskDue(Task $task): void
    {
        $task->loadMissing('assignee');
        if (! $task->assignee instanceof User) {
            return;
        }

        $this->sendTo(
            collect([$task->assignee]),
            AdminNotificationEvent::TaskDue,
            'Task due',
            'Task due: '.$task->title,
            '/admin/work',
            ['task_id' => $task->id],
        );
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function talentPoolAssignmentProgress(string $title, string $body, string $url, array $context): void
    {
        $this->send(
            AdminNotificationEvent::TalentPoolAssignmentProgress,
            $title,
            $body,
            $url,
            $context,
        );
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function staffingAccessRequested(string $companyName, int $employerProfileId, array $context = []): void
    {
        $this->send(
            AdminNotificationEvent::StaffingAccessRequested,
            'Staffing access requested',
            trim($companyName.' requested Staffing module access.'),
            '/admin/employers?employer='.$employerProfileId,
            array_merge(['employer_profile_id' => $employerProfileId], $context),
        );
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function send(
        AdminNotificationEvent $event,
        string $title,
        string $body,
        string $url,
        array $context,
    ): void {
        $this->sendTo($this->notificationStaff(), $event, $title, $body, $url, $context);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, User>  $recipients
     * @param  array<string, mixed>  $context
     */
    private function sendTo(
        $recipients,
        AdminNotificationEvent $event,
        string $title,
        string $body,
        string $url,
        array $context,
    ): void {
        $fingerprint = $event->value.':'.collect($context)->sortKeys()->map(fn ($value, $key) => $key.'='.$value)->implode('|');

        DB::afterCommit(function () use ($recipients, $event, $title, $body, $url, $context, $fingerprint) {
            try {
                $admins = collect($recipients)
                    ->filter(fn ($user) => $user instanceof User && $user->isAdmin() && $user->is_active)
                    ->unique('id')
                    ->values();

                if ($admins->isEmpty()) {
                    return;
                }

                $fresh = $admins->filter(function (User $admin) use ($fingerprint) {
                    return ! $admin->notifications()
                        ->where('data->fingerprint', $fingerprint)
                        ->exists();
                });

                if ($fresh->isEmpty()) {
                    return;
                }

                Notification::send($fresh, new LifecycleNotification(
                    $event,
                    $title,
                    $body,
                    $url,
                    $context,
                    $fingerprint,
                ));
            } catch (\Throwable $exception) {
                Log::warning('In-app admin notification could not be stored.', [
                    'event' => $event->value,
                    'message' => $exception->getMessage(),
                ]);
            }
        });
    }

    /**
     * @return \Illuminate\Support\Collection<int, User>
     */
    private function notificationStaff()
    {
        return User::query()
            ->where('role', UserRole::Admin->value)
            ->where('is_active', true)
            ->whereHas('roles.permissions', fn ($query) => $query->where('name', PermissionName::NotificationsView->value))
            ->get();
    }

    /**
     * @return \Illuminate\Support\Collection<int, User>
     */
    private function interviewStaff(ApplicationInterview $interview)
    {
        return collect([$interview->interviewer, $interview->creator])
            ->filter(fn ($user) => $user instanceof User);
    }
}
