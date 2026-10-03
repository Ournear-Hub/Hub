<?php

namespace App\Models;

use App\Enums\BusinessIdPrefix;
use App\Enums\InvestorStatus;
use App\Models\Concerns\HasBusinessId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Investor extends Model
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
        'verified_at',
        'verified_by',
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
            'status' => InvestorStatus::class,
            'verified_at' => 'datetime',
            'suspended_at' => 'datetime',
        ];
    }

    public static function businessIdPrefix(): BusinessIdPrefix
    {
        return BusinessIdPrefix::Investor;
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
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * @return HasMany<InvestorInterest, $this>
     */
    public function interests(): HasMany
    {
        return $this->hasMany(InvestorInterest::class);
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
        return $this->status === InvestorStatus::Active;
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
            'verifier:id,name',
            'interests',
            'tags',
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
            'verified_at' => $this->verified_at?->toDateTimeString(),
            'suspended_at' => $this->suspended_at?->toDateTimeString(),
            'created_by' => $this->created_by,
            'creator_name' => $this->creator?->name,
            'verifier_name' => $this->verifier?->name,
            'interest_count' => $this->interests->count(),
            'user' => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
                'account_status' => $this->user->account_status instanceof \App\Enums\AccountStatus
                    ? $this->user->account_status->value
                    : (string) $this->user->account_status,
                'account_type' => $this->user->role instanceof \App\Enums\UserRole
                    ? $this->user->role->value
                    : (string) $this->user->role,
            ] : null,
            'tags' => $this->tags->map(fn (Tag $tag) => $tag->toAdminArray())->values()->all(),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
