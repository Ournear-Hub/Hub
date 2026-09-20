<?php

namespace App\Models;

use App\Enums\JobPublishingStatus;
use Database\Factories\JobFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Job extends Model
{
    /** @use HasFactory<JobFactory> */
    use HasFactory;

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

    /**
     * @return HasMany<Application, $this>
     */
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
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
        return [
            'id' => $this->id,
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
            'active' => $this->active,
            'publishing_status' => $this->publishing_status->value,
            'description' => $this->description,
            'responsibilities' => $this->responsibilities,
            'requirements' => $this->requirements,
            'what_we_offer' => $this->what_we_offer,
            'applications_count' => $this->applications_count ?? $this->applications()->count(),
        ];
    }
}
