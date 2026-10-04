<?php

namespace App\Models;

use App\Casts\RecoveredBackedEnumCast;
use App\Enums\BusinessIdPrefix;
use App\Enums\JobPublishingStatus;
use App\Models\Concerns\HasBusinessId;
use App\Services\UserPhotoService;
use Database\Factories\JobFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
        'joining_date',
        'site',
        'accommodation',
        'featured',
        'urgent',
        'active',
        'publishing_status',
        'description',
        'responsibilities',
        'requirements',
        'what_we_offer',
        'posting_tier',
        'trial_expires_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'featured' => 'boolean',
            'urgent' => 'boolean',
            'active' => 'boolean',
            'vacancy' => 'integer',
            'posted_date' => 'date',
            'closing_date' => 'date',
            'joining_date' => 'date',
            'publishing_status' => RecoveredBackedEnumCast::class.':'.JobPublishingStatus::class,
            'trial_expires_at' => 'datetime',
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
     * @return HasMany<SavedJob, $this>
     */
    public function savedJobs(): HasMany
    {
        return $this->hasMany(SavedJob::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function savedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'saved_jobs')->withTimestamps();
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
     * @return HasMany<Placement, $this>
     */
    public function placements(): HasMany
    {
        return $this->hasMany(Placement::class);
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
            ->where(function (Builder $trial) {
                $trial->where('posting_tier', '!=', 'trial')
                    ->orWhereNull('trial_expires_at')
                    ->orWhere('trial_expires_at', '>=', now());
            })
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

        if ($this->posting_tier === 'trial' && $this->trial_expires_at?->isPast()) {
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

    public function isOperatorClosed(): bool
    {
        return in_array($this->publishing_status, [
            JobPublishingStatus::Closed,
            JobPublishingStatus::Archived,
        ], true);
    }

    public function statusLabel(): string
    {
        return match ($this->publishing_status) {
            JobPublishingStatus::Published => $this->active ? 'Published' : 'Inactive',
            JobPublishingStatus::Draft => 'Draft',
            JobPublishingStatus::Paused => 'Paused',
            JobPublishingStatus::Closed => 'Closed',
            JobPublishingStatus::Archived => 'Archived',
        };
    }

    public function acceptsApplications(): bool
    {
        return $this->isPubliclyVisible();
    }

    public function postedLabel(): string
    {
        if ($this->posted_date === null) {
            return '—';
        }

        $days = (int) $this->posted_date->copy()->startOfDay()->diffInDays(now()->startOfDay());

        if ($days <= 0) {
            return 'Today';
        }

        if ($days === 1) {
            return '1 day ago';
        }

        if ($days < 7) {
            return $days.' days ago';
        }

        $weeks = intdiv($days, 7);

        return $weeks === 1 ? '1 week ago' : $weeks.' weeks ago';
    }

    public function closingLabel(): string
    {
        if ($this->isClosed()) {
            return 'Closed';
        }

        if ($this->closing_date === null) {
            return 'Open';
        }

        $days = (int) now()->startOfDay()->diffInDays($this->closing_date->copy()->startOfDay());

        if ($days <= 0) {
            return '1 day left';
        }

        if ($days === 1) {
            return '1 day left';
        }

        if ($days === 7) {
            return '1 week left';
        }

        if ($days % 7 === 0 && $days <= 28) {
            return intdiv($days, 7).' weeks left';
        }

        return $days.' days left';
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
        $this->loadMissing('employer.user');

        return [
            'title' => $this->title,
            'slug' => $this->slug,
            'project' => $this->project,
            'company_name' => $this->employer?->publicDisplayCompany() ?: $this->project,
            'company_logo_url' => app(UserPhotoService::class)->publicUrl($this->employer?->user?->photo_path),
            'company_url' => $this->employer && $this->employer->shareAllows('enabled')
                ? route('companies.show', $this->employer, false)
                : null,
            'industry' => $this->industry,
            'category' => $this->category,
            'location' => $this->location,
            'employment_type' => $this->employment_type,
            'experience' => $this->experience,
            'salary' => $this->salary,
            'vacancy' => $this->vacancy,
            'posted_date' => $this->posted_date?->toDateString(),
            'closing_date' => $this->closing_date?->toDateString(),
            'posted_label' => $this->postedLabel(),
            'closing_label' => $this->closingLabel(),
            'is_closed' => $this->isClosed(),
            'featured' => $this->featured,
            'urgent' => (bool) $this->urgent,
            'joining_date' => $this->joining_date?->toDateString(),
            'site' => $this->site,
            'accommodation' => $this->accommodation,
            'posting_tier' => $this->posting_tier,
            'trial_expires_at' => $this->trial_expires_at?->toIso8601String(),
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
            'joining_date' => $this->joining_date?->toDateString(),
            'site' => $this->site,
            'accommodation' => $this->accommodation,
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
            'joining_date' => $this->joining_date?->toDateString(),
            'site' => $this->site,
            'accommodation' => $this->accommodation,
            'featured' => $this->featured,
            'urgent' => (bool) $this->urgent,
            'posting_tier' => $this->posting_tier,
            'trial_expires_at' => $this->trial_expires_at?->toIso8601String(),
            'active' => $this->active,
            'publishing_status' => $this->publishing_status->value,
            'status_label' => $this->statusLabel(),
            'can_publish' => $this->publishing_status !== JobPublishingStatus::Published,
            'can_unpublish' => $this->publishing_status === JobPublishingStatus::Published,
            'can_pause' => $this->publishing_status === JobPublishingStatus::Published,
            'can_close' => in_array($this->publishing_status, [
                JobPublishingStatus::Published,
                JobPublishingStatus::Paused,
            ], true),
            'can_archive' => $this->publishing_status !== JobPublishingStatus::Archived,
            'description' => $this->description,
            'responsibilities' => $this->responsibilities,
            'requirements' => $this->requirements,
            'what_we_offer' => $this->what_we_offer,
            'applications_count' => $this->applications_count ?? $this->applications()->count(),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
            'skills' => null,
            'notice_period' => null,
            'keywords' => null,
            'qualification' => null,
            'created_by' => null,
            'source' => null,
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
