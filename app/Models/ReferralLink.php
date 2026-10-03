<?php

namespace App\Models;

use App\Enums\BusinessIdPrefix;
use App\Enums\ReferralLinkStatus;
use App\Models\Concerns\HasBusinessId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReferralLink extends Model
{
    use HasBusinessId;

    public const COOKIE_NAME = 'ourneer_ref';

    public const COOKIE_MINUTES = 10080;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'status',
        'path',
        'campaign_id',
        'job_id',
        'referrer_user_id',
        'created_by',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ReferralLinkStatus::class,
        ];
    }

    public static function businessIdPrefix(): BusinessIdPrefix
    {
        return BusinessIdPrefix::ReferralLink;
    }

    public function publicUrl(): string
    {
        $path = $this->path !== '' ? $this->path : '/';
        $separator = str_contains($path, '?') ? '&' : '?';

        return url($path.$separator.'ref='.$this->code);
    }

    public function isCapturable(): bool
    {
        if ($this->status !== ReferralLinkStatus::Active) {
            return false;
        }

        if ($this->campaign && ! $this->campaign->isUsable()) {
            return false;
        }

        return true;
    }

    /**
     * @return BelongsTo<Campaign, $this>
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * @return BelongsTo<Job, $this>
     */
    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
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
     * @return array<string, mixed>
     */
    public function toSelectArray(): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'label' => $this->code.' · '.$this->path,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminArray(): array
    {
        $this->loadMissing([
            'campaign:id,business_id,name,status',
            'job:id,business_id,title,slug',
            'referrer:id,name',
            'creator:id,name',
        ]);
        $this->loadCount(['shareEvents', 'referrals']);

        return [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'code' => $this->code,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'path' => $this->path,
            'public_url' => $this->publicUrl(),
            'campaign_id' => $this->campaign_id,
            'job_id' => $this->job_id,
            'referrer_user_id' => $this->referrer_user_id,
            'notes' => $this->notes,
            'share_count' => $this->share_events_count ?? 0,
            'referral_count' => $this->referrals_count ?? 0,
            'campaign' => $this->campaign ? [
                'id' => $this->campaign->id,
                'name' => $this->campaign->name,
            ] : null,
            'job' => $this->job ? [
                'id' => $this->job->id,
                'title' => $this->job->title,
            ] : null,
            'referrer_name' => $this->referrer?->name,
            'creator_name' => $this->creator?->name,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
