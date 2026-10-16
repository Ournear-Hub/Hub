<?php

namespace App\Services;

use App\Enums\RecruitmentActivityType;
use App\Enums\RecruitmentOperationalVisibility;
use App\Models\Application;
use App\Models\ChannelPartner;
use App\Models\ChannelPartnerAgreement;
use App\Models\CommissionEntry;
use App\Models\CrmLead;
use App\Models\CrmOpportunity;
use App\Models\Employee;
use App\Models\EmployerEnquiry;
use App\Models\EmployerProfile;
use App\Models\EmployerSubmission;
use App\Models\ApplicationInterview;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Placement;
use App\Models\Replacement;
use App\Models\RecruitmentActivity;
use App\Models\InvestmentOpportunity;
use App\Models\Investor;
use App\Models\InvestorInterest;
use App\Models\Campaign;
use App\Models\Referral;
use App\Models\ReferralLink;
use App\Models\ShareEvent;
use App\Models\Task;
use App\Models\User;

class RecruitmentActivityService
{
    public function record(
        User $actor,
        RecruitmentActivityType $type,
        ?Application $application = null,
        ?EmployerEnquiry $enquiry = null,
        ?string $body = null,
        ?string $fromStatus = null,
        ?string $toStatus = null,
        ?EmployerSubmission $submission = null,
        ?EmployerProfile $employer = null,
        ?Placement $placement = null,
        ?Employee $employee = null,
        ?Replacement $replacement = null,
        ?Invoice $invoice = null,
        ?Payment $payment = null,
        ?ApplicationInterview $interview = null,
        ?Task $task = null,
        ?CrmLead $crmLead = null,
        ?CrmOpportunity $crmOpportunity = null,
        ?ChannelPartner $channelPartner = null,
        ?ChannelPartnerAgreement $partnerAgreement = null,
        ?CommissionEntry $commissionEntry = null,
        ?Investor $investor = null,
        ?InvestmentOpportunity $investmentOpportunity = null,
        ?InvestorInterest $investorInterest = null,
        ?Campaign $campaign = null,
        ?ReferralLink $referralLink = null,
        ?ShareEvent $shareEvent = null,
        ?Referral $referral = null,
        ?\App\Models\TalentPoolCompanySubmission $talentPoolSubmission = null,
        ?\App\Models\RecruiterTalentPoolAssignment $talentPoolAssignment = null,
        ?\App\Models\StaffingCandidateSubmission $staffingSubmission = null,
        ?RecruitmentOperationalVisibility $visibility = null,
        ?array $operationalMeta = null,
    ): RecruitmentActivity {
        return RecruitmentActivity::query()->create([
            'application_id' => $application?->id ?? $interview?->application_id ?? $task?->application_id,
            'employer_enquiry_id' => $enquiry?->id
                ?? $staffingSubmission?->employer_enquiry_id
                ?? $crmLead?->employer_enquiry_id
                ?? $task?->employer_enquiry_id,
            'employer_submission_id' => $submission?->id,
            'talent_pool_company_submission_id' => $talentPoolSubmission?->id,
            'staffing_candidate_submission_id' => $staffingSubmission?->id,
            'employer_profile_id' => $employer?->id
                ?? $talentPoolSubmission?->employer_profile_id
                ?? $crmLead?->employer_profile_id
                ?? $crmOpportunity?->employer_profile_id
                ?? $submission?->employer_profile_id
                ?? $placement?->employer_profile_id
                ?? $invoice?->employer_profile_id
                ?? $task?->employer_profile_id,
            'placement_id' => $placement?->id ?? $employee?->placement_id ?? $invoice?->placement_id,
            'employee_id' => $employee?->id ?? $invoice?->employee_id,
            'replacement_id' => $replacement?->id ?? $invoice?->replacement_id,
            'invoice_id' => $invoice?->id,
            'payment_id' => $payment?->id,
            'interview_id' => $interview?->id,
            'task_id' => $task?->id,
            'crm_lead_id' => $crmLead?->id ?? $crmOpportunity?->crm_lead_id ?? $task?->crm_lead_id,
            'crm_opportunity_id' => $crmOpportunity?->id ?? $task?->crm_opportunity_id,
            'channel_partner_id' => $channelPartner?->id
                ?? $partnerAgreement?->channel_partner_id
                ?? $commissionEntry?->channel_partner_id
                ?? $crmLead?->channel_partner_id
                ?? $task?->channel_partner_id,
            'partner_agreement_id' => $partnerAgreement?->id ?? $commissionEntry?->channel_partner_agreement_id,
            'commission_entry_id' => $commissionEntry?->id ?? $task?->commission_entry_id,
            'investor_id' => $investor?->id
                ?? $investorInterest?->investor_id
                ?? $task?->investor_id,
            'investment_opportunity_id' => $investmentOpportunity?->id
                ?? $investorInterest?->investment_opportunity_id
                ?? $task?->investment_opportunity_id,
            'investor_interest_id' => $investorInterest?->id ?? $task?->investor_interest_id,
            'campaign_id' => $campaign?->id ?? $referralLink?->campaign_id ?? $referral?->campaign_id ?? $task?->campaign_id,
            'referral_link_id' => $referralLink?->id ?? $referral?->referral_link_id ?? $shareEvent?->referral_link_id ?? $task?->referral_link_id,
            'share_event_id' => $shareEvent?->id,
            'referral_id' => $referral?->id ?? $task?->referral_id,
            'type' => $type,
            'body' => $body,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'actor_id' => $actor->id,
            'recruiter_talent_pool_assignment_id' => $talentPoolAssignment?->id
                ?? $talentPoolSubmission?->recruiter_talent_pool_assignment_id,
            'visibility' => $visibility?->value,
            'operational_meta' => $operationalMeta,
        ]);
    }
}
