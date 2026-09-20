<?php

namespace App\Models;

use App\Enums\BusinessIdPrefix;
use App\Models\Concerns\HasBusinessId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployerProfile extends Model
{
    use HasBusinessId;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'company_name',
        'contact_person',
        'mobile_number',
        'industry',
        'industry_id',
    ];

    public static function businessIdPrefix(): BusinessIdPrefix
    {
        return BusinessIdPrefix::Employer;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Industry, $this>
     */
    public function classifiedIndustry(): BelongsTo
    {
        return $this->belongsTo(Industry::class, 'industry_id');
    }

    /**
     * @return HasMany<Job, $this>
     */
    public function jobs(): HasMany
    {
        return $this->hasMany(Job::class, 'employer_id');
    }

    /**
     * @return HasMany<EmployerSubmission, $this>
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(EmployerSubmission::class);
    }

    /**
     * @return HasMany<Placement, $this>
     */
    public function placements(): HasMany
    {
        return $this->hasMany(Placement::class);
    }

    /**
     * @return HasMany<Employee, $this>
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * @return HasMany<EmployerEnquiry, $this>
     */
    public function enquiries(): HasMany
    {
        return $this->hasMany(EmployerEnquiry::class, 'user_id', 'user_id');
    }

    /**
     * @return array<string, mixed>
     */
    public function toAccountArray(): array
    {
        return [
            'company_name' => $this->company_name,
            'contact_person' => $this->contact_person,
            'mobile_number' => $this->mobile_number,
            'industry' => $this->industry,
        ];
    }

    public function displayIndustry(): string
    {
        return $this->classifiedIndustry?->name ?: (string) ($this->industry ?: '');
    }

    public function displayCompany(): string
    {
        return filled($this->company_name) ? (string) $this->company_name : 'Unnamed employer';
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminSummary(): array
    {
        $this->loadMissing('user');

        return [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'company_name' => $this->displayCompany(),
            'contact_person' => $this->contact_person ?: $this->user?->name,
            'email' => $this->user?->email,
            'mobile_number' => $this->mobile_number,
            'workspace_url' => route('admin.employers.index', ['employer' => $this->id], false),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toSelectArray(): array
    {
        $summary = $this->toAdminSummary();

        return [
            'id' => $summary['id'],
            'business_id' => $summary['business_id'],
            'company_name' => $summary['company_name'],
            'contact_person' => $summary['contact_person'],
            'email' => $summary['email'],
            'label' => trim(implode(' · ', array_filter([
                $summary['business_id'],
                $summary['company_name'],
                $summary['contact_person'],
            ]))),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminArray(): array
    {
        $this->loadMissing(['user', 'classifiedIndustry']);

        return [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'company_name' => $this->displayCompany(),
            'contact_person' => $this->contact_person ?: $this->user?->name,
            'email' => $this->user?->email,
            'mobile_number' => $this->mobile_number,
            'industry' => $this->displayIndustry() ?: '—',
            'industry_id' => $this->industry_id,
            'jobs_count' => $this->jobs_count ?? $this->jobs()->count(),
            'submissions_count' => $this->submissions_count ?? $this->submissions()->count(),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
            'workspace_url' => route('admin.employers.index', ['employer' => $this->id], false),
        ];
    }
}
