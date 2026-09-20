<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use App\Enums\BusinessIdPrefix;
use App\Enums\ScreeningDecision;
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
     * @return HasOne<ApplicationScreening, $this>
     */
    public function screening(): HasOne
    {
        return $this->hasOne(ApplicationScreening::class);
    }

    /**
     * @return HasMany<EmployerSubmission, $this>
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(EmployerSubmission::class);
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
        $this->loadMissing([
            'job.classifiedIndustry',
            'job.classifiedLocation',
            'job.employer.user',
            'user.candidateProfile',
            'screening',
            'classifiedLocation',
            'classifiedExperienceRange',
            'classifiedQualification',
        ]);

        $profile = $this->user?->candidateProfile;
        $submitBlockedReason = $this->submitBlockedReason();

        return [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'full_name' => $this->full_name,
            'mobile' => $this->mobile,
            'email' => $this->email,
            'current_location' => $this->displayLocation(),
            'experience' => $this->displayExperience(),
            'qualification' => $this->displayQualification(),
            'status' => $this->status->value,
            'applied_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
            'has_resume' => filled($this->getRawOriginal('resume_path') ?? $this->attributes['resume_path'] ?? null),
            'resume_url' => route('admin.applications.resume', $this),
            'screening_decision' => $this->screeningDecision()->value,
            'screening_decision_label' => $this->screeningDecision()->label(),
            'shortlisted_at' => $this->screening?->shortlisted_at?->toDateTimeString(),
            'can_submit_to_employer' => $submitBlockedReason === null,
            'submit_blocked_reason' => $submitBlockedReason,
            'candidate' => [
                'id' => $profile?->id,
                'business_id' => $profile?->business_id,
                'name' => $this->full_name,
            ],
            'job' => $this->job ? [
                'id' => $this->job->id,
                'business_id' => $this->job->business_id,
                'title' => $this->job->title,
                'slug' => $this->job->slug,
                'industry' => $this->job->displayIndustry(),
                'location' => $this->job->displayLocation(),
            ] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminArray(bool $detailed = false): array
    {
        $this->loadMissing([
            'user.candidateProfile.classifiedLocation',
            'user.candidateProfile.classifiedExperienceRange',
            'user.candidateProfile.classifiedQualification',
        ]);

        $profile = $this->user?->candidateProfile;
        $payload = $this->toAdminListArray();
        $payload['additional_information'] = $this->additional_information;
        $payload['consent'] = $this->consent;
        $payload['can_shortlist'] = $this->canShortlist();
        $payload['can_mark_suitable'] = $this->canMarkSuitable();
        $payload['can_mark_not_suitable'] = $this->canMarkNotSuitable();
        $payload['candidate'] = [
            'id' => $profile?->id,
            'business_id' => $profile?->business_id,
            'name' => $profile?->user?->name ?: $this->full_name,
            'mobile' => $profile?->mobile ?: $this->mobile,
            'email' => $profile?->user?->email ?: $this->email,
            'location' => $profile?->classifiedLocation?->name ?: ($profile?->location ?: $this->displayLocation()),
            'experience' => $profile?->classifiedExperienceRange?->name ?: ($profile?->experience ?: $this->displayExperience()),
            'qualification' => $profile?->classifiedQualification?->name ?: ($profile?->qualification ?: $this->displayQualification()),
            'workspace_url' => $profile ? route('admin.candidates.index', ['candidate' => $profile->id], false) : null,
        ];
        $payload['job'] = $this->jobSummary();

        if ($detailed) {
            $this->loadMissing(['interview', 'activities.actor', 'screening.rejectionReason', 'screening.screener', 'screening.shortlister']);
            $payload['internal_notes'] = $this->notes();
            $payload['interview'] = $this->interview?->toAdminArray();
            $payload['screening'] = $this->screening?->toAdminArray() ?? [
                'decision' => ScreeningDecision::Pending->value,
                'decision_label' => ScreeningDecision::Pending->label(),
                'notes' => null,
                'rejection_reason_id' => null,
                'rejection_reason' => null,
                'screened_by' => null,
                'screened_at' => null,
                'shortlisted_by' => null,
                'shortlisted_at' => null,
            ];
            $payload['activities'] = $this->activities
                ->map(fn (RecruitmentActivity $activity) => $activity->toAdminArray())
                ->values()
                ->all();
        }

        return $payload;
    }

    public function displayLocation(): string
    {
        return $this->classifiedLocation?->name ?: (string) $this->current_location;
    }

    public function displayExperience(): string
    {
        return $this->classifiedExperienceRange?->name ?: (string) $this->experience;
    }

    public function displayQualification(): string
    {
        return $this->classifiedQualification?->name ?: (string) $this->qualification;
    }

    public function screeningDecision(): ScreeningDecision
    {
        return $this->screening?->decision ?? ScreeningDecision::Pending;
    }

    public function canShortlist(): bool
    {
        if ($this->screeningDecision() === ScreeningDecision::Shortlisted) {
            return false;
        }

        return in_array($this->status, [
            ApplicationStatus::New,
            ApplicationStatus::Reviewing,
            ApplicationStatus::Shortlisted,
            ApplicationStatus::Interview,
        ], true);
    }

    public function canMarkSuitable(): bool
    {
        if ($this->screeningDecision() === ScreeningDecision::Shortlisted) {
            return false;
        }

        return in_array($this->status, [
            ApplicationStatus::New,
            ApplicationStatus::Reviewing,
        ], true);
    }

    public function canMarkNotSuitable(): bool
    {
        return in_array($this->status, [
            ApplicationStatus::New,
            ApplicationStatus::Reviewing,
            ApplicationStatus::Shortlisted,
            ApplicationStatus::Interview,
        ], true);
    }

    public function isEligibleForEmployerSubmission(): bool
    {
        if (in_array($this->status, [
            ApplicationStatus::New,
            ApplicationStatus::Reviewing,
            ApplicationStatus::Rejected,
            ApplicationStatus::Withdrawn,
            ApplicationStatus::Placed,
        ], true)) {
            return false;
        }

        if ($this->screeningDecision() === ScreeningDecision::NotSuitable) {
            return false;
        }

        return $this->status === ApplicationStatus::Shortlisted
            || $this->screeningDecision() === ScreeningDecision::Shortlisted;
    }

    public function submitBlockedReason(): ?string
    {
        if (! $this->isEligibleForEmployerSubmission()) {
            return 'Only shortlisted applications can be submitted.';
        }

        $this->loadMissing('job');

        if ($this->job === null || $this->job->employer_id === null) {
            return 'This job is not assigned to an employer yet.';
        }

        $exists = $this->submissions()
            ->where('job_id', $this->job_id)
            ->where('employer_profile_id', $this->job->employer_id)
            ->exists();

        if ($exists) {
            return 'This application has already been submitted to the employer for this job.';
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function jobSummary(): ?array
    {
        if (! $this->job) {
            return null;
        }

        $job = $this->job->toAdminArray();

        return [
            'id' => $job['id'],
            'business_id' => $job['business_id'],
            'title' => $job['title'],
            'slug' => $job['slug'],
            'industry' => $job['industry'],
            'category' => $job['category'],
            'location' => $job['location'],
            'employment_type' => $job['employment_type'],
            'experience' => $job['experience'],
            'status_label' => $job['status_label'],
            'employer' => $job['employer'],
            'workspace_url' => route('admin.jobs.index', ['job' => $this->job->slug], false),
        ];
    }
}
