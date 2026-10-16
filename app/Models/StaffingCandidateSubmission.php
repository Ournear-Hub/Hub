<?php

namespace App\Models;

use App\Enums\BusinessIdPrefix;
use App\Enums\StaffingCandidateSubmissionStatus;
use App\Models\Concerns\HasBusinessId;
use App\Support\EmployerVisibleCandidate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StaffingCandidateSubmission extends Model
{
    use HasBusinessId;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'employer_enquiry_id',
        'candidate_user_id',
        'responsible_recruiter_user_id',
        'submitted_by_user_id',
        'recruiter_talent_pool_assignment_id',
        'status',
        'internal_notes',
        'client_feedback',
        'rejection_reason_id',
        'submitted_at',
        'decided_by_user_id',
        'decided_at',
        'joining_date',
        'joining_confirmation_notes',
        'joining_confirmed_by_user_id',
        'joining_confirmed_at',
        'fulfilment_counted_at',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'internal_notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => StaffingCandidateSubmissionStatus::class,
            'submitted_at' => 'datetime',
            'decided_at' => 'datetime',
            'joining_date' => 'date',
            'joining_confirmed_at' => 'datetime',
            'fulfilment_counted_at' => 'datetime',
        ];
    }

    public static function businessIdPrefix(): BusinessIdPrefix
    {
        return BusinessIdPrefix::StaffingSubmission;
    }

    public function isActive(): bool
    {
        return ! $this->status->isTerminal();
    }

    /**
     * @return BelongsTo<EmployerEnquiry, $this>
     */
    public function employerEnquiry(): BelongsTo
    {
        return $this->belongsTo(EmployerEnquiry::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function candidateUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'candidate_user_id');
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
    public function submittedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_user_id');
    }

    /**
     * @return BelongsTo<RecruiterTalentPoolAssignment, $this>
     */
    public function assignment(): BelongsTo
    {
        return $this->belongsTo(RecruiterTalentPoolAssignment::class, 'recruiter_talent_pool_assignment_id');
    }

    /**
     * @return HasMany<StaffingSubmissionStatusEvent, $this>
     */
    public function statusEvents(): HasMany
    {
        return $this->hasMany(StaffingSubmissionStatusEvent::class)->orderBy('created_at');
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminListArray(): array
    {
        $this->loadMissing(['candidateUser', 'employerEnquiry', 'responsibleRecruiter']);

        return [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'candidate_name' => $this->candidateUser?->name,
            'candidate_user_id' => $this->candidate_user_id,
            'requirement_label' => $this->employerEnquiry
                ? trim(($this->employerEnquiry->company_name ?? 'Staffing').' · '.$this->employerEnquiry->project_location)
                : null,
            'employer_enquiry_id' => $this->employer_enquiry_id,
            'recruiter_name' => $this->responsibleRecruiter?->name,
            'submitted_at' => $this->submitted_at?->toDateTimeString(),
            'joining_date' => $this->joining_date?->toDateString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toClientArray(): array
    {
        $this->loadMissing(['candidateUser.candidateProfile', 'employerEnquiry']);

        return EmployerVisibleCandidate::fromStaffingSubmission($this);
    }
}
