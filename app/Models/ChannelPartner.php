<?php

namespace App\Models;

use App\Enums\BusinessIdPrefix;
use App\Enums\ChannelPartnerStatus;
use App\Enums\PartnerAgreementStatus;
use App\Models\Concerns\HasBusinessId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class ChannelPartner extends Model
{
    use HasBusinessId;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'status',
        'display_name',
        'contact_person',
        'email',
        'mobile',
        'notes',
        'approved_at',
        'approved_by',
        'suspended_at',
        'suspended_by',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ChannelPartnerStatus::class,
            'approved_at' => 'datetime',
            'suspended_at' => 'datetime',
        ];
    }

    public static function businessIdPrefix(): BusinessIdPrefix
    {
        return BusinessIdPrefix::ChannelPartner;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * @return HasMany<ChannelPartnerAgreement, $this>
     */
    public function agreements(): HasMany
    {
        return $this->hasMany(ChannelPartnerAgreement::class);
    }

    /**
     * @return HasOne<ChannelPartnerAgreement, $this>
     */
    public function activeAgreement(): HasOne
    {
        return $this->hasOne(ChannelPartnerAgreement::class)
            ->where('status', PartnerAgreementStatus::Active->value)
            ->latestOfMany();
    }

    /**
     * @return HasMany<EmployerAttribution, $this>
     */
    public function attributions(): HasMany
    {
        return $this->hasMany(EmployerAttribution::class);
    }

    /**
     * @return HasMany<CommissionEntry, $this>
     */
    public function commissionEntries(): HasMany
    {
        return $this->hasMany(CommissionEntry::class);
    }

    /**
     * @return HasMany<CommissionPayment, $this>
     */
    public function commissionPayments(): HasMany
    {
        return $this->hasMany(CommissionPayment::class);
    }

    /**
     * @return HasMany<CrmLead, $this>
     */
    public function leads(): HasMany
    {
        return $this->hasMany(CrmLead::class);
    }

    /**
     * @return MorphToMany<Tag, $this>
     */
    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable')->withTimestamps();
    }

    public function isActive(): bool
    {
        return $this->status === ChannelPartnerStatus::Active;
    }

    /**
     * @return array<string, mixed>
     */
    public function toSelectArray(): array
    {
        return [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'display_name' => $this->display_name,
            'status' => $this->status->value,
            'label' => ($this->business_id ? $this->business_id.' · ' : '').$this->display_name,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminArray(): array
    {
        $this->loadMissing([
            'user:id,name,email,role,account_status',
            'creator:id,name',
            'approver:id,name',
            'activeAgreement',
            'agreements',
            'attributions.employer:id,business_id,company_name',
        ]);

        return [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'user_id' => $this->user_id,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'display_name' => $this->display_name,
            'contact_person' => $this->contact_person,
            'email' => $this->email,
            'mobile' => $this->mobile,
            'notes' => $this->notes,
            'approved_at' => $this->approved_at?->toDateTimeString(),
            'suspended_at' => $this->suspended_at?->toDateTimeString(),
            'created_by' => $this->created_by,
            'creator_name' => $this->creator?->name,
            'approver_name' => $this->approver?->name,
            'user' => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
                'account_type' => $this->user->role instanceof \App\Enums\UserRole
                    ? $this->user->role->value
                    : (string) $this->user->role,
            ] : null,
            'active_agreement' => $this->activeAgreement?->toAdminArray(),
            'agreement_count' => $this->agreements->count(),
            'attribution_count' => $this->attributions->count(),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
