<?php

namespace App\Models;

use App\Casts\RecoveredBackedEnumCast;
use App\Enums\EmployerEnquiryStatus;
use Database\Factories\EmployerEnquiryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class EmployerEnquiry extends Model
{
    /** @use HasFactory<EmployerEnquiryFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'company_name',
        'contact_person',
        'mobile_number',
        'email',
        'industry',
        'project_location',
        'workforce_type',
        'number_of_people',
        'required_skills',
        'expected_start_date',
        'training_required',
        'additional_requirements',
        'status',
        'internal_notes',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'internal_notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'number_of_people' => 'integer',
            'training_required' => 'boolean',
            'expected_start_date' => 'date',
            'internal_notes' => 'array',
            'status' => RecoveredBackedEnumCast::class.':'.EmployerEnquiryStatus::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Industry, $this>
     */
    public function classifiedIndustry(): BelongsTo
    {
        return $this->belongsTo(Industry::class, 'industry_id');
    }

    /**
     * @return BelongsTo<Location, $this>
     */
    public function classifiedLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    /**
     * @return HasOne<CrmLead, $this>
     */
    public function crmLead(): HasOne
    {
        return $this->hasOne(CrmLead::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function toAccountArray(): array
    {
        return [
            'id' => $this->id,
            'company_name' => $this->company_name,
            'contact_person' => $this->contact_person,
            'mobile_number' => $this->mobile_number,
            'email' => $this->email,
            'industry' => $this->industry,
            'project_location' => $this->project_location,
            'workforce_type' => $this->workforce_type,
            'number_of_people' => $this->number_of_people,
            'fulfilled_joinings_count' => (int) ($this->fulfilled_joinings_count ?? 0),
            'vacancies_remaining' => max(0, (int) $this->number_of_people - (int) ($this->fulfilled_joinings_count ?? 0)),
            'required_skills' => $this->required_skills,
            'expected_start_date' => $this->expected_start_date?->toDateString(),
            'training_required' => $this->training_required,
            'additional_requirements' => $this->additional_requirements,
            'status' => $this->status->toEmployerLabel(),
            'status_value' => $this->status->value,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
            'detail_href' => route('account.enquiries.show', $this, false),
        ];
    }

    protected static function newFactory(): EmployerEnquiryFactory
    {
        return EmployerEnquiryFactory::new();
    }

    /**
     * @return list<array{body: string, author_id: int|null, author_name: string, created_at: string}>
     */
    public function notes(): array
    {
        $notes = $this->internal_notes;

        return is_array($notes) ? array_values($notes) : [];
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminArray(): array
    {
        return [
            'id' => $this->id,
            'company_name' => $this->company_name,
            'contact_person' => $this->contact_person,
            'mobile_number' => $this->mobile_number,
            'email' => $this->email,
            'industry' => $this->industry,
            'project_location' => $this->project_location,
            'workforce_type' => $this->workforce_type,
            'number_of_people' => $this->number_of_people,
            'required_skills' => $this->required_skills,
            'expected_start_date' => $this->expected_start_date?->toDateString(),
            'training_required' => $this->training_required,
            'additional_requirements' => $this->additional_requirements,
            'status' => $this->status->value,
            'internal_notes' => $this->notes(),
            'created_at' => $this->created_at?->toDateTimeString(),
            'call_url' => 'tel:'.$this->mobile_number,
            'whatsapp_url' => 'https://wa.me/'.preg_replace('/\D+/', '', $this->mobile_number),
            'email_url' => 'mailto:'.$this->email,
            'crm_lead_id' => $this->crmLead?->id,
            'crm_workspace_url' => $this->crmLead
                ? route('admin.crm.index', ['lead' => $this->crmLead->id], false)
                : null,
        ];
    }
}
