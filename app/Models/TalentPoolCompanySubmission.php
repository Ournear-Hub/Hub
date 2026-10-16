<?php

namespace App\Models;

use App\Enums\BusinessIdPrefix;
use App\Enums\EmployerSubmissionStatus;
use App\Enums\TalentPoolSubmissionOwnerResolution;
use App\Models\Concerns\HasBusinessId;
use App\Support\RecruitmentWorkspace;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TalentPoolCompanySubmission extends Model
{
    use HasBusinessId;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'candidate_user_id',
        'employer_profile_id',
        'responsible_recruiter_user_id',
        'candidate_owner_recruiter_user_id',
        'company_owner_recruiter_user_id',
        'owner_resolution',
        'submitted_by_user_id',
        'recruiter_talent_pool_assignment_id',
        'status',
        'internal_notes',
        'employer_notes',
        'rejection_reason_id',
        'submitted_at',
        'decided_by_user_id',
        'decided_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => EmployerSubmissionStatus::class,
            'owner_resolution' => TalentPoolSubmissionOwnerResolution::class,
            'submitted_at' => 'datetime',
            'decided_at' => 'datetime',
        ];
    }

    public static function businessIdPrefix(): BusinessIdPrefix
    {
        return BusinessIdPrefix::TalentPoolSubmission;
    }

    public function isActive(): bool
    {
        return ! $this->status->isTerminal();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function candidateUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'candidate_user_id');
    }

    /**
     * @return BelongsTo<EmployerProfile, $this>
     */
    public function employerProfile(): BelongsTo
    {
        return $this->belongsTo(EmployerProfile::class, 'employer_profile_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function responsibleRecruiter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_recruiter_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_user_id');
    }

    /**
     * @return BelongsTo<RecruiterTalentPoolAssignment, $this>
     */
    public function talentPoolAssignment(): BelongsTo
    {
        return $this->belongsTo(RecruiterTalentPoolAssignment::class, 'recruiter_talent_pool_assignment_id');
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
    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by_user_id');
    }

    /**
     * @return HasMany<TalentPoolSubmissionStatusEvent, $this>
     */
    public function statusEvents(): HasMany
    {
        return $this->hasMany(TalentPoolSubmissionStatusEvent::class)->latest('id');
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminListArray(): array
    {
        $this->loadMissing(['candidateUser', 'employerProfile', 'responsibleRecruiter', 'submitter']);

        return [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'candidate_user_id' => $this->candidate_user_id,
            'candidate_name' => $this->candidateUser?->name,
            'company_name' => $this->employerProfile?->displayCompany(),
            'employer_profile_id' => $this->employer_profile_id,
            'responsible_recruiter_name' => $this->responsibleRecruiter?->name,
            'responsible_recruiter_user_id' => $this->responsible_recruiter_user_id,
            'owner_resolution' => $this->owner_resolution?->value,
            'owner_resolution_label' => $this->owner_resolution?->label(),
            'submitted_by_name' => $this->submitter?->name,
            'status' => $this->status->value,
            'status_label' => RecruitmentWorkspace::responseLabel($this->status),
            'submitted_at' => $this->submitted_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
