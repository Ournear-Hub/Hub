<?php

namespace App\Enums;

enum SecurityActivityType: string
{
    case UserCreated = 'user_created';
    case UserUpdated = 'user_updated';
    case UserActivated = 'user_activated';
    case UserDeactivated = 'user_deactivated';
    case RoleAssigned = 'role_assigned';
    case RoleRemoved = 'role_removed';
    case RoleCreated = 'role_created';
    case PermissionChanged = 'permission_changed';
    case PasswordResetSent = 'password_reset_sent';
    case UserBlocked = 'user_blocked';
    case UserSuspended = 'user_suspended';
    case InvoiceCancelled = 'invoice_cancelled';
    case UserSourceChanged = 'user_source_changed';
    case TagCreated = 'tag_created';
    case TagUpdated = 'tag_updated';
    case TagStatusChanged = 'tag_status_changed';
    case TagAssigned = 'tag_assigned';
    case TagRemoved = 'tag_removed';
    case TalentInterestRecorded = 'talent_interest_recorded';
    case EmployerPostingApproved = 'employer_posting_approved';
    case EmployerPostingRevoked = 'employer_posting_revoked';
    case EmployerPostingRestricted = 'employer_posting_restricted';
    case EmployerPostingUnrestricted = 'employer_posting_unrestricted';
    case JobPublished = 'job_published';
    case JobUnpublished = 'job_unpublished';
    case JobPaused = 'job_paused';
    case JobClosed = 'job_closed';
    case JobArchived = 'job_archived';
    case CrmConverted = 'crm_converted';
    case CrmEmployerCreated = 'crm_employer_created';
    case PartnerApproved = 'partner_approved';
    case PartnerSuspended = 'partner_suspended';
    case AttributionConfirmed = 'attribution_confirmed';
    case CommissionApproved = 'commission_approved';
    case CommissionVoided = 'commission_voided';
    case InvestorVerified = 'investor_verified';
    case InvestorSuspended = 'investor_suspended';
    case InvestmentOpportunityOpened = 'investment_opportunity_opened';
    case InvestorInterestCommitted = 'investor_interest_committed';
    case CampaignActivated = 'campaign_activated';
    case ReferralAttributed = 'referral_attributed';

    public function label(): string
    {
        return match ($this) {
            self::UserCreated => 'User created',
            self::UserUpdated => 'User updated',
            self::UserActivated => 'User activated',
            self::UserDeactivated => 'User deactivated',
            self::RoleAssigned => 'Role assigned',
            self::RoleRemoved => 'Role removed',
            self::RoleCreated => 'Role created',
            self::PermissionChanged => 'Permissions changed',
            self::PasswordResetSent => 'Password reset sent',
            self::UserBlocked => 'User blocked',
            self::UserSuspended => 'User suspended',
            self::InvoiceCancelled => 'Invoice cancelled',
            self::UserSourceChanged => 'Registration source changed',
            self::TagCreated => 'Tag created',
            self::TagUpdated => 'Tag updated',
            self::TagStatusChanged => 'Tag status changed',
            self::TagAssigned => 'Tag assigned',
            self::TagRemoved => 'Tag removed',
            self::TalentInterestRecorded => 'Talent interest recorded',
            self::EmployerPostingApproved => 'Employer posting approved',
            self::EmployerPostingRevoked => 'Employer posting revoked',
            self::EmployerPostingRestricted => 'Employer posting restricted',
            self::EmployerPostingUnrestricted => 'Employer posting unrestricted',
            self::JobPublished => 'Job published',
            self::JobUnpublished => 'Job unpublished',
            self::JobPaused => 'Job paused',
            self::JobClosed => 'Job closed',
            self::JobArchived => 'Job archived',
            self::CrmConverted => 'CRM converted to employer',
            self::CrmEmployerCreated => 'Employer created from CRM',
            self::PartnerApproved => 'Channel partner approved',
            self::PartnerSuspended => 'Channel partner suspended',
            self::AttributionConfirmed => 'Employer attribution confirmed',
            self::CommissionApproved => 'Commission approved',
            self::CommissionVoided => 'Commission voided',
            self::InvestorVerified => 'Investor verified',
            self::InvestorSuspended => 'Investor suspended',
            self::InvestmentOpportunityOpened => 'Investment opportunity opened',
            self::InvestorInterestCommitted => 'Non-binding investment intent recorded',
            self::CampaignActivated => 'Campaign activated',
            self::ReferralAttributed => 'Public referral attributed',
        };
    }
}
