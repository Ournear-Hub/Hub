<?php

namespace App\Models;

use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'body',
        'status',
        'due_at',
        'assigned_to',
        'created_by',
        'application_id',
        'job_id',
        'candidate_profile_id',
        'employer_profile_id',
        'employer_enquiry_id',
        'crm_lead_id',
        'crm_opportunity_id',
        'channel_partner_id',
        'commission_entry_id',
        'investor_id',
        'investment_opportunity_id',
        'investor_interest_id',
        'campaign_id',
        'referral_link_id',
        'referral_id',
        'completed_at',
        'reminder_sent_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TaskStatus::class,
            'due_at' => 'datetime',
            'completed_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<Application, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * @return BelongsTo<Job, $this>
     */
    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    /**
     * @return BelongsTo<CandidateProfile, $this>
     */
    public function candidate(): BelongsTo
    {
        return $this->belongsTo(CandidateProfile::class, 'candidate_profile_id');
    }

    /**
     * @return BelongsTo<EmployerProfile, $this>
     */
    public function employer(): BelongsTo
    {
        return $this->belongsTo(EmployerProfile::class, 'employer_profile_id');
    }

    /**
     * @return BelongsTo<EmployerEnquiry, $this>
     */
    public function enquiry(): BelongsTo
    {
        return $this->belongsTo(EmployerEnquiry::class, 'employer_enquiry_id');
    }

    /**
     * @return BelongsTo<CrmLead, $this>
     */
    public function crmLead(): BelongsTo
    {
        return $this->belongsTo(CrmLead::class);
    }

    /**
     * @return BelongsTo<CrmOpportunity, $this>
     */
    public function crmOpportunity(): BelongsTo
    {
        return $this->belongsTo(CrmOpportunity::class);
    }

    /**
     * @return BelongsTo<ChannelPartner, $this>
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(ChannelPartner::class, 'channel_partner_id');
    }

    /**
     * @return BelongsTo<CommissionEntry, $this>
     */
    public function commissionEntry(): BelongsTo
    {
        return $this->belongsTo(CommissionEntry::class);
    }

    /**
     * @return BelongsTo<Investor, $this>
     */
    public function investor(): BelongsTo
    {
        return $this->belongsTo(Investor::class);
    }

    /**
     * @return BelongsTo<InvestmentOpportunity, $this>
     */
    public function investmentOpportunity(): BelongsTo
    {
        return $this->belongsTo(InvestmentOpportunity::class);
    }

    /**
     * @return BelongsTo<InvestorInterest, $this>
     */
    public function investorInterest(): BelongsTo
    {
        return $this->belongsTo(InvestorInterest::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * @return BelongsTo<ReferralLink, $this>
     */
    public function referralLink(): BelongsTo
    {
        return $this->belongsTo(ReferralLink::class);
    }

    /**
     * @return BelongsTo<Referral, $this>
     */
    public function referral(): BelongsTo
    {
        return $this->belongsTo(Referral::class);
    }

    public function isJobOnly(): bool
    {
        return $this->job_id !== null
            && $this->application_id === null
            && $this->candidate_profile_id === null
            && ! $this->isEmployerLinked()
            && ! $this->isInvestorLinked()
            && ! $this->isCampaignLinked();
    }

    public function isCommissionLinked(): bool
    {
        return $this->commission_entry_id !== null;
    }

    public function isInvestorLinked(): bool
    {
        return $this->investor_id !== null
            || $this->investment_opportunity_id !== null
            || $this->investor_interest_id !== null;
    }

    public function isCampaignLinked(): bool
    {
        return $this->campaign_id !== null
            || $this->referral_link_id !== null
            || $this->referral_id !== null;
    }

    public function isEmployerLinked(): bool
    {
        return $this->employer_profile_id !== null
            || $this->employer_enquiry_id !== null
            || $this->crm_lead_id !== null
            || $this->crm_opportunity_id !== null
            || $this->channel_partner_id !== null
            || $this->isCommissionLinked();
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminArray(): array
    {
        $this->loadMissing([
            'assignee:id,name',
            'creator:id,name',
            'application:id,business_id,full_name,job_id',
            'job:id,business_id,title',
            'candidate.user:id,name',
            'employer:id,business_id,company_name',
            'enquiry:id,company_name',
            'crmLead:id,company_name',
            'crmOpportunity:id,title',
            'partner:id,business_id,display_name',
            'commissionEntry:id,business_id,commission_amount,status',
            'investor:id,business_id,display_name',
            'investmentOpportunity:id,business_id,title',
            'investorInterest:id,business_id,status',
            'campaign:id,business_id,name',
            'referralLink:id,code',
        ]);

        return [
            'id' => $this->id,
            'title' => $this->title,
            'body' => $this->body,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'due_at' => $this->due_at?->format('Y-m-d\TH:i'),
            'due_at_label' => $this->due_at?->toDateTimeString(),
            'assigned_to' => $this->assigned_to,
            'assignee_name' => $this->assignee?->name,
            'created_by' => $this->created_by,
            'creator_name' => $this->creator?->name,
            'application_id' => $this->application_id,
            'job_id' => $this->job_id,
            'candidate_profile_id' => $this->candidate_profile_id,
            'employer_profile_id' => $this->employer_profile_id,
            'employer_enquiry_id' => $this->employer_enquiry_id,
            'crm_lead_id' => $this->crm_lead_id,
            'crm_opportunity_id' => $this->crm_opportunity_id,
            'channel_partner_id' => $this->channel_partner_id,
            'commission_entry_id' => $this->commission_entry_id,
            'investor_id' => $this->investor_id,
            'investment_opportunity_id' => $this->investment_opportunity_id,
            'investor_interest_id' => $this->investor_interest_id,
            'campaign_id' => $this->campaign_id,
            'referral_link_id' => $this->referral_link_id,
            'referral_id' => $this->referral_id,
            'completed_at' => $this->completed_at?->toDateTimeString(),
            'is_job_only' => $this->isJobOnly(),
            'is_employer_linked' => $this->isEmployerLinked(),
            'is_investor_linked' => $this->isInvestorLinked(),
            'is_campaign_linked' => $this->isCampaignLinked(),
            'application' => $this->application ? [
                'id' => $this->application->id,
                'business_id' => $this->application->business_id,
                'full_name' => $this->application->full_name,
                'workspace_url' => route('admin.applications.index', ['application' => $this->application->id], false),
            ] : null,
            'job' => $this->job ? [
                'id' => $this->job->id,
                'business_id' => $this->job->business_id,
                'title' => $this->job->title,
            ] : null,
            'candidate' => $this->candidate ? [
                'id' => $this->candidate->id,
                'business_id' => $this->candidate->business_id,
                'name' => $this->candidate->user?->name,
            ] : null,
            'employer' => $this->employer ? [
                'id' => $this->employer->id,
                'business_id' => $this->employer->business_id,
                'company_name' => $this->employer->company_name,
                'workspace_url' => route('admin.employers.index', ['employer' => $this->employer->id], false),
            ] : null,
            'enquiry' => $this->enquiry ? [
                'id' => $this->enquiry->id,
                'company_name' => $this->enquiry->company_name,
                'workspace_url' => route('admin.employer-enquiries.index', ['enquiry' => $this->enquiry->id], false),
            ] : null,
            'crm_lead' => $this->crmLead ? [
                'id' => $this->crmLead->id,
                'company_name' => $this->crmLead->company_name,
                'workspace_url' => route('admin.crm.index', ['lead' => $this->crmLead->id], false),
            ] : null,
            'crm_opportunity' => $this->crmOpportunity ? [
                'id' => $this->crmOpportunity->id,
                'title' => $this->crmOpportunity->title,
                'workspace_url' => route('admin.crm.index', ['opportunity' => $this->crmOpportunity->id], false),
            ] : null,
            'partner' => $this->partner ? [
                'id' => $this->partner->id,
                'business_id' => $this->partner->business_id,
                'display_name' => $this->partner->display_name,
                'workspace_url' => route('admin.partners.index', ['partner' => $this->partner->id], false),
            ] : null,
            'commission_entry' => $this->commissionEntry ? [
                'id' => $this->commissionEntry->id,
                'business_id' => $this->commissionEntry->business_id,
                'workspace_url' => route('admin.commission.index', ['entry' => $this->commissionEntry->id], false),
            ] : null,
            'investor' => $this->investor ? [
                'id' => $this->investor->id,
                'business_id' => $this->investor->business_id,
                'display_name' => $this->investor->display_name,
                'workspace_url' => route('admin.investors.index', ['investor' => $this->investor->id], false),
            ] : null,
            'investment_opportunity' => $this->investmentOpportunity ? [
                'id' => $this->investmentOpportunity->id,
                'business_id' => $this->investmentOpportunity->business_id,
                'title' => $this->investmentOpportunity->title,
                'workspace_url' => route('admin.investors.index', [
                    'tab' => 'opportunities',
                    'opportunity' => $this->investmentOpportunity->id,
                ], false),
            ] : null,
            'campaign' => $this->campaign ? [
                'id' => $this->campaign->id,
                'business_id' => $this->campaign->business_id,
                'name' => $this->campaign->name,
                'workspace_url' => route('admin.campaigns.index', ['campaign' => $this->campaign->id], false),
            ] : null,
        ];
    }
}
