<?php

namespace App\Models;

use App\Enums\BusinessIdPrefix;
use App\Enums\EmployerSubmissionStatus;
use App\Enums\PlacementStatus;
use App\Enums\ReplacementReason;
use App\Enums\ReplacementStatus;
use App\Models\Concerns\HasBusinessId;
use App\Support\RecruitmentWorkspace;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Replacement extends Model
{
    use HasBusinessId;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'original_employee_id',
        'original_placement_id',
        'employer_profile_id',
        'job_id',
        'original_application_id',
        'original_submission_id',
        'reason',
        'status',
        'notes',
        'replacement_application_id',
        'replacement_submission_id',
        'replacement_placement_id',
        'replacement_employee_id',
        'requested_by',
        'requested_at',
        'completed_at',
        'cancelled_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reason' => ReplacementReason::class,
            'status' => ReplacementStatus::class,
            'requested_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public static function businessIdPrefix(): BusinessIdPrefix
    {
        return BusinessIdPrefix::Replacement;
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function originalEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'original_employee_id');
    }

    /**
     * @return BelongsTo<Placement, $this>
     */
    public function originalPlacement(): BelongsTo
    {
        return $this->belongsTo(Placement::class, 'original_placement_id');
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
    public function originalApplication(): BelongsTo
    {
        return $this->belongsTo(Application::class, 'original_application_id');
    }

    /**
     * @return BelongsTo<EmployerSubmission, $this>
     */
    public function originalSubmission(): BelongsTo
    {
        return $this->belongsTo(EmployerSubmission::class, 'original_submission_id');
    }

    /**
     * @return BelongsTo<Application, $this>
     */
    public function replacementApplication(): BelongsTo
    {
        return $this->belongsTo(Application::class, 'replacement_application_id');
    }

    /**
     * @return BelongsTo<EmployerSubmission, $this>
     */
    public function replacementSubmission(): BelongsTo
    {
        return $this->belongsTo(EmployerSubmission::class, 'replacement_submission_id');
    }

    /**
     * @return BelongsTo<Placement, $this>
     */
    public function replacementPlacement(): BelongsTo
    {
        return $this->belongsTo(Placement::class, 'replacement_placement_id');
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function replacementEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'replacement_employee_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * @return HasMany<RecruitmentActivity, $this>
     */
    public function activities(): HasMany
    {
        return $this->hasMany(RecruitmentActivity::class)->orderBy('created_at');
    }

    public function canIdentifyCandidate(): bool
    {
        if ($this->status->isTerminal()) {
            return false;
        }

        $placement = $this->replacementPlacement;
        if ($placement === null) {
            return true;
        }

        return in_array($placement->status, [PlacementStatus::Declined, PlacementStatus::Withdrawn], true);
    }

    public function canCancel(): bool
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
            'originalEmployee.application.user.candidateProfile',
            'originalPlacement',
            'originalApplication.user.candidateProfile',
            'replacementEmployee.application',
            'replacementApplication.user.candidateProfile',
            'replacementSubmission',
            'replacementPlacement',
        ]);

        $original = $this->originalApplication ?? $this->originalEmployee?->application;
        $originalProfile = $original?->user?->candidateProfile
            ?? $this->originalEmployee?->candidateProfile;
        $current = $this->replacementApplication;
        $replacementProfile = $current?->user?->candidateProfile;

        return [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'status' => $this->status->value,
            'operational_stage' => RecruitmentWorkspace::replacementOperationalStage($this),
            'reason' => $this->reason->value,
            'requested_at' => $this->requested_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
            'eligibility' => RecruitmentWorkspace::replacementEligibility($this),
            'closure_date' => $this->completed_at?->toDateTimeString() ?? $this->cancelled_at?->toDateTimeString(),
            'employer' => $this->employer?->toAdminSummary(),
            'job' => $this->job ? [
                'id' => $this->job->id,
                'business_id' => $this->job->business_id,
                'title' => $this->job->title,
                'slug' => $this->job->slug,
            ] : null,
            'original_employee' => $this->originalEmployee ? [
                'id' => $this->originalEmployee->id,
                'business_id' => $this->originalEmployee->business_id,
                'name' => $original?->full_name,
            ] : null,
            'original_candidate' => [
                'id' => $originalProfile?->id,
                'business_id' => $originalProfile?->business_id,
                'name' => $original?->full_name,
            ],
            'original_placement' => $this->originalPlacement ? [
                'id' => $this->originalPlacement->id,
                'business_id' => $this->originalPlacement->business_id,
                'status' => $this->originalPlacement->status->value,
            ] : null,
            'replacement_candidate' => [
                'id' => $replacementProfile?->id,
                'business_id' => $replacementProfile?->business_id,
                'name' => $current?->full_name,
                'application_id' => $current?->business_id,
            ],
            'replacement_employee' => $this->replacementEmployee ? [
                'id' => $this->replacementEmployee->id,
                'business_id' => $this->replacementEmployee->business_id,
                'name' => $this->replacementEmployee->application?->full_name,
            ] : null,
            'links' => [
                'original_placement' => RecruitmentWorkspace::placementUrl($this->originalPlacement),
                'original_candidate' => RecruitmentWorkspace::candidateUrl($originalProfile),
                'original_employee' => RecruitmentWorkspace::employeeUrl($this->originalEmployee),
                'employer' => RecruitmentWorkspace::employerUrl($this->employer),
                'job' => RecruitmentWorkspace::jobUrl($this->job),
                'replacement_candidate' => RecruitmentWorkspace::candidateUrl($replacementProfile),
                'replacement_placement' => RecruitmentWorkspace::placementUrl($this->replacementPlacement),
                'replacement' => RecruitmentWorkspace::replacementUrl($this),
            ],
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
            'originalEmployee.application.user.candidateProfile',
            'originalPlacement',
            'originalApplication',
            'originalSubmission',
            'replacementApplication.user.candidateProfile',
            'replacementSubmission',
            'replacementPlacement.employee',
            'replacementEmployee.application',
            'requester:id,name',
            'activities.actor:id,name',
        ]);

        $payload = $this->toAdminListArray();
        $payload['notes'] = $this->notes;
        $payload['requested_by'] = $this->requester?->name;
        $payload['completed_at'] = $this->completed_at?->toDateTimeString();
        $payload['cancelled_at'] = $this->cancelled_at?->toDateTimeString();
        $payload['can_identify_candidate'] = $this->canIdentifyCandidate();
        $payload['can_cancel'] = $this->canCancel();
        $payload['original_placement'] = $this->originalPlacement ? [
            'id' => $this->originalPlacement->id,
            'business_id' => $this->originalPlacement->business_id,
            'status' => $this->originalPlacement->status->value,
        ] : null;
        $payload['original_application'] = $this->originalApplication ? [
            'id' => $this->originalApplication->id,
            'business_id' => $this->originalApplication->business_id,
        ] : null;
        $payload['original_submission'] = $this->originalSubmission ? [
            'id' => $this->originalSubmission->id,
            'business_id' => $this->originalSubmission->business_id,
        ] : null;
        $payload['replacement_application'] = $this->replacementApplication ? [
            'id' => $this->replacementApplication->id,
            'business_id' => $this->replacementApplication->business_id,
            'name' => $this->replacementApplication->full_name,
            'status' => $this->replacementApplication->status->value,
        ] : null;
        $payload['replacement_submission'] = $this->replacementSubmission ? [
            'id' => $this->replacementSubmission->id,
            'business_id' => $this->replacementSubmission->business_id,
            'status' => $this->replacementSubmission->status->value,
        ] : null;
        $payload['replacement_placement'] = $this->replacementPlacement ? [
            'id' => $this->replacementPlacement->id,
            'business_id' => $this->replacementPlacement->business_id,
            'status' => $this->replacementPlacement->status->value,
        ] : null;
        $payload['chain'] = RecruitmentWorkspace::replacementChain($this);
        $payload['workflow'] = RecruitmentWorkspace::replacementWorkflow($this);
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

        if ($this->replacementPlacement) {
            $items = $items->concat(collect($this->replacementPlacement->timeline()));
        }

        if ($this->originalPlacement) {
            $items = $items->concat(
                $this->originalPlacement->activities()->with('actor:id,name')->get()
                    ->map(fn (RecruitmentActivity $activity) => $activity->toAdminArray())
            );
        }

        return $items
            ->unique('id')
            ->sortBy('created_at')
            ->values()
            ->all();
    }

    public function currentAttemptHasFailed(): bool
    {
        $placement = $this->replacementPlacement;
        if ($placement && in_array($placement->status, [PlacementStatus::Declined, PlacementStatus::Withdrawn], true)) {
            return true;
        }

        $submission = $this->replacementSubmission;

        return $submission !== null && $submission->status === EmployerSubmissionStatus::Rejected;
    }
}
