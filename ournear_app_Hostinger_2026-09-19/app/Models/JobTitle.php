<?php

namespace App\Models;

use App\Models\Concerns\HasMasterRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobTitle extends Model
{
    use HasMasterRecord;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'job_category_id',
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
     * @return BelongsTo<JobCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(JobCategory::class, 'job_category_id');
    }

    /**
     * @return HasMany<Job, $this>
     */
    public function jobs(): HasMany
    {
        return $this->hasMany(Job::class);
    }

    public function isSelectable(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $this->loadMissing('category.industry');

        return $this->category?->isSelectable() === true;
    }
}
