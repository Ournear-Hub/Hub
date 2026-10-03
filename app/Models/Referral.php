<?php

namespace App\Models;

use App\Enums\BusinessIdPrefix;
use App\Enums\ReferralConversionType;
use App\Enums\RegistrationSource;
use App\Models\Concerns\HasBusinessId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Referral extends Model
{
    use HasBusinessId;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'conversion_type',
        'is_primary',
        'referral_link_id',
        'campaign_id',
        'referrer_user_id',
        'referred_user_id',
        'application_id',
        'job_id',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'conversion_type' => ReferralConversionType::class,
            'is_primary' => 'boolean',
        ];
    }

    public static function businessIdPrefix(): BusinessIdPrefix
    {
        return BusinessIdPrefix::Referral;
    }

    public function registrationSource(): RegistrationSource
    {
        return $this->campaign_id
            ? RegistrationSource::Campaign
            : RegistrationSource::Referral;
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
    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function referredUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_user_id');
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
     * @return array<string, mixed>
     */
    public function toAdminArray(): array
    {
        $this->loadMissing([
            'referralLink:id,code,path',
            'campaign:id,name',
            'referrer:id,name',
            'referredUser:id,name,email,role,registration_source',
            'application:id,business_id,full_name,job_id',
            'job:id,title',
        ]);

        return [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'conversion_type' => $this->conversion_type->value,
            'conversion_label' => $this->conversion_type->label(),
            'is_primary' => $this->is_primary,
            'code' => $this->referralLink?->code,
            'campaign_name' => $this->campaign?->name,
            'referrer_name' => $this->referrer?->name,
            'referred_name' => $this->referredUser?->name,
            'referred_email' => $this->referredUser?->email,
            'registration_source' => $this->referredUser?->registration_source instanceof RegistrationSource
                ? $this->referredUser->registration_source->value
                : null,
            'application' => $this->application ? [
                'id' => $this->application->id,
                'full_name' => $this->application->full_name,
            ] : null,
            'job_title' => $this->job?->title,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
