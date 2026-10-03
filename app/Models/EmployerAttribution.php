<?php

namespace App\Models;

use App\Enums\EmployerAttributionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployerAttribution extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'channel_partner_id',
        'employer_profile_id',
        'crm_lead_id',
        'crm_opportunity_id',
        'status',
        'notes',
        'confirmed_at',
        'confirmed_by',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => EmployerAttributionStatus::class,
            'confirmed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<ChannelPartner, $this>
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(ChannelPartner::class, 'channel_partner_id');
    }

    /**
     * @return BelongsTo<EmployerProfile, $this>
     */
    public function employer(): BelongsTo
    {
        return $this->belongsTo(EmployerProfile::class, 'employer_profile_id');
    }

    /**
     * @return BelongsTo<CrmLead, $this>
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(CrmLead::class, 'crm_lead_id');
    }

    /**
     * @return BelongsTo<CrmOpportunity, $this>
     */
    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(CrmOpportunity::class, 'crm_opportunity_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function isConfirmed(): bool
    {
        return $this->status === EmployerAttributionStatus::Confirmed;
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminArray(): array
    {
        $this->loadMissing([
            'partner:id,business_id,display_name,status',
            'employer:id,business_id,company_name',
            'lead:id,company_name',
            'opportunity:id,title',
            'creator:id,name',
            'confirmer:id,name',
        ]);

        return [
            'id' => $this->id,
            'channel_partner_id' => $this->channel_partner_id,
            'employer_profile_id' => $this->employer_profile_id,
            'crm_lead_id' => $this->crm_lead_id,
            'crm_opportunity_id' => $this->crm_opportunity_id,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'notes' => $this->notes,
            'confirmed_at' => $this->confirmed_at?->toDateTimeString(),
            'creator_name' => $this->creator?->name,
            'confirmer_name' => $this->confirmer?->name,
            'partner' => $this->partner ? [
                'id' => $this->partner->id,
                'business_id' => $this->partner->business_id,
                'display_name' => $this->partner->display_name,
                'workspace_url' => route('admin.partners.index', ['partner' => $this->partner->id], false),
            ] : null,
            'employer' => $this->employer ? [
                'id' => $this->employer->id,
                'business_id' => $this->employer->business_id,
                'company_name' => $this->employer->company_name,
                'workspace_url' => route('admin.employers.index', ['employer' => $this->employer->id], false),
            ] : null,
            'lead' => $this->lead ? [
                'id' => $this->lead->id,
                'company_name' => $this->lead->company_name,
                'workspace_url' => route('admin.crm.index', ['lead' => $this->lead->id], false),
            ] : null,
            'opportunity' => $this->opportunity ? [
                'id' => $this->opportunity->id,
                'title' => $this->opportunity->title,
            ] : null,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
