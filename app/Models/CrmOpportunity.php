<?php

namespace App\Models;

use App\Enums\CrmOpportunityStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmOpportunity extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'crm_lead_id',
        'employer_profile_id',
        'title',
        'status',
        'notes',
        'assigned_to',
        'created_by',
        'converted_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => CrmOpportunityStatus::class,
            'converted_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<CrmLead, $this>
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(CrmLead::class, 'crm_lead_id');
    }

    /**
     * @return BelongsTo<EmployerProfile, $this>
     */
    public function employer(): BelongsTo
    {
        return $this->belongsTo(EmployerProfile::class, 'employer_profile_id');
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
     * @return array<string, mixed>
     */
    public function toAdminSummary(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'assigned_to' => $this->assigned_to,
            'employer_profile_id' => $this->employer_profile_id,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminArray(): array
    {
        $this->loadMissing([
            'lead.employer',
            'lead.enquiry',
            'employer:id,business_id,company_name,user_id',
            'assignee:id,name',
            'creator:id,name',
        ]);

        return [
            'id' => $this->id,
            'crm_lead_id' => $this->crm_lead_id,
            'title' => $this->title,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'notes' => $this->notes,
            'assigned_to' => $this->assigned_to,
            'assignee_name' => $this->assignee?->name,
            'created_by' => $this->created_by,
            'creator_name' => $this->creator?->name,
            'employer_profile_id' => $this->employer_profile_id,
            'converted_at' => $this->converted_at?->toDateTimeString(),
            'created_at' => $this->created_at?->toDateTimeString(),
            'lead' => $this->lead?->toAdminArray(),
            'employer' => $this->employer ? [
                'id' => $this->employer->id,
                'business_id' => $this->employer->business_id,
                'company_name' => $this->employer->company_name,
                'workspace_url' => route('admin.employers.index', ['employer' => $this->employer->id], false),
            ] : null,
        ];
    }
}
