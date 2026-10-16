<?php

namespace App\Enums;

enum AdminNotificationEvent: string
{
    case ApplicationReceived = 'application_received';
    case FreelancerReceived = 'freelancer_received';
    case CandidateShortlisted = 'candidate_shortlisted';
    case SubmissionCreated = 'submission_created';
    case EmployerSelected = 'employer_selected';
    case EmployerRejected = 'employer_rejected';
    case PlacementCreated = 'placement_created';
    case OfferCreated = 'offer_created';
    case OfferAccepted = 'offer_accepted';
    case OfferDeclined = 'offer_declined';
    case JoiningConfirmed = 'joining_confirmed';
    case EmployeeJoined = 'employee_joined';
    case ReplacementRequested = 'replacement_requested';
    case ReplacementCandidateIdentified = 'replacement_candidate_identified';
    case ReplacementCompleted = 'replacement_completed';
    case ReplacementCancelled = 'replacement_cancelled';
    case InvoiceIssued = 'invoice_issued';
    case PaymentReceived = 'payment_received';
    case InvoiceCancelled = 'invoice_cancelled';
    case InterviewScheduled = 'interview_scheduled';
    case InterviewReminder = 'interview_reminder';
    case TaskAssigned = 'task_assigned';
    case TaskDue = 'task_due';
    case CrmLeadAssigned = 'crm_lead_assigned';
    case CrmOpportunityStageChanged = 'crm_opportunity_stage_changed';
    case CrmConverted = 'crm_converted';
    case PartnerAgreementConfirmed = 'partner_agreement_confirmed';
    case AttributionDisputed = 'attribution_disputed';
    case CommissionApproved = 'commission_approved';
    case CommissionPaid = 'commission_paid';
    case InvestorVerified = 'investor_verified';
    case OpportunityInterestReceived = 'opportunity_interest_received';
    case OpportunityStageChanged = 'opportunity_stage_changed';
    case CampaignActivated = 'campaign_activated';
    case ReferralAttributed = 'referral_attributed';
    case AutomationRecommended = 'automation_recommended';
    case TalentPoolAssignmentProgress = 'talent_pool_assignment_progress';
    case TalentPoolSubmissionCreated = 'talent_pool_submission_created';
    case CompanyRelationshipRecruiterAssigned = 'company_relationship_recruiter_assigned';
    case TalentPoolEmployerResponse = 'talent_pool_employer_response';
    case StaffingAccessRequested = 'staffing_access_requested';

    public function category(): string
    {
        return match ($this) {
            self::ApplicationReceived => 'Application',
            self::FreelancerReceived => 'Freelancer',
            self::CandidateShortlisted => 'Shortlisted',
            self::SubmissionCreated => 'Employer Submission',
            self::EmployerSelected, self::EmployerRejected => 'Selection',
            self::PlacementCreated => 'Placement',
            self::OfferCreated, self::OfferAccepted, self::OfferDeclined => 'Offer',
            self::JoiningConfirmed, self::EmployeeJoined => 'Joining',
            self::ReplacementRequested, self::ReplacementCandidateIdentified, self::ReplacementCompleted, self::ReplacementCancelled => 'Replacement',
            self::InvoiceIssued, self::PaymentReceived, self::InvoiceCancelled => 'Payment',
            self::InterviewScheduled, self::InterviewReminder => 'Interview',
            self::TaskAssigned, self::TaskDue => 'Task',
            self::CrmLeadAssigned, self::CrmOpportunityStageChanged, self::CrmConverted => 'CRM',
            self::PartnerAgreementConfirmed, self::AttributionDisputed, self::CommissionApproved, self::CommissionPaid => 'Partner',
            self::InvestorVerified, self::OpportunityInterestReceived, self::OpportunityStageChanged => 'Investor',
            self::CampaignActivated, self::ReferralAttributed => 'Campaign',
            self::AutomationRecommended => 'Automation',
            self::TalentPoolAssignmentProgress, self::TalentPoolSubmissionCreated, self::CompanyRelationshipRecruiterAssigned, self::TalentPoolEmployerResponse => 'Talent Pool',
            self::StaffingAccessRequested => 'Staffing',
        };
    }

    public function label(): string
    {
        return str_replace('_', ' ', $this->value);
    }

    /**
     * @return list<string>
     */
    public static function categories(): array
    {
        return array_values(array_unique(array_map(
            fn (self $event) => $event->category(),
            self::cases(),
        )));
    }

    /**
     * @return list<string>
     */
    public static function valuesForCategory(string $category): array
    {
        return array_values(array_map(
            fn (self $event) => $event->value,
            array_filter(self::cases(), fn (self $event) => $event->category() === $category),
        ));
    }
}
