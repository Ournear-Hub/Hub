<?php

namespace App\Models;

use App\Enums\BusinessIdPrefix;
use App\Enums\FreelancerGender;
use App\Enums\FreelancerStatus;
use App\Enums\NoticePeriod;
use App\Enums\PreferredWorkType;
use App\Models\Concerns\HasBusinessId;
use Database\Factories\FreelancerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

class Freelancer extends Model
{
    /** @use HasFactory<FreelancerFactory> */
    use HasBusinessId, HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'business_id',
        'submission_token',
        'name',
        'mobile',
        'email',
        'gender',
        'date_of_birth',
        'current_location_id',
        'primary_skill',
        'current_designation',
        'current_company',
        'industry_id',
        'job_category_id',
        'experience_range_id',
        'employment_type_id',
        'preferred_work_type',
        'expected_rate',
        'notice_period',
        'availability',
        'skills',
        'summary',
        'resume_disk',
        'resume_path',
        'resume_original_name',
        'resume_mime',
        'resume_size',
        'certificate_disk',
        'certificate_path',
        'certificate_original_name',
        'certificate_mime',
        'certificate_size',
        'consent',
        'status',
        'status_history',
        'submitted_at',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'submission_token',
        'resume_disk',
        'resume_path',
        'certificate_disk',
        'certificate_path',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'gender' => FreelancerGender::class,
            'date_of_birth' => 'date',
            'preferred_work_type' => PreferredWorkType::class,
            'notice_period' => NoticePeriod::class,
            'skills' => 'array',
            'consent' => 'boolean',
            'status' => FreelancerStatus::class,
            'status_history' => 'array',
            'submitted_at' => 'datetime',
            'resume_size' => 'integer',
            'certificate_size' => 'integer',
        ];
    }

    public static function businessIdPrefix(): BusinessIdPrefix
    {
        return BusinessIdPrefix::Freelancer;
    }

    /**
     * @return BelongsTo<Location, $this>
     */
    public function currentLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'current_location_id');
    }

    /**
     * @return BelongsToMany<Location, $this>
     */
    public function preferredLocations(): BelongsToMany
    {
        return $this->belongsToMany(Location::class, 'freelancer_preferred_location');
    }

    /**
     * @return BelongsTo<Industry, $this>
     */
    public function industry(): BelongsTo
    {
        return $this->belongsTo(Industry::class);
    }

    /**
     * @return BelongsTo<JobCategory, $this>
     */
    public function jobCategory(): BelongsTo
    {
        return $this->belongsTo(JobCategory::class);
    }

    /**
     * @return BelongsTo<ExperienceRange, $this>
     */
    public function experienceRange(): BelongsTo
    {
        return $this->belongsTo(ExperienceRange::class);
    }

    /**
     * @return BelongsTo<EmploymentType, $this>
     */
    public function employmentType(): BelongsTo
    {
        return $this->belongsTo(EmploymentType::class);
    }

    /**
     * @return list<string>
     */
    public function skillList(): array
    {
        $skills = $this->skills;

        return is_array($skills)
            ? array_values(array_filter(array_map(fn ($skill) => trim((string) $skill), $skills)))
            : [];
    }

    public function preferredLocationLabel(): string
    {
        $names = $this->preferredLocations->pluck('name')->filter()->values();

        return $names->isNotEmpty() ? $names->implode(', ') : '—';
    }

    /**
     * @return list<array{from: string, to: string, actor_id: int|null, actor_name: string|null, created_at: string}>
     */
    public function history(): array
    {
        $history = $this->status_history;

        return is_array($history) ? array_values($history) : [];
    }

    /**
     * @return array<string, mixed>
     */
    public function toPublicCard(): array
    {
        $this->loadMissing('industry');

        return [
            'name' => $this->name,
            'business_id' => $this->business_id,
            'status' => $this->status === FreelancerStatus::Converted ? 'Approved' : 'Pending',
            'joined_at' => ($this->submitted_at ?? $this->created_at)?->format('M Y'),
            'works_for' => $this->worksForLabel(),
        ];
    }

    public function worksForLabel(): string
    {
        $this->loadMissing('industry');

        return $this->industry?->name ?: (string) ($this->primary_skill ?: 'OurNear partnership');
    }

    public function toConfirmationArray(): array
    {
        $this->loadMissing('preferredLocations');

        return [
            'business_id' => $this->business_id,
            'name' => $this->name,
            'email' => $this->email,
            'preferred_location' => $this->preferredLocationLabel(),
            'primary_skill' => $this->primary_skill,
            'submitted_at' => $this->submitted_at?->toDateTimeString() ?? $this->created_at?->toDateTimeString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminListArray(): array
    {
        $this->loadMissing(['currentLocation', 'experienceRange', 'industry']);

        return [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'name' => $this->name,
            'mobile' => $this->mobile,
            'email' => $this->email,
            'primary_skill' => $this->primary_skill,
            'location' => $this->currentLocation?->name,
            'industry' => $this->industry?->name,
            'experience' => $this->experienceRange?->name,
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'submitted_at' => $this->submitted_at?->toDateTimeString() ?? $this->created_at?->toDateTimeString(),
        ];
    }

    /**
     * @param  Collection<int, User>  $matches
     * @return array<string, mixed>
     */
    public function toAdminDetailArray(Collection $matches = new Collection): array
    {
        $this->loadMissing([
            'currentLocation',
            'preferredLocations',
            'industry',
            'jobCategory',
            'experienceRange',
            'employmentType',
        ]);

        return [
            ...$this->toAdminListArray(),
            'gender' => $this->gender?->label(),
            'date_of_birth' => $this->date_of_birth?->toDateString(),
            'current_designation' => $this->current_designation,
            'current_company' => $this->current_company,
            'industry' => $this->industry?->name,
            'functional_area' => $this->jobCategory?->name,
            'preferred_locations' => $this->preferredLocations->pluck('name')->values()->all(),
            'preferred_work_type' => $this->preferred_work_type?->label(),
            'employment_type' => $this->employmentType?->name,
            'expected_rate' => $this->expected_rate,
            'notice_period' => $this->notice_period?->label(),
            'availability' => $this->availability,
            'skills' => $this->skillList(),
            'summary' => $this->summary,
            'consent' => $this->consent,
            'has_resume' => filled($this->getAttributes()['resume_path'] ?? null),
            'resume_name' => $this->resume_original_name,
            'has_certificate' => filled($this->getAttributes()['certificate_path'] ?? null),
            'certificate_name' => $this->certificate_original_name,
            'status_history' => $this->history(),
            'possible_matches' => $matches->map(fn (User $user) => [
                'business_id' => $user->business_id,
                'name' => $user->name,
                'role' => $user->role->value,
            ])->values()->all(),
            'resume_url' => route('admin.freelancers.resume', $this, false),
            'certificate_url' => route('admin.freelancers.certificate', $this, false),
        ];
    }

    protected static function newFactory(): FreelancerFactory
    {
        return FreelancerFactory::new();
    }
}
