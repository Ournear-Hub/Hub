<?php

namespace App\Models;

use App\Models\Concerns\HasMasterRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobCategory extends Model
{
    use HasMasterRecord;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'industry_id',
        'name',
        'description',
        'is_active',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Industry, $this>
     */
    public function industry(): BelongsTo
    {
        return $this->belongsTo(Industry::class);
    }

    /**
     * @return HasMany<JobTitle, $this>
     */
    public function jobTitles(): HasMany
    {
        return $this->hasMany(JobTitle::class);
    }

    /**
     * @return HasMany<Job, $this>
     */
    public function jobs(): HasMany
    {
        return $this->hasMany(Job::class, 'job_category_id');
    }

    public function isSelectable(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $this->loadMissing('industry');

        return $this->industry?->isSelectable() === true;
    }
}
