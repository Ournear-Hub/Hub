<?php

namespace App\Models;

use App\Enums\BusinessIdPrefix;
use App\Enums\InvestmentOpportunityStatus;
use App\Models\Concerns\HasBusinessId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class InvestmentOpportunity extends Model
{
    use HasBusinessId;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'status',
        'title',
        'summary',
        'sector',
        'location',
        'indicated_size',
        'currency',
        'opens_at',
        'closes_at',
        'notes',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => InvestmentOpportunityStatus::class,
            'indicated_size' => 'decimal:2',
            'opens_at' => 'date',
            'closes_at' => 'date',
        ];
    }

    public static function businessIdPrefix(): BusinessIdPrefix
    {
        return BusinessIdPrefix::InvestmentOpportunity;
    }

    public function acceptsInterest(): bool
    {
        return ! in_array($this->status, [
            InvestmentOpportunityStatus::Draft,
            InvestmentOpportunityStatus::Closed,
        ], true);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
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

    /**
     * @return array<string, mixed>
     */
    public function toSelectArray(): array
    {
        return [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'title' => $this->title,
            'status' => $this->status->value,
            'label' => ($this->business_id ? $this->business_id.' · ' : '').$this->title,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminArray(): array
    {
        $this->loadMissing(['creator:id,name', 'interests']);

        return [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'title' => $this->title,
            'summary' => $this->summary,
            'sector' => $this->sector,
            'location' => $this->location,
            'indicated_size' => $this->indicated_size,
            'currency' => $this->currency,
            'opens_at' => $this->opens_at?->toDateString(),
            'closes_at' => $this->closes_at?->toDateString(),
            'notes' => $this->notes,
            'created_by' => $this->created_by,
            'creator_name' => $this->creator?->name,
            'interest_count' => $this->interests->count(),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
