<?php

namespace App\Models;

use App\Enums\BusinessIdPrefix;
use App\Enums\EmployerSubmissionStatus;
use App\Models\Concerns\HasBusinessId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployerSubmission extends Model
{
    use HasBusinessId;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'employer_profile_id',
        'job_id',
        'application_id',
        'status',
        'notes',
        'rejection_reason_id',
        'submitted_by',
        'submitted_at',
        'decided_by',
        'decided_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => EmployerSubmissionStatus::class,
            'submitted_at' => 'datetime',
            'decided_at' => 'datetime',
        ];
    }

    public static function businessIdPrefix(): BusinessIdPrefix
    {
        return BusinessIdPrefix::Submission;
    }

    /**
     * @return BelongsTo<EmployerProfile, $this>
     */
    public function employer(): BelongsTo
    {
        return $this->belongsTo(EmployerProfile::class, 'employer_profile_id');
    }

    /**
     * @return BelongsTo<Job, $this>
     */
    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    /**
     * @return BelongsTo<Application, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * @return BelongsTo<RejectionReason, $this>
     */
    public function rejectionReason(): BelongsTo
    {
        return $this->belongsTo(RejectionReason::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    /**
     * @return HasMany<RecruitmentActivity, $this>
     */
    public function activities(): HasMany
    {
        return $this->hasMany(RecruitmentActivity::class)->orderBy('created_at');
    }

    public function canSelect(): bool
    {
        return ! $this->status->isTerminal();
    }

    public function canReject(): bool
    {
        return ! $this->status->isTerminal();
    }

    public function canHold(): bool
    {
        return ! $this->status->isTerminal();
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminListArray(): array
    {
        $this->loadMissing([
            'employer.user',
            'job',
            'application.user.candidateProfile',
        ]);

        $application = $this->application;
        $profile = $application?->user?->candidateProfile;

        return [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'status' => $this->status->value,
            'submitted_at' => $this->submitted_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
            'employer' => $this->employer?->toAdminSummary(),
            'job' => $this->job ? [
                'id' => $this->job->id,
                'business_id' => $this->job->business_id,
                'title' => $this->job->title,
                'slug' => $this->job->slug,
            ] : null,
            'candidate' => [
                'id' => $profile?->id,
                'business_id' => $profile?->business_id,
                'name' => $application?->full_name,
            ],
            'application' => $application ? [
                'id' => $application->id,
                'business_id' => $application->business_id,
            ] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminArray(): array
    {
        $this->loadMissing([
            'employer.user',
            'employer.classifiedIndustry',
            'job',
            'application',
            'rejectionReason',
            'submitter:id,name',
            'decider:id,name',
            'activities.actor:id,name',
        ]);

        $payload = $this->toAdminListArray();
        $payload['notes'] = $this->notes;
        $payload['rejection_reason'] = $this->rejectionReason?->name;
        $payload['rejection_reason_id'] = $this->rejection_reason_id;
        $payload['submitted_by'] = $this->submitter?->name;
        $payload['decided_by'] = $this->decider?->name;
        $payload['decided_at'] = $this->decided_at?->toDateTimeString();
        $payload['can_select'] = $this->canSelect();
        $payload['can_reject'] = $this->canReject();
        $payload['can_hold'] = $this->canHold();
        $payload['employer'] = $this->employer?->toAdminArray();
        $payload['job'] = $this->job?->toAdminArray();
        $payload['application'] = $this->application?->toAdminArray(true);
        $payload['resume_url'] = $this->application
            ? route('admin.applications.resume', $this->application)
            : null;
        $payload['has_resume'] = $this->application
            ? filled($this->application->getRawOriginal('resume_path') ?? $this->application->getAttributes()['resume_path'] ?? null)
            : false;
        $payload['activities'] = $this->timeline();

        return $payload;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function timeline(): array
    {
        $items = $this->activities
            ->map(fn (RecruitmentActivity $activity) => $activity->toAdminArray())
            ->values();

        if ($this->application) {
            $applicationItems = $this->application->activities()
                ->with('actor:id,name')
                ->get()
                ->map(fn (RecruitmentActivity $activity) => $activity->toAdminArray());
            $items = $items->concat($applicationItems);
        }

        return $items
            ->unique('id')
            ->sortBy('created_at')
            ->values()
            ->all();
    }
}
