<?php

namespace App\Models;

use App\Enums\BusinessIdPrefix;
use App\Enums\PlacementStatus;
use App\Enums\ReplacementStatus;
use App\Models\Concerns\HasBusinessId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Employee extends Model
{
    use HasBusinessId;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'placement_id',
        'employer_profile_id',
        'job_id',
        'application_id',
        'employer_submission_id',
        'candidate_profile_id',
        'joined_at',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
        ];
    }

    public static function businessIdPrefix(): BusinessIdPrefix
    {
        return BusinessIdPrefix::Employee;
    }

    /**
     * @return BelongsTo<Placement, $this>
     */
    public function placement(): BelongsTo
    {
        return $this->belongsTo(Placement::class);
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
     * @return BelongsTo<EmployerSubmission, $this>
     */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(EmployerSubmission::class, 'employer_submission_id');
    }

    /**
     * @return BelongsTo<CandidateProfile, $this>
     */
    public function candidateProfile(): BelongsTo
    {
        return $this->belongsTo(CandidateProfile::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<RecruitmentActivity, $this>
     */
    public function activities(): HasMany
    {
        return $this->hasMany(RecruitmentActivity::class)->orderBy('created_at');
    }

    /**
     * @return HasMany<Replacement, $this>
     */
    public function originatingReplacements(): HasMany
    {
        return $this->hasMany(Replacement::class, 'original_employee_id');
    }

    /**
     * @return HasOne<Replacement, $this>
     */
    public function resultingReplacement(): HasOne
    {
        return $this->hasOne(Replacement::class, 'replacement_employee_id');
    }

    public function canRequestReplacement(): bool
    {
        $this->loadMissing(['placement', 'employer', 'job', 'application', 'submission']);

        if ($this->placement === null || $this->placement->status !== PlacementStatus::Joined) {
            return false;
        }

        if ($this->employer === null || $this->job === null || $this->application === null || $this->submission === null) {
            return false;
        }

        return ! Replacement::query()
            ->where('original_employee_id', $this->id)
            ->whereIn('status', [
                ReplacementStatus::Requested->value,
                ReplacementStatus::InProgress->value,
                ReplacementStatus::Placed->value,
                ReplacementStatus::Completed->value,
            ])
            ->exists();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function replacementSummary(): ?array
    {
        $this->loadMissing(['originatingReplacements', 'resultingReplacement.originalEmployee.application']);

        $originating = $this->originatingReplacements->sortByDesc('id')->first();
        $resulting = $this->resultingReplacement;

        if ($originating === null && $resulting === null) {
            return null;
        }

        return [
            'role' => $resulting ? 'replacement' : 'original',
            'originating' => $originating ? [
                'id' => $originating->id,
                'business_id' => $originating->business_id,
                'status' => $originating->status->value,
                'replacement_employee_id' => $originating->replacement_employee_id,
            ] : null,
            'resulting' => $resulting ? [
                'id' => $resulting->id,
                'business_id' => $resulting->business_id,
                'status' => $resulting->status->value,
                'original_employee_id' => $resulting->original_employee_id,
                'original_employee_name' => $resulting->originalEmployee?->application?->full_name,
            ] : null,
        ];
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
            'submission',
            'placement',
            'candidateProfile',
        ]);

        $application = $this->application;
        $profile = $this->candidateProfile ?? $application?->user?->candidateProfile;

        return [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'joined_at' => $this->joined_at?->toDateTimeString(),
            'created_at' => $this->created_at?->toDateTimeString(),
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
            'submission' => $this->submission ? [
                'id' => $this->submission->id,
                'business_id' => $this->submission->business_id,
            ] : null,
            'placement' => $this->placement ? [
                'id' => $this->placement->id,
                'business_id' => $this->placement->business_id,
                'status' => $this->placement->status->value,
            ] : null,
            'replacement' => $this->replacementSummary(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminArray(): array
    {
        $this->loadMissing([
            'employer.user',
            'job',
            'application',
            'submission',
            'placement.offer',
            'candidateProfile',
            'creator:id,name',
            'activities.actor:id,name',
        ]);

        $payload = $this->toAdminListArray();
        $payload['created_by'] = $this->creator?->name;
        $payload['employer'] = $this->employer?->toAdminArray();
        $payload['job'] = $this->job?->toAdminArray();
        $payload['application'] = $this->application?->toAdminArray(true);
        $payload['submission'] = $this->submission?->toAdminListArray();
        $payload['placement'] = $this->placement?->toAdminListArray();
        $payload['offer'] = $this->placement?->offer?->toAdminArray();
        $payload['expected_joining_date'] = $this->placement?->expected_joining_date?->toDateString();
        $payload['actual_joining_date'] = $this->placement?->actual_joining_date?->toDateString();
        $payload['joining_notes'] = $this->placement?->joining_notes;
        $payload['activities'] = $this->placement?->timeline() ?? [];
        $payload['can_request_replacement'] = $this->canRequestReplacement();
        $payload['replacement'] = $this->replacementSummary();

        return $payload;
    }
}
