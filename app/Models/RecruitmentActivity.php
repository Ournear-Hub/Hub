<?php

namespace App\Models;

use App\Casts\RecoveredBackedEnumCast;
use App\Enums\RecruitmentActivityType;
use App\Enums\RecruitmentOperationalVisibility;
use Illuminate\Database\Eloquent\Model;
use App\Models\TalentPoolCompanySubmission;
use App\Models\RecruiterTalentPoolAssignment;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecruitmentActivity extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'application_id',
        'employer_enquiry_id',
        'employer_submission_id',
        'talent_pool_company_submission_id',
        'staffing_candidate_submission_id',
        'employer_profile_id',
        'placement_id',
        'employee_id',
        'replacement_id',
        'invoice_id',
        'payment_id',
        'interview_id',
        'task_id',
        'crm_lead_id',
        'crm_opportunity_id',
        'channel_partner_id',
        'partner_agreement_id',
        'commission_entry_id',
        'investor_id',
        'investment_opportunity_id',
        'investor_interest_id',
        'campaign_id',
        'referral_link_id',
        'share_event_id',
        'referral_id',
        'type',
        'body',
        'from_status',
        'to_status',
        'actor_id',
        'recruiter_talent_pool_assignment_id',
        'visibility',
        'operational_meta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => RecoveredBackedEnumCast::class.':'.RecruitmentActivityType::class,
            'visibility' => RecruitmentOperationalVisibility::class,
            'operational_meta' => 'array',
        ];
    }

    /**
     * @return BelongsTo<TalentPoolCompanySubmission, $this>
     */
    public function talentPoolSubmission(): BelongsTo
    {
        return $this->belongsTo(TalentPoolCompanySubmission::class, 'talent_pool_company_submission_id');
    }

    /**
     * @return BelongsTo<StaffingCandidateSubmission, $this>
     */
    public function staffingSubmission(): BelongsTo
    {
        return $this->belongsTo(StaffingCandidateSubmission::class, 'staffing_candidate_submission_id');
    }

    /**
     * @return BelongsTo<RecruiterTalentPoolAssignment, $this>
     */
    public function talentPoolAssignment(): BelongsTo
    {
        return $this->belongsTo(RecruiterTalentPoolAssignment::class, 'recruiter_talent_pool_assignment_id');
    }

    /**
     * @return BelongsTo<Application, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * @return BelongsTo<EmployerEnquiry, $this>
     */
    public function employerEnquiry(): BelongsTo
    {
        return $this->belongsTo(EmployerEnquiry::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * @return BelongsTo<EmployerSubmission, $this>
     */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(EmployerSubmission::class, 'employer_submission_id');
    }

    /**
     * @return BelongsTo<EmployerProfile, $this>
     */
    public function employer(): BelongsTo
    {
        return $this->belongsTo(EmployerProfile::class, 'employer_profile_id');
    }

    /**
     * @return BelongsTo<Placement, $this>
     */
    public function placement(): BelongsTo
    {
        return $this->belongsTo(Placement::class);
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return BelongsTo<Replacement, $this>
     */
    public function replacement(): BelongsTo
    {
        return $this->belongsTo(Replacement::class);
    }

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * @return BelongsTo<Payment, $this>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * @return BelongsTo<ApplicationInterview, $this>
     */
    public function interview(): BelongsTo
    {
        return $this->belongsTo(ApplicationInterview::class, 'interview_id');
    }

    /**
     * @return BelongsTo<Task, $this>
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
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
     * @return BelongsTo<ChannelPartnerAgreement, $this>
     */
    public function partnerAgreement(): BelongsTo
    {
        return $this->belongsTo(ChannelPartnerAgreement::class, 'partner_agreement_id');
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

    /**
     * @return BelongsTo<Campaign, $this>
     */
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
     * @return BelongsTo<ShareEvent, $this>
     */
    public function shareEvent(): BelongsTo
    {
        return $this->belongsTo(ShareEvent::class);
    }

    /**
     * @return BelongsTo<Referral, $this>
     */
    public function referral(): BelongsTo
    {
        return $this->belongsTo(Referral::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminArray(): array
    {
        $this->loadMissing('actor:id,name');

        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'body' => $this->body,
            'from_status' => $this->from_status,
            'to_status' => $this->to_status,
            'actor_name' => $this->actor?->name,
            'created_at' => $this->created_at?->toDateTimeString(),
            'visibility' => $this->visibility?->value,
            'operational_meta' => $this->operational_meta,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toOperationalArray(): array
    {
        $payload = $this->toAdminArray();
        $meta = is_array($this->operational_meta) ? $this->operational_meta : [];
        $payload['follow_up_type'] = $meta['follow_up_type'] ?? null;
        $payload['follow_up_type_label'] = $meta['follow_up_type_label'] ?? null;
        $payload['occurred_at'] = $meta['occurred_at'] ?? $payload['created_at'];
        $payload['outcome'] = $meta['outcome'] ?? null;
        $payload['issue'] = $meta['issue'] ?? null;
        $payload['next_action'] = $meta['next_action'] ?? null;
        $payload['next_action_due_at'] = $meta['next_action_due_at'] ?? null;
        $payload['visibility_label'] = $this->visibility?->label();

        return $payload;
    }
}
