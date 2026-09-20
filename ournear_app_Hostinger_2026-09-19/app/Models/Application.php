<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use App\Enums\BusinessIdPrefix;
use App\Models\Concerns\HasBusinessId;
use Database\Factories\ApplicationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Application extends Model
{
    /** @use HasFactory<ApplicationFactory> */
    use HasBusinessId, HasFactory;

    protected static function newFactory(): ApplicationFactory
    {
        return ApplicationFactory::new();
    }

    /**
     * @var list<string>
     */
    protected $fillable = [
        'job_id',
        'user_id',
        'full_name',
        'mobile',
        'email',
        'current_location',
        'experience',
        'qualification',
        'resume_path',
        'additional_information',
        'consent',
        'status',
        'internal_notes',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'resume_path',
        'internal_notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'consent' => 'boolean',
            'status' => ApplicationStatus::class,
            'internal_notes' => 'array',
        ];
    }

    public static function businessIdPrefix(): BusinessIdPrefix
    {
        return BusinessIdPrefix::Application;
    }

    /**
     * @return BelongsTo<Job, $this>
     */
    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    /**
     * @return BelongsTo<Location, $this>
     */
    public function classifiedLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    /**
     * @return BelongsTo<ExperienceRange, $this>
     */
    public function classifiedExperienceRange(): BelongsTo
    {
        return $this->belongsTo(ExperienceRange::class, 'experience_range_id');
    }

    /**
     * @return BelongsTo<Qualification, $this>
     */
    public function classifiedQualification(): BelongsTo
    {
        return $this->belongsTo(Qualification::class, 'qualification_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasOne<ApplicationInterview, $this>
     */
    public function interview(): HasOne
    {
        return $this->hasOne(ApplicationInterview::class);
    }

    /**
     * @return HasMany<RecruitmentActivity, $this>
     */
    public function activities(): HasMany
    {
        return $this->hasMany(RecruitmentActivity::class)->orderBy('created_at');
    }

    /**
     * @return array<string, mixed>
     */
    public function toAccountArray(): array
    {
        $this->loadMissing('job');

        return [
            'id' => $this->id,
            'applied_at' => $this->created_at?->toDateTimeString(),
            'has_resume' => filled($this->getRawOriginal('resume_path') ?? $this->attributes['resume_path'] ?? null),
            'resume_url' => route('account.applications.resume', $this),
            'status' => $this->status->toCandidateLabel(),
            'can_withdraw' => $this->candidateCanWithdraw(),
            'withdraw_requires_confirmation' => $this->status === ApplicationStatus::Selected,
            'job' => $this->job ? [
                'title' => $this->job->title,
                'slug' => $this->job->slug,
                'location' => $this->job->location,
            ] : null,
        ];
    }

    public function candidateCanWithdraw(): bool
    {
        return $this->user_id !== null && in_array($this->status, [
            ApplicationStatus::New,
            ApplicationStatus::Reviewing,
            ApplicationStatus::Shortlisted,
            ApplicationStatus::Interview,
            ApplicationStatus::Selected,
        ], true);
    }

    /**
     * @return list<array{body: string, author_id: int|null, author_name: string, created_at: string}>
     */
    public function notes(): array
    {
        $notes = $this->internal_notes;

        return is_array($notes) ? array_values($notes) : [];
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminListArray(): array
    {
        $this->loadMissing('job');

        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'mobile' => $this->mobile,
            'email' => $this->email,
            'current_location' => $this->current_location,
            'status' => $this->status->value,
            'applied_at' => $this->created_at?->toDateTimeString(),
            'job' => $this->job ? [
                'id' => $this->job->id,
                'title' => $this->job->title,
                'industry' => $this->job->industry,
                'location' => $this->job->location,
            ] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminArray(bool $detailed = false): array
    {
        $this->loadMissing('job');

        $payload = [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'mobile' => $this->mobile,
            'email' => $this->email,
            'current_location' => $this->current_location,
            'experience' => $this->experience,
            'qualification' => $this->qualification,
            'additional_information' => $this->additional_information,
            'consent' => $this->consent,
            'status' => $this->status->value,
            'applied_at' => $this->created_at?->toDateTimeString(),
            'has_resume' => filled($this->getRawOriginal('resume_path') ?? $this->resume_path),
            'resume_url' => route('admin.applications.resume', $this),
            'job' => $this->job ? [
                'id' => $this->job->id,
                'title' => $this->job->title,
                'slug' => $this->job->slug,
                'industry' => $this->job->industry,
                'location' => $this->job->location,
            ] : null,
        ];

        if ($detailed) {
            $this->loadMissing(['interview', 'activities.actor']);
            $payload['internal_notes'] = $this->notes();
            $payload['interview'] = $this->interview?->toAdminArray();
            $payload['activities'] = $this->activities
                ->map(fn (RecruitmentActivity $activity) => $activity->toAdminArray())
                ->values()
                ->all();
        }

        return $payload;
    }
}
