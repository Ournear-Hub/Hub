<?php

namespace App\Models;

use App\Models\Concerns\HasMasterRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Industry extends Model
{
    use HasMasterRecord;

    /**
     * @var list<string>
     */
    protected $fillable = [
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
     * @return HasMany<JobCategory, $this>
     */
    public function categories(): HasMany
    {
        return $this->hasMany(JobCategory::class);
    }

    /**
     * @return HasMany<Job, $this>
     */
    public function jobs(): HasMany
    {
        return $this->hasMany(Job::class);
    }

    /**
     * @return HasMany<EmployerProfile, $this>
     */
    public function employerProfiles(): HasMany
    {
        return $this->hasMany(EmployerProfile::class);
    }

    /**
     * @return HasMany<EmployerEnquiry, $this>
     */
    public function employerEnquiries(): HasMany
    {
        return $this->hasMany(EmployerEnquiry::class);
    }

    public function isSelectable(): bool
    {
        return $this->is_active;
    }
}
