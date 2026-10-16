<?php

namespace App\Models;

use App\Enums\RecruiterTalentPoolAssignmentProgressStage;
use App\Enums\RecruiterTalentPoolAssignmentStatus;
use App\Enums\RecruiterTalentPoolAssignmentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecruiterTalentPoolAssignment extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'candidate_user_id',
        'assigned_recruiter_user_id',
        'assigned_by_user_id',
        'application_id',
        'job_id',
        'employer_enquiry_id',
        'assignment_type',
        'status',
        'progress_stage',
        'admin_notes',
        'recruiter_notes',
        'follow_up_at',
        'interview_at',
        'interview_details',
        'employer_submission_id',
        'talent_pool_company_submission_id',
        'staffing_candidate_submission_id',
        'archived_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'assignment_type' => RecruiterTalentPoolAssignmentType::class,
            'status' => RecruiterTalentPoolAssignmentStatus::class,
            'progress_stage' => RecruiterTalentPoolAssignmentProgressStage::class,
            'follow_up_at' => 'datetime',
            'interview_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
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
    public function recruiterUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_recruiter_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_user_id');
    }

    /**
     * @return BelongsTo<Application, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * @return BelongsTo<Job, $this>
     */
    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    /**
     * @return BelongsTo<EmployerEnquiry, $this>
     */
    public function employerEnquiry(): BelongsTo
    {
        return $this->belongsTo(EmployerEnquiry::class);
    }

    /**
     * @return BelongsTo<EmployerSubmission, $this>
     */
    public function employerSubmission(): BelongsTo
    {
        return $this->belongsTo(EmployerSubmission::class);
    }

    /**
     * @return BelongsTo<TalentPoolCompanySubmission, $this>
     */
    public function talentPoolCompanySubmission(): BelongsTo
    {
        return $this->belongsTo(TalentPoolCompanySubmission::class, 'talent_pool_company_submission_id');
    }

    /**
     * @return BelongsTo<StaffingCandidateSubmission, $this>
     */
    public function staffingCandidateSubmission(): BelongsTo
    {
        return $this->belongsTo(StaffingCandidateSubmission::class, 'staffing_candidate_submission_id');
    }

    /**
     * @return HasMany<RecruiterTalentPoolAssignmentStatusEvent, $this>
     */
    public function statusEvents(): HasMany
    {
        return $this->hasMany(RecruiterTalentPoolAssignmentStatusEvent::class)->orderBy('created_at');
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminListArray(): array
    {
        $this->loadMissing([
            'candidateUser.candidateProfile',
            'recruiterUser.employerProfile',
            'assignedByUser',
            'job',
            'employerEnquiry',
        ]);

        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'assignment_type' => $this->assignment_type?->value ?? RecruiterTalentPoolAssignmentType::Job->value,
            'assignment_type_label' => $this->assignment_type?->label() ?? RecruiterTalentPoolAssignmentType::Job->label(),
            'progress_stage' => $this->progress_stage?->value ?? RecruiterTalentPoolAssignmentProgressStage::Assigned->value,
            'progress_stage_label' => $this->progressStageLabel(),
            'follow_up_at' => $this->follow_up_at?->toDateTimeString(),
            'follow_up_overdue' => $this->follow_up_at !== null && $this->follow_up_at->isPast() && ! $this->status->isTerminal(),
            'interview_at' => $this->interview_at?->toDateTimeString(),
            'candidate_name' => $this->candidateUser?->name,
            'candidate_user_id' => $this->candidate_user_id,
            'recruiter_name' => $this->recruiterUser?->name,
            'recruiter_user_id' => $this->assigned_recruiter_user_id,
            'recruiter_company' => $this->recruiterUser?->employerProfile?->displayCompany(),
            'assigned_by_name' => $this->assignedByUser?->name,
            'job_title' => $this->job?->title,
            'job_id' => $this->job_id,
            'employer_enquiry_id' => $this->employer_enquiry_id,
            'requirement_label' => $this->requirementLabel(),
            'admin_notes' => $this->admin_notes,
            'recruiter_notes' => $this->recruiter_notes,
            'archived_at' => $this->archived_at?->toDateTimeString(),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminDetailArray(): array
    {
        $base = $this->toAdminListArray();
        $base['status_events'] = $this->statusEvents()
            ->with('actor:id,name')
            ->get()
            ->map(fn (RecruiterTalentPoolAssignmentStatusEvent $event) => [
                'from_status' => $event->from_status,
                'to_status' => $event->to_status,
                'to_status_label' => RecruiterTalentPoolAssignmentStatus::from($event->to_status)->label(),
                'progress_stage' => $event->progress_stage,
                'progress_stage_label' => $event->progress_stage !== null
                    ? RecruiterTalentPoolAssignmentProgressStage::from($event->progress_stage)->label()
                    : null,
                'notes' => $event->notes,
                'actor_name' => $event->actor?->name,
                'created_at' => $event->created_at?->toDateTimeString(),
            ])
            ->all();

        return $base;
    }

    /**
     * @return array<string, mixed>
     */
    public function toRecruiterListArray(): array
    {
        $this->loadMissing([
            'candidateUser.candidateProfile',
            'recruiterUser.candidateProfile',
            'job',
            'employerEnquiry',
            'assignedByUser',
        ]);

        $profile = $this->candidateUser?->candidateProfile;
        $executive = $this->recruiterUser;
        $executiveCandidateProfile = $executive?->candidateProfile;
        $executiveProfileUrl = null;
        if ($executive !== null
            && $executive->username
            && $executiveCandidateProfile?->shareAllows('enabled')) {
            $executiveProfileUrl = route('profiles.show', $executive, false);
        }

        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'assignment_type' => $this->assignment_type?->value ?? RecruiterTalentPoolAssignmentType::Job->value,
            'assignment_type_label' => $this->assignment_type?->label() ?? RecruiterTalentPoolAssignmentType::Job->label(),
            'progress_stage' => $this->progress_stage?->value ?? RecruiterTalentPoolAssignmentProgressStage::Assigned->value,
            'progress_stage_label' => $this->progressStageLabel(),
            'follow_up_at' => $this->follow_up_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
            'candidate_name' => $this->candidateUser?->name,
            'candidate_user_id' => $this->candidate_user_id,
            'candidate_ournear_id' => $this->candidateUser
                ? app(\App\Support\OurNearHireIdentity::class)->display($this->candidateUser, 'CAN')
                : null,
            'candidate_headline' => $profile?->current_designation,
            'executive_name' => $executive?->name,
            'executive_profile_url' => $executiveProfileUrl,
            'requirement_label' => $this->requirementLabel(),
            'assigned_by_name' => $this->assignedByUser?->name,
            'assigned_at' => $this->created_at?->toDateTimeString(),
            'recruiter_notes' => $this->recruiter_notes,
            'can_download_resume' => $this->application_id !== null,
            'application_id' => $this->application_id,
            'archived_at' => $this->archived_at?->toDateTimeString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toRecruiterDetailArray(): array
    {
        $base = $this->toRecruiterListArray();
        $profile = $this->candidateUser?->candidateProfile;

        $base['admin_notes'] = $this->admin_notes;
        $base['job_id'] = $this->job_id;
        $base['employer_enquiry_id'] = $this->employer_enquiry_id;
        $base['profile_summary'] = [
            'location' => $profile?->location,
            'experience' => $profile?->experience,
            'availability' => $profile?->availability,
        ];
        $base['status_events'] = $this->statusEvents()
            ->with('actor:id,name')
            ->get()
            ->map(fn (RecruiterTalentPoolAssignmentStatusEvent $event) => [
                'from_status' => $event->from_status,
                'to_status' => $event->to_status,
                'to_status_label' => RecruiterTalentPoolAssignmentStatus::from($event->to_status)->label(),
                'progress_stage' => $event->progress_stage,
                'progress_stage_label' => $event->progress_stage !== null
                    ? RecruiterTalentPoolAssignmentProgressStage::from($event->progress_stage)->label()
                    : null,
                'notes' => $event->notes,
                'actor_name' => $event->actor?->name,
                'created_at' => $event->created_at?->toDateTimeString(),
            ])
            ->all();
        $stage = $this->progress_stage ?? RecruiterTalentPoolAssignmentProgressStage::Assigned;
        $base['allowed_progress_stages'] = array_map(
            fn (RecruiterTalentPoolAssignmentProgressStage $progressStage) => [
                'value' => $progressStage->value,
                'label' => $progressStage->label(),
            ],
            $stage->allowedRecruiterTransitions(),
        );
        $base['can_submit_to_employer'] = $this->canSubmitToEmployer();
        $base['can_submit_to_staffing_client'] = $this->canSubmitToStaffingClient();
        $base['employer_submission_id'] = $this->employer_submission_id;
        $base['staffing_candidate_submission_id'] = $this->staffing_candidate_submission_id;
        $base['interview_details'] = $this->interview_details;

        return $base;
    }

    public function progressStageLabel(): string
    {
        return ($this->progress_stage ?? RecruiterTalentPoolAssignmentProgressStage::Assigned)->label();
    }

    public function canSubmitToEmployer(): bool
    {
        if ($this->employer_submission_id !== null) {
            return false;
        }

        if ($this->job_id === null) {
            return false;
        }

        return $this->application_id !== null;
    }

    public function canSubmitToStaffingClient(): bool
    {
        if ($this->assignment_type !== RecruiterTalentPoolAssignmentType::Staffing) {
            return false;
        }

        if ($this->staffing_candidate_submission_id !== null) {
            return false;
        }

        return $this->employer_enquiry_id !== null;
    }

    public function requirementLabel(): ?string
    {
        if ($this->relationLoaded('job') && $this->job) {
            return $this->job->title;
        }
        if ($this->relationLoaded('employerEnquiry') && $this->employerEnquiry) {
            return trim(($this->employerEnquiry->company_name ?? 'Recruitment support').' · '.$this->employerEnquiry->project_location);
        }

        return null;
    }
}
