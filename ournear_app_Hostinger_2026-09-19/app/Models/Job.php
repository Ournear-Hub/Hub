<?php

namespace App\Models;

use App\Enums\BusinessIdPrefix;
use App\Enums\JobPublishingStatus;
use App\Models\Concerns\HasBusinessId;
use Database\Factories\JobFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Job extends Model
{
    /** @use HasFactory<JobFactory> */
    use HasBusinessId, HasFactory;

    protected static function newFactory(): JobFactory
    {
        return JobFactory::new();
    }

    /**
     * Laravel's queue already occupies the `jobs` table.
     */
    protected $table = 'job_listings';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'slug',
        'project',
        'industry',
        'category',
        'location',
        'employment_type',
        'experience',
        'industry_id',
        'job_category_id',
        'job_title_id',
        'location_id',
        'employment_type_id',
        'experience_range_id',
        'employer_id',
        'salary',
        'vacancy',
        'posted_date',
        'closing_date',
        'featured',
        'active',
        'publishing_status',
        'description',
        'responsibilities',
        'requirements',
        'what_we_offer',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'featured' => 'boolean',
            'active' => 'boolean',
            'vacancy' => 'integer',
            'posted_date' => 'date',
            'closing_date' => 'date',
            'publishing_status' => JobPublishingStatus::class,
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected static function booted(): void
    {
        static::creating(function (Job $job): void {
            if (blank($job->slug)) {
                $job->slug = static::uniqueSlug($job->title);
            }
        });
    }

    public static function businessIdPrefix(): BusinessIdPrefix
    {
        return BusinessIdPrefix::Job;
    }

    /**
     * @return HasMany<Application, $this>
     */
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    /**
     * @return BelongsTo<EmployerProfile, $this>
     */
    public function employer(): BelongsTo
    {
        return $this->belongsTo(EmployerProfile::class, 'employer_id');
    }

    /**
     * @return HasMany<EmployerSubmission, $this>
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(EmployerSubmission::class);
    }

    /**
     * @return BelongsTo<Industry, $this>
     */
    public function classifiedIndustry(): BelongsTo
    {
        return $this->belongsTo(Industry::class, 'industry_id');
    }

    /**
     * @return BelongsTo<JobCategory, $this>
     */
    public function classifiedCategory(): BelongsTo
    {
        return $this->belongsTo(JobCategory::class, 'job_category_id');
    }

    /**
     * @return BelongsTo<JobTitle, $this>
     */
    public function classifiedTitle(): BelongsTo
    {
        return $this->belongsTo(JobTitle::class, 'job_title_id');
    }

    /**
     * @return BelongsTo<Location, $this>
     */
    public function classifiedLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    /**
     * @return BelongsTo<EmploymentType, $this>
     */
    public function classifiedEmploymentType(): BelongsTo
    {
        return $this->belongsTo(EmploymentType::class, 'employment_type_id');
    }

    /**
     * @return BelongsTo<ExperienceRange, $this>
     */
    public function classifiedExperienceRange(): BelongsTo
    {
        return $this->belongsTo(ExperienceRange::class, 'experience_range_id');
    }

    /**
     * @param  Builder<Job>  $query
     * @return Builder<Job>
     */
    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query
            ->where('active', true)
            ->where('publishing_status', JobPublishingStatus::Published)
            ->where(function (Builder $closing) {
                $closing->whereNull('closing_date')
                    ->orWhereDate('closing_date', '>=', now()->toDateString());
            });
    }

    public function isPubliclyVisible(): bool
    {
        if (! $this->active) {
            return false;
        }

        if ($this->publishing_status !== JobPublishingStatus::Published) {
            return false;
        }

        return ! $this->isClosed();
    }

    public function isClosed(): bool
    {
        if ($this->closing_date === null) {
            return false;
        }

        return $this->closing_date->toDateString() < now()->toDateString();
    }

    public function acceptsApplications(): bool
    {
        return $this->isPubliclyVisible();
    }

    public static function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'job';
        $slug = $base;
        $suffix = 2;

        while (static::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn (Builder $query) => $query->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    /**
     * @return array<string, mixed>
     */
    public function toPublicCard(): array
    {
        return [
            'title' => $this->title,
            'slug' => $this->slug,
            'project' => $this->project,
            'industry' => $this->industry,
            'category' => $this->category,
            'location' => $this->location,
            'employment_type' => $this->employment_type,
            'experience' => $this->experience,
            'salary' => $this->salary,
            'vacancy' => $this->vacancy,
            'posted_date' => $this->posted_date?->toDateString(),
            'closing_date' => $this->closing_date?->toDateString(),
            'featured' => $this->featured,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toPublicDetail(): array
    {
        return [
            ...$this->toPublicCard(),
            'description' => $this->description,
            'responsibilities' => $this->responsibilities,
            'requirements' => $this->requirements,
            'what_we_offer' => $this->what_we_offer,
            'accepts_applications' => $this->acceptsApplications(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminArray(): array
    {
        $this->loadMissing([
            'classifiedIndustry',
            'classifiedCategory',
            'classifiedTitle',
            'classifiedLocation',
            'classifiedEmploymentType',
            'classifiedExperienceRange',
            'employer.user',
        ]);

        return [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'title' => $this->title,
            'slug' => $this->slug,
            'project' => $this->project,
            'industry' => $this->displayIndustry(),
            'category' => $this->displayCategory(),
            'legacy_category' => $this->job_category_id ? null : $this->category,
            'location' => $this->displayLocation(),
            'employment_type' => $this->displayEmploymentType(),
            'experience' => $this->displayExperience(),
            'classified_title' => $this->classifiedTitle?->name,
            'industry_id' => $this->industry_id,
            'job_category_id' => $this->job_category_id,
            'job_title_id' => $this->job_title_id,
            'location_id' => $this->location_id,
            'employment_type_id' => $this->employment_type_id,
            'experience_range_id' => $this->experience_range_id,
            'employer_id' => $this->employer_id,
            'employer' => $this->employer?->toAdminSummary(),
            'salary' => $this->salary,
            'vacancy' => $this->vacancy,
            'posted_date' => $this->posted_date?->toDateString(),
            'closing_date' => $this->closing_date?->toDateString(),
            'featured' => $this->featured,
            'active' => $this->active,
            'publishing_status' => $this->publishing_status->value,
            'status_label' => $this->active
                ? ($this->publishing_status === JobPublishingStatus::Published ? 'Published' : 'Draft')
                : 'Inactive',
            'description' => $this->description,
            'responsibilities' => $this->responsibilities,
            'requirements' => $this->requirements,
            'what_we_offer' => $this->what_we_offer,
            'applications_count' => $this->applications_count ?? $this->applications()->count(),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }

    public function displayIndustry(): string
    {
        return $this->classifiedIndustry?->name ?: (string) $this->industry;
    }

    public function displayCategory(): ?string
    {
        return $this->classifiedCategory?->name;
    }

    public function displayLocation(): string
    {
        return $this->classifiedLocation?->name ?: (string) $this->location;
    }

    public function displayEmploymentType(): string
    {
        return $this->classifiedEmploymentType?->name ?: (string) $this->employment_type;
    }

    public function displayExperience(): string
    {
        return $this->classifiedExperienceRange?->name ?: (string) $this->experience;
    }
}
