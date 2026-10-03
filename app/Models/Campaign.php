<?php

namespace App\Models;

use App\Enums\BusinessIdPrefix;
use App\Enums\CampaignStatus;
use App\Models\Concerns\HasBusinessId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Campaign extends Model
{
    use HasBusinessId;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'status',
        'name',
        'summary',
        'notes',
        'starts_at',
        'ends_at',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => CampaignStatus::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public static function businessIdPrefix(): BusinessIdPrefix
    {
        return BusinessIdPrefix::Campaign;
    }

    public function isUsable(): bool
    {
        if ($this->status !== CampaignStatus::Active) {
            return false;
        }

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }

        if ($this->ends_at && $this->ends_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<ReferralLink, $this>
     */
    public function links(): HasMany
    {
        return $this->hasMany(ReferralLink::class);
    }

    /**
     * @return HasMany<ShareEvent, $this>
     */
    public function shareEvents(): HasMany
    {
        return $this->hasMany(ShareEvent::class);
    }

    /**
     * @return HasMany<Referral, $this>
     */
    public function referrals(): HasMany
    {
        return $this->hasMany(Referral::class);
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
    public function toSelectArray(): array
    {
        return [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'name' => $this->name,
            'status' => $this->status->value,
            'label' => ($this->business_id ? $this->business_id.' · ' : '').$this->name,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminArray(): array
    {
        $this->loadMissing(['creator:id,name']);
        $this->loadCount(['links', 'shareEvents', 'referrals']);

        return [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'name' => $this->name,
            'summary' => $this->summary,
            'notes' => $this->notes,
            'starts_at' => $this->starts_at?->toDateTimeString(),
            'ends_at' => $this->ends_at?->toDateTimeString(),
            'created_by' => $this->created_by,
            'creator_name' => $this->creator?->name,
            'link_count' => $this->links_count ?? 0,
            'share_count' => $this->share_events_count ?? 0,
            'referral_count' => $this->referrals_count ?? 0,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
