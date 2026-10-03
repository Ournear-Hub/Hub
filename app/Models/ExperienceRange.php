<?php

namespace App\Models;

use App\Models\Concerns\HasMasterRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExperienceRange extends Model
{
    use HasMasterRecord;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'min_years',
        'max_years',
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
            'min_years' => 'integer',
            'max_years' => 'integer',
        ];
    }

    /**
     * @return HasMany<Job, $this>
     */
    public function jobs(): HasMany
    {
        return $this->hasMany(Job::class);
    }

    /**
     * @return HasMany<CandidateProfile, $this>
     */
    public function candidateProfiles(): HasMany
    {
        return $this->hasMany(CandidateProfile::class);
    }

    /**
     * @return HasMany<Application, $this>
     */
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function isSelectable(): bool
    {
        return $this->is_active;
    }
}
