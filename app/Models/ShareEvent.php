<?php

namespace App\Models;

use App\Enums\BusinessIdPrefix;
use App\Enums\ShareChannel;
use App\Models\Concerns\HasBusinessId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShareEvent extends Model
{
    use HasBusinessId;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'channel',
        'path',
        'job_id',
        'referral_link_id',
        'campaign_id',
        'user_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'channel' => ShareChannel::class,
        ];
    }

    public static function businessIdPrefix(): BusinessIdPrefix
    {
        return BusinessIdPrefix::ShareEvent;
    }

    /**
     * @return BelongsTo<Job, $this>
     */
    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    /**
     * @return BelongsTo<ReferralLink, $this>
     */
    public function referralLink(): BelongsTo
    {
        return $this->belongsTo(ReferralLink::class);
    }

    /**
     * @return BelongsTo<Campaign, $this>
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminArray(): array
    {
        $this->loadMissing([
            'job:id,business_id,title,slug',
            'referralLink:id,code,path',
            'campaign:id,name',
            'user:id,name',
        ]);

        return [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'channel' => $this->channel->value,
            'channel_label' => $this->channel->label(),
            'path' => $this->path,
            'job_id' => $this->job_id,
            'referral_link_id' => $this->referral_link_id,
            'campaign_id' => $this->campaign_id,
            'job' => $this->job ? [
                'id' => $this->job->id,
                'title' => $this->job->title,
            ] : null,
            'code' => $this->referralLink?->code,
            'campaign_name' => $this->campaign?->name,
            'user_name' => $this->user?->name,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
