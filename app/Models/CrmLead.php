<?php

namespace App\Models;

use App\Enums\CrmLeadSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class CrmLead extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_name',
        'contact_person',
        'email',
        'mobile',
        'website',
        'source',
        'notes',
        'employer_enquiry_id',
        'contact_enquiry_id',
        'employer_profile_id',
        'channel_partner_id',
        'assigned_to',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'source' => CrmLeadSource::class,
        ];
    }

    /**
     * @return BelongsTo<EmployerEnquiry, $this>
     */
    public function enquiry(): BelongsTo
    {
        return $this->belongsTo(EmployerEnquiry::class, 'employer_enquiry_id');
    }

    /**
     * @return BelongsTo<ContactEnquiry, $this>
     */
    public function contactEnquiry(): BelongsTo
    {
        return $this->belongsTo(ContactEnquiry::class, 'contact_enquiry_id');
    }

    /**
     * @return BelongsTo<EmployerProfile, $this>
     */
    public function employer(): BelongsTo
    {
        return $this->belongsTo(EmployerProfile::class, 'employer_profile_id');
    }

    /**
     * @return BelongsTo<ChannelPartner, $this>
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(ChannelPartner::class, 'channel_partner_id');
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
     * @return HasMany<CrmOpportunity, $this>
     */
    public function opportunities(): HasMany
    {
        return $this->hasMany(CrmOpportunity::class);
    }

    /**
     * @return MorphToMany<Tag, $this>
     */
    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable')->withTimestamps();
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminArray(): array
    {
        $this->loadMissing([
            'assignee:id,name',
            'creator:id,name',
            'employer:id,business_id,company_name,user_id',
            'enquiry:id,company_name,email',
            'partner:id,business_id,display_name,status',
            'opportunities',
            'tags',
        ]);

        return [
            'id' => $this->id,
            'company_name' => $this->company_name,
            'contact_person' => $this->contact_person,
            'email' => $this->email,
            'mobile' => $this->mobile,
            'website' => $this->website,
            'source' => $this->source->value,
            'source_label' => $this->source->label(),
            'notes' => $this->notes,
            'employer_enquiry_id' => $this->employer_enquiry_id,
            'contact_enquiry_id' => $this->contact_enquiry_id,
            'employer_profile_id' => $this->employer_profile_id,
            'channel_partner_id' => $this->channel_partner_id,
            'assigned_to' => $this->assigned_to,
            'assignee_name' => $this->assignee?->name,
            'created_by' => $this->created_by,
            'creator_name' => $this->creator?->name,
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
            'partner' => $this->partner ? [
                'id' => $this->partner->id,
                'business_id' => $this->partner->business_id,
                'display_name' => $this->partner->display_name,
                'workspace_url' => route('admin.partners.index', ['partner' => $this->partner->id], false),
            ] : null,
            'opportunities' => $this->opportunities
                ->map(fn (CrmOpportunity $opportunity) => $opportunity->toAdminSummary())
                ->values()
                ->all(),
            'tags' => $this->tags->map(fn (Tag $tag) => $tag->toAdminArray())->values()->all(),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
