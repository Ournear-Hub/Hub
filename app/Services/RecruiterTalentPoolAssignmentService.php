<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Enums\PermissionName;
use App\Enums\RecruiterTalentPoolAssignmentProgressStage;
use App\Enums\RecruiterTalentPoolAssignmentStatus;
use App\Enums\RecruiterTalentPoolAssignmentType;
use App\Models\Application;
use App\Models\EmployerEnquiry;
use App\Models\EmployerSubmission;
use App\Models\Job;
use App\Models\EmployerProfile;
use App\Models\RecruiterTalentPoolAssignment;
use App\Models\TalentPoolCompanySubmission;
use App\Models\RecruiterTalentPoolAssignmentStatusEvent;
use App\Models\User;
use App\Support\OurNearInternalRecruiters;
use App\Support\RecruitingEmployerAccounts;
use App\Support\StaffingRequirementAllocationQuery;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecruiterTalentPoolAssignmentService
{
    public function __construct(
        private RecruiterTalentPoolAssignmentNotificationService $notifications,
        private EmployerSubmissionService $employerSubmissions,
    ) {}

    /**
     * @param  list<int>  $candidateUserIds
     * @return list<RecruiterTalentPoolAssignment>
     */
    public function assignMany(
        User $admin,
        array $candidateUserIds,
        User $recruiter,
        RecruiterTalentPoolAssignmentType $type,
        ?Application $application,
        ?Job $job,
        ?EmployerEnquiry $enquiry,
        ?string $adminNotes,
        ?Carbon $followUpAt,
    ): array {
        $created = [];
        foreach (array_values(array_unique($candidateUserIds)) as $candidateUserId) {
            $candidate = User::query()->findOrFail((int) $candidateUserId);
            $created[] = $this->assign(
                $admin,
                $candidate,
                $recruiter,
                $type,
                $application,
                $job,
                $enquiry,
                $adminNotes,
                $followUpAt,
            );
        }

        return $created;
    }

    public function assign(
        User $admin,
        User $candidate,
        User $recruiter,
        RecruiterTalentPoolAssignmentType $type,
        ?Application $application = null,
        ?Job $job = null,
        ?EmployerEnquiry $enquiry = null,
        ?string $adminNotes = null,
        ?Carbon $followUpAt = null,
    ): RecruiterTalentPoolAssignment {
        return DB::transaction(function () use ($admin, $candidate, $recruiter, $type, $application, $job, $enquiry, $adminNotes, $followUpAt) {
            $this->assertAdminOperator($admin);
            $this->assertCandidate($candidate);
            $this->assertRecruiter($recruiter);
            $this->assertAssignmentContext($type, $job, $enquiry);
            $this->assertShortlistedWhenRequired($type, $candidate, $application);
            $this->assertNoDuplicateActiveAssignment($candidate, $recruiter, $type, $job, $enquiry);

            if ($application !== null) {
                $this->assertApplicationBelongsToCandidate($application, $candidate);
                $job ??= $application->job;
            }

            if ($job !== null) {
                $this->assertJobBelongsToRecruiter($job, $recruiter);
            }

            if ($enquiry !== null) {
                $this->assertEnquiryEligibleForStaffingAssignment($enquiry);
            }

            $initialStage = RecruiterTalentPoolAssignmentProgressStage::Assigned;
            $initialStatus = $initialStage->syncCoarseStatus();

            $assignment = RecruiterTalentPoolAssignment::query()->create([
                'candidate_user_id' => $candidate->id,
                'assigned_recruiter_user_id' => $recruiter->id,
                'assigned_by_user_id' => $admin->id,
                'application_id' => $application?->id,
                'job_id' => $job?->id,
                'employer_enquiry_id' => $enquiry?->id,
                'assignment_type' => $type,
                'status' => $initialStatus,
                'progress_stage' => $initialStage,
                'admin_notes' => $adminNotes,
                'follow_up_at' => $followUpAt,
            ]);

            $this->recordStatusEvent(
                $assignment,
                null,
                $initialStatus,
                $initialStage,
                $admin,
                $adminNotes,
            );

            $fresh = $assignment->fresh() ?? $assignment;
            $this->notifications->assignmentCreated($fresh);

            return $fresh;
        });
    }

    public function transition(
        RecruiterTalentPoolAssignment $assignment,
        User $actor,
        RecruiterTalentPoolAssignmentStatus $toStatus,
        ?string $notes = null,
    ): RecruiterTalentPoolAssignment {
        return DB::transaction(function () use ($assignment, $actor, $toStatus, $notes) {
            $assignment = RecruiterTalentPoolAssignment::query()->lockForUpdate()->findOrFail($assignment->id);

            if ($assignment->isArchived()) {
                throw ValidationException::withMessages([
                    'status' => 'This assignment is archived and cannot be updated.',
                ]);
            }

            $from = $assignment->status;

            if ($this->isAdminOperator($actor)) {
                $this->assertAdminOperator($actor);
            } else {
                $this->assertRecruiterOwnsAssignment($actor, $assignment);
                if (! in_array($toStatus, $from->allowedRecruiterTransitions(), true)) {
                    throw ValidationException::withMessages([
                        'status' => 'This status change is not allowed for recruiters.',
                    ]);
                }
            }

            if ($from === $toStatus) {
                return $assignment;
            }

            $assignment->update([
                'status' => $toStatus,
                'recruiter_notes' => $notes !== null && $notes !== '' ? $notes : $assignment->recruiter_notes,
            ]);

            $stage = $assignment->progress_stage ?? RecruiterTalentPoolAssignmentProgressStage::Assigned;
            $this->recordStatusEvent($assignment, $from, $toStatus, $stage, $actor, $notes);
            $fresh = $assignment->fresh() ?? $assignment;
            $this->notifications->assignmentStatusChanged($fresh, $from, $actor);

            return $fresh;
        });
    }

    public function updateProgress(
        RecruiterTalentPoolAssignment $assignment,
        User $actor,
        RecruiterTalentPoolAssignmentProgressStage $toStage,
        ?string $notes = null,
        ?Carbon $followUpAt = null,
        ?Carbon $interviewAt = null,
        ?string $interviewDetails = null,
    ): RecruiterTalentPoolAssignment {
        return DB::transaction(function () use ($assignment, $actor, $toStage, $notes, $followUpAt, $interviewAt, $interviewDetails) {
            $assignment = RecruiterTalentPoolAssignment::query()->lockForUpdate()->findOrFail($assignment->id);

            if ($assignment->isArchived()) {
                throw ValidationException::withMessages([
                    'progress_stage' => 'This assignment is archived and cannot be updated.',
                ]);
            }

            $fromStage = $assignment->progress_stage ?? RecruiterTalentPoolAssignmentProgressStage::Assigned;
            $fromStatus = $assignment->status;
            $toStatus = $toStage->syncCoarseStatus();

            if ($this->isAdminOperator($actor)) {
                $this->assertAdminOperator($actor);
            } else {
                $this->assertRecruiterOwnsAssignment($actor, $assignment);
                if ($fromStage->isTerminal()) {
                    throw ValidationException::withMessages([
                        'progress_stage' => 'This assignment is closed and cannot be updated.',
                    ]);
                }
                if (! in_array($toStage, $fromStage->allowedRecruiterTransitions(), true)) {
                    throw ValidationException::withMessages([
                        'progress_stage' => 'This progress update is not allowed.',
                    ]);
                }
            }

            if ($fromStage === $toStage
                && $notes === null
                && $followUpAt === null
                && $interviewAt === null
                && $interviewDetails === null) {
                return $assignment;
            }

            $assignment->update([
                'progress_stage' => $toStage,
                'status' => $toStatus,
                'recruiter_notes' => $notes !== null && $notes !== '' ? $notes : $assignment->recruiter_notes,
                'follow_up_at' => $followUpAt ?? $assignment->follow_up_at,
                'interview_at' => $interviewAt ?? $assignment->interview_at,
                'interview_details' => $interviewDetails ?? $assignment->interview_details,
            ]);

            $this->recordStatusEvent($assignment, $fromStatus, $toStatus, $toStage, $actor, $notes);
            $fresh = $assignment->fresh() ?? $assignment;
            $this->notifications->assignmentStatusChanged($fresh, $fromStatus, $actor);

            return $fresh;
        });
    }

    public function submitToCompany(
        RecruiterTalentPoolAssignment $assignment,
        EmployerProfile $employer,
        User $actor,
        ?User $explicitResponsibleRecruiter = null,
        ?string $internalNotes = null,
    ): TalentPoolCompanySubmission {
        $candidate = User::query()->findOrFail($assignment->candidate_user_id);

        return app(TalentPoolCompanySubmissionService::class)->submit(
            $candidate,
            $employer,
            $actor,
            $assignment,
            $explicitResponsibleRecruiter,
            $internalNotes,
        );
    }

    public function submitToStaffingClient(
        RecruiterTalentPoolAssignment $assignment,
        User $actor,
        ?string $internalNotes = null,
    ): \App\Models\StaffingCandidateSubmission {
        return app(StaffingCandidateSubmissionService::class)->submitFromAssignment(
            $assignment,
            $actor,
            $internalNotes,
        );
    }

    public function submitToEmployer(
        RecruiterTalentPoolAssignment $assignment,
        User $actor,
    ): EmployerSubmission {
        return DB::transaction(function () use ($assignment, $actor) {
            $assignment = RecruiterTalentPoolAssignment::query()->lockForUpdate()->findOrFail($assignment->id);

            if ($assignment->employer_submission_id !== null) {
                throw ValidationException::withMessages([
                    'submission' => 'This assignment was already submitted to an employer.',
                ]);
            }

            if ($this->isAdminOperator($actor)) {
                $this->assertAdminOperator($actor);
            } else {
                $this->assertRecruiterOwnsAssignment($actor, $assignment);
            }

            $application = Application::query()->find($assignment->application_id);
            if ($application === null) {
                throw ValidationException::withMessages([
                    'submission' => 'Submit only when a real shortlisted application is linked to this assignment.',
                ]);
            }

            $submission = $this->employerSubmissions->submit($application, $actor);

            $fromStatus = $assignment->status;
            $toStatus = RecruiterTalentPoolAssignmentProgressStage::SubmittedToEmployer->syncCoarseStatus();

            $assignment->update([
                'employer_submission_id' => $submission->id,
                'progress_stage' => RecruiterTalentPoolAssignmentProgressStage::SubmittedToEmployer,
                'status' => $toStatus,
            ]);

            $this->recordStatusEvent(
                $assignment,
                $fromStatus,
                $toStatus,
                RecruiterTalentPoolAssignmentProgressStage::SubmittedToEmployer,
                $actor,
                'Submitted to employer via assignment workflow.',
            );

            return $submission;
        });
    }

    public function archive(RecruiterTalentPoolAssignment $assignment, User $actor): RecruiterTalentPoolAssignment
    {
        return DB::transaction(function () use ($assignment, $actor) {
            $assignment = RecruiterTalentPoolAssignment::query()->lockForUpdate()->findOrFail($assignment->id);

            if ($this->isAdminOperator($actor)) {
                $this->assertAdminOperator($actor);
            } else {
                $this->assertRecruiterOwnsAssignment($actor, $assignment);
            }

            if ($assignment->archived_at !== null) {
                return $assignment;
            }

            $assignment->update(['archived_at' => now()]);

            return $assignment->fresh() ?? $assignment;
        });
    }

    private function recordStatusEvent(
        RecruiterTalentPoolAssignment $assignment,
        ?RecruiterTalentPoolAssignmentStatus $from,
        RecruiterTalentPoolAssignmentStatus $to,
        RecruiterTalentPoolAssignmentProgressStage $stage,
        User $actor,
        ?string $notes,
    ): void {
        RecruiterTalentPoolAssignmentStatusEvent::query()->create([
            'recruiter_talent_pool_assignment_id' => $assignment->id,
            'from_status' => $from?->value,
            'to_status' => $to->value,
            'progress_stage' => $stage->value,
            'notes' => $notes,
            'actor_id' => $actor->id,
        ]);
    }

    private function isAdminOperator(User $user): bool
    {
        return $user->hasPermission(PermissionName::CandidatesManage->value);
    }

    private function assertAdminOperator(User $admin): void
    {
        if (! $this->isAdminOperator($admin)) {
            throw ValidationException::withMessages([
                'actor' => 'Only authorized admin staff can manage talent pool assignments.',
            ]);
        }
    }

    private function assertCandidate(User $candidate): void
    {
        if (! $candidate->isCandidate() || $candidate->candidateProfile === null) {
            throw ValidationException::withMessages([
                'candidate_user_id' => 'Select a valid candidate profile.',
            ]);
        }
    }

    private function assertRecruiter(User $recruiter): void
    {
        if (! OurNearInternalRecruiters::canActAsInternalRecruiter($recruiter)) {
            throw ValidationException::withMessages([
                'assigned_recruiter_user_id' => 'Select an eligible OurNear internal recruiter.',
            ]);
        }
    }

    private function assertAssignmentContext(
        RecruiterTalentPoolAssignmentType $type,
        ?Job $job,
        ?EmployerEnquiry $enquiry,
    ): void {
        match ($type) {
            RecruiterTalentPoolAssignmentType::Job => $job !== null || throw ValidationException::withMessages([
                'job_id' => 'Select a job for this assignment type.',
            ]),
            RecruiterTalentPoolAssignmentType::Staffing => $enquiry !== null || throw ValidationException::withMessages([
                'employer_enquiry_id' => 'Select a staffing requirement for this assignment type.',
            ]),
            RecruiterTalentPoolAssignmentType::TalentPool, RecruiterTalentPoolAssignmentType::General => null,
        };

        if (in_array($type, [RecruiterTalentPoolAssignmentType::General, RecruiterTalentPoolAssignmentType::TalentPool], true)
            && ($job !== null || $enquiry !== null)) {
            throw ValidationException::withMessages([
                'assignment_type' => 'This assignment type must not include a job or staffing requirement.',
            ]);
        }
    }

    private function assertShortlistedWhenRequired(
        RecruiterTalentPoolAssignmentType $type,
        User $candidate,
        ?Application $application,
    ): void {
        if ($type === RecruiterTalentPoolAssignmentType::Staffing) {
            return;
        }

        if (in_array($type, [
            RecruiterTalentPoolAssignmentType::General,
            RecruiterTalentPoolAssignmentType::TalentPool,
            RecruiterTalentPoolAssignmentType::Staffing,
        ], true)) {
            return;
        }

        if ($application !== null) {
            if ($application->status !== ApplicationStatus::Shortlisted) {
                throw ValidationException::withMessages([
                    'application_id' => 'The linked application must be shortlisted.',
                ]);
            }

            return;
        }

        if ($type === RecruiterTalentPoolAssignmentType::Job) {
            return;
        }

        $hasShortlisted = Application::query()
            ->where('user_id', $candidate->id)
            ->where('status', ApplicationStatus::Shortlisted)
            ->exists();

        if (! $hasShortlisted) {
            throw ValidationException::withMessages([
                'candidate_user_id' => 'Assign only candidates with at least one shortlisted application for staffing assignments without a linked application.',
            ]);
        }
    }

    private function assertNoDuplicateActiveAssignment(
        User $candidate,
        User $recruiter,
        RecruiterTalentPoolAssignmentType $type,
        ?Job $job,
        ?EmployerEnquiry $enquiry,
    ): void {
        $query = RecruiterTalentPoolAssignment::query()
            ->where('candidate_user_id', $candidate->id)
            ->where('assigned_recruiter_user_id', $recruiter->id)
            ->whereIn('status', [
                RecruiterTalentPoolAssignmentStatus::PendingReview->value,
                RecruiterTalentPoolAssignmentStatus::InProgress->value,
            ])
            ->whereNull('archived_at');

        if ($type === RecruiterTalentPoolAssignmentType::Job && $job !== null) {
            $query->where('job_id', $job->id);
        } elseif ($type === RecruiterTalentPoolAssignmentType::Staffing && $enquiry !== null) {
            $query->where('employer_enquiry_id', $enquiry->id);
        } elseif ($type === RecruiterTalentPoolAssignmentType::General) {
            $query->where('assignment_type', RecruiterTalentPoolAssignmentType::General->value);
        } elseif ($type === RecruiterTalentPoolAssignmentType::TalentPool) {
            $query->where('assignment_type', RecruiterTalentPoolAssignmentType::TalentPool->value);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'assignment' => 'An active assignment already exists for this recruiter and requirement.',
            ]);
        }
    }

    private function assertApplicationBelongsToCandidate(Application $application, User $candidate): void
    {
        if ((int) $application->user_id !== (int) $candidate->id) {
            throw ValidationException::withMessages([
                'application_id' => 'The application does not belong to the selected candidate.',
            ]);
        }
    }

    private function assertJobBelongsToRecruiter(Job $job, User $recruiter): void
    {
        if (OurNearInternalRecruiters::canActAsInternalRecruiter($recruiter)) {
            return;
        }

        $recruiter->loadMissing('employerProfile');
        $profile = $recruiter->employerProfile;
        if ($profile === null || (int) $job->employer_id !== (int) $profile->id) {
            throw ValidationException::withMessages([
                'job_id' => 'The job must belong to the selected recruiter company.',
            ]);
        }
    }

    private function assertEnquiryEligibleForStaffingAssignment(EmployerEnquiry $enquiry): void
    {
        $eligible = StaffingRequirementAllocationQuery::findEligible($enquiry->id);
        if ($eligible === null) {
            throw ValidationException::withMessages([
                'employer_enquiry_id' => 'Select an eligible staffing requirement from an approved company.',
            ]);
        }
    }

    private function assertRecruiterOwnsAssignment(User $recruiter, RecruiterTalentPoolAssignment $assignment): void
    {
        $owns = (int) $assignment->assigned_recruiter_user_id === (int) $recruiter->id
            && (
                OurNearInternalRecruiters::canActAsInternalRecruiter($recruiter)
                || RecruitingEmployerAccounts::canActAsRecruiter($recruiter)
            );

        if (! $owns) {
            throw ValidationException::withMessages([
                'assignment' => 'You are not authorized to update this assignment.',
            ]);
        }
    }
}
