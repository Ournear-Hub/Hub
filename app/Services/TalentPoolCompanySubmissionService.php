<?php

namespace App\Services;

use App\Enums\EmployerSubmissionStatus;
use App\Enums\PermissionName;
use App\Enums\RecruiterTalentPoolAssignmentProgressStage;
use App\Enums\RecruiterTalentPoolAssignmentStatus;
use App\Enums\RecruitmentActivityType;
use App\Enums\TalentPoolSubmissionOwnerResolution;
use App\Models\EmployerProfile;
use App\Models\RecruiterTalentPoolAssignment;
use App\Models\RejectionReason;
use App\Models\TalentPoolCompanySubmission;
use App\Models\RecruiterTalentPoolAssignmentStatusEvent;
use App\Models\TalentPoolSubmissionStatusEvent;
use App\Models\User;
use App\Support\OurNearInternalRecruiters;
use App\Support\TalentPoolSubmissionOwnerResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TalentPoolCompanySubmissionService
{
    public function __construct(
        private TalentPoolSubmissionOwnerResolver $ownerResolver,
        private RecruitmentActivityService $activities,
    ) {}

    public function submit(
        User $candidate,
        EmployerProfile $employer,
        User $actor,
        ?RecruiterTalentPoolAssignment $assignment = null,
        ?User $explicitResponsibleRecruiter = null,
        ?string $internalNotes = null,
    ): TalentPoolCompanySubmission {
        return DB::transaction(function () use ($candidate, $employer, $actor, $assignment, $explicitResponsibleRecruiter, $internalNotes) {
            $this->assertCandidate($candidate);
            $this->assertActor($actor, $candidate, $employer, $assignment);

            if ($this->hasActiveSubmission($candidate->id, $employer->id)) {
                throw ValidationException::withMessages([
                    'submission' => 'An active Talent Pool submission already exists for this candidate and company.',
                ]);
            }

            $owner = $this->ownerResolver->resolve($candidate, $employer, $explicitResponsibleRecruiter, $assignment);

            if ($owner['owner_resolution'] === TalentPoolSubmissionOwnerResolution::Unassigned
                && ! $this->isAdminSubmitter($actor)) {
                throw ValidationException::withMessages([
                    'responsible_recruiter_user_id' => 'Select a responsible OurNear recruiter or assign one to the candidate or company first.',
                ]);
            }

            if ($assignment !== null && $assignment->talent_pool_company_submission_id !== null) {
                throw ValidationException::withMessages([
                    'assignment' => 'This assignment was already submitted to a company.',
                ]);
            }

            $submission = TalentPoolCompanySubmission::query()->create([
                'candidate_user_id' => $candidate->id,
                'employer_profile_id' => $employer->id,
                'responsible_recruiter_user_id' => $owner['responsible_recruiter_user_id'],
                'candidate_owner_recruiter_user_id' => $owner['candidate_owner_recruiter_user_id'],
                'company_owner_recruiter_user_id' => $owner['company_owner_recruiter_user_id'],
                'owner_resolution' => $owner['owner_resolution'],
                'submitted_by_user_id' => $actor->id,
                'recruiter_talent_pool_assignment_id' => $owner['recruiter_talent_pool_assignment_id'],
                'status' => EmployerSubmissionStatus::Submitted,
                'internal_notes' => $internalNotes,
                'submitted_at' => now(),
            ]);

            $this->recordStatusEvent($submission, null, EmployerSubmissionStatus::Submitted, $actor, $internalNotes);

            if ($assignment !== null) {
                $this->linkAssignment($assignment, $submission, $actor);
            } elseif ($owner['recruiter_talent_pool_assignment_id'] !== null) {
                $linked = RecruiterTalentPoolAssignment::query()->find($owner['recruiter_talent_pool_assignment_id']);
                if ($linked instanceof RecruiterTalentPoolAssignment) {
                    $this->linkAssignment($linked, $submission, $actor);
                }
            }

            $this->activities->record(
                actor: $actor,
                type: RecruitmentActivityType::TalentPoolSubmittedToCompany,
                body: trim(($candidate->name ?? 'Candidate').' submitted to '.$employer->displayCompany().' via Talent Pool.'),
                employer: $employer,
                talentPoolSubmission: $submission,
            );

            app(TalentPoolCoordinationNotificationService::class)->submissionCreated($submission, $actor);

            return $submission->fresh() ?? $submission;
        });
    }

    public function review(TalentPoolCompanySubmission $submission, User $actor, ?string $notes = null): TalentPoolCompanySubmission
    {
        return $this->decide($submission, $actor, EmployerSubmissionStatus::UnderReview, $notes);
    }

    public function hold(TalentPoolCompanySubmission $submission, User $actor, ?string $notes = null): TalentPoolCompanySubmission
    {
        return $this->decide($submission, $actor, EmployerSubmissionStatus::OnHold, $notes);
    }

    public function select(TalentPoolCompanySubmission $submission, User $actor, ?string $notes = null): TalentPoolCompanySubmission
    {
        return $this->decide($submission, $actor, EmployerSubmissionStatus::Selected, $notes);
    }

    public function reject(
        TalentPoolCompanySubmission $submission,
        User $actor,
        ?int $rejectionReasonId = null,
        ?string $notes = null,
    ): TalentPoolCompanySubmission {
        if ($rejectionReasonId !== null) {
            $reason = RejectionReason::query()->find($rejectionReasonId);
            if ($reason === null || ! $reason->is_active) {
                throw ValidationException::withMessages([
                    'rejection_reason_id' => 'A valid rejection reason is required.',
                ]);
            }
        }

        return DB::transaction(function () use ($submission, $actor, $rejectionReasonId, $notes) {
            $submission = TalentPoolCompanySubmission::query()->lockForUpdate()->findOrFail($submission->id);
            $from = $submission->status;

            if ($from->isTerminal()) {
                throw ValidationException::withMessages([
                    'status' => 'A final decision has already been recorded.',
                ]);
            }

            $submission->forceFill([
                'status' => EmployerSubmissionStatus::Rejected,
                'employer_notes' => $notes ?: $submission->employer_notes,
                'rejection_reason_id' => $rejectionReasonId,
                'decided_by_user_id' => $actor->id,
                'decided_at' => now(),
            ])->save();

            $this->recordStatusEvent($submission, $from, EmployerSubmissionStatus::Rejected, $actor, $notes);

            $this->activities->record(
                actor: $actor,
                type: RecruitmentActivityType::EmployerRejected,
                body: 'Employer rejected this Talent Pool candidate.',
                fromStatus: $from->value,
                toStatus: EmployerSubmissionStatus::Rejected->value,
                employer: $submission->employerProfile,
                talentPoolSubmission: $submission,
            );

            app(TalentPoolCoordinationNotificationService::class)->employerStatusChanged($submission, $actor, EmployerSubmissionStatus::Rejected);

            return $submission->fresh() ?? $submission;
        });
    }

    private function decide(
        TalentPoolCompanySubmission $submission,
        User $actor,
        EmployerSubmissionStatus $toStatus,
        ?string $notes,
    ): TalentPoolCompanySubmission {
        return DB::transaction(function () use ($submission, $actor, $toStatus, $notes) {
            $submission = TalentPoolCompanySubmission::query()->lockForUpdate()->findOrFail($submission->id);
            $from = $submission->status;

            if ($from === $toStatus) {
                throw ValidationException::withMessages([
                    'status' => 'This submission is already '.$toStatus->value.'.',
                ]);
            }

            if ($from->isTerminal()) {
                throw ValidationException::withMessages([
                    'status' => 'A final decision has already been recorded.',
                ]);
            }

            $submission->forceFill([
                'status' => $toStatus,
                'employer_notes' => $notes ?: $submission->employer_notes,
                'decided_by_user_id' => $actor->id,
                'decided_at' => now(),
            ])->save();

            $this->recordStatusEvent($submission, $from, $toStatus, $actor, $notes);

            $this->activities->record(
                actor: $actor,
                type: RecruitmentActivityType::SubmissionStatusChanged,
                body: 'Employer updated Talent Pool submission to '.$toStatus->value.'.',
                fromStatus: $from->value,
                toStatus: $toStatus->value,
                employer: $submission->employerProfile,
                talentPoolSubmission: $submission,
            );

            app(TalentPoolCoordinationNotificationService::class)->employerStatusChanged($submission, $actor, $toStatus);

            return $submission->fresh() ?? $submission;
        });
    }

    private function linkAssignment(
        RecruiterTalentPoolAssignment $assignment,
        TalentPoolCompanySubmission $submission,
        User $actor,
    ): void {
        $assignment = RecruiterTalentPoolAssignment::query()->lockForUpdate()->findOrFail($assignment->id);
        $fromStatus = $assignment->status;
        $stage = RecruiterTalentPoolAssignmentProgressStage::SubmittedToEmployer;
        $toStatus = $stage->syncCoarseStatus();

        $assignment->update([
            'talent_pool_company_submission_id' => $submission->id,
            'progress_stage' => $stage,
            'status' => $toStatus,
        ]);

        RecruiterTalentPoolAssignmentStatusEvent::query()->create([
            'recruiter_talent_pool_assignment_id' => $assignment->id,
            'from_status' => $fromStatus->value,
            'to_status' => $toStatus->value,
            'progress_stage' => $stage->value,
            'notes' => 'Submitted to company via Talent Pool coordination.',
            'actor_id' => $actor->id,
        ]);
    }

    private function recordStatusEvent(
        TalentPoolCompanySubmission $submission,
        ?EmployerSubmissionStatus $from,
        EmployerSubmissionStatus $to,
        User $actor,
        ?string $notes,
    ): void {
        TalentPoolSubmissionStatusEvent::query()->create([
            'talent_pool_company_submission_id' => $submission->id,
            'from_status' => $from?->value,
            'to_status' => $to->value,
            'notes' => $notes,
            'actor_id' => $actor->id,
        ]);
    }

    private function hasActiveSubmission(int $candidateUserId, int $employerProfileId): bool
    {
        return TalentPoolCompanySubmission::query()
            ->where('candidate_user_id', $candidateUserId)
            ->where('employer_profile_id', $employerProfileId)
            ->whereNotIn('status', [
                EmployerSubmissionStatus::Selected->value,
                EmployerSubmissionStatus::Rejected->value,
            ])
            ->exists();
    }

    private function assertCandidate(User $candidate): void
    {
        if (! $candidate->isCandidate() || $candidate->candidateProfile === null) {
            throw ValidationException::withMessages([
                'candidate_user_id' => 'Select a valid candidate.',
            ]);
        }
    }

    private function assertActor(
        User $actor,
        User $candidate,
        EmployerProfile $employer,
        ?RecruiterTalentPoolAssignment $assignment,
    ): void {
        if ($this->isAdminSubmitter($actor)) {
            return;
        }

        if (OurNearInternalRecruiters::shouldScopeTalentPoolWorkspace($actor)) {
            $allowed = (int) $assignment?->assigned_recruiter_user_id === (int) $actor->id
                || RecruiterTalentPoolAssignment::query()
                    ->where('candidate_user_id', $candidate->id)
                    ->where('assigned_recruiter_user_id', $actor->id)
                    ->whereNull('archived_at')
                    ->exists()
                || \App\Models\CompanyRelationshipRecruiterAssignment::query()
                    ->where('employer_profile_id', $employer->id)
                    ->where('assigned_recruiter_user_id', $actor->id)
                    ->where('status', \App\Enums\CompanyRelationshipRecruiterAssignmentStatus::Active->value)
                    ->exists();

            if (! $allowed) {
                throw ValidationException::withMessages([
                    'actor' => 'You are not authorized to submit for this candidate or company.',
                ]);
            }

            return;
        }

        if (OurNearInternalRecruiters::canActAsInternalRecruiter($actor)) {
            return;
        }

        throw ValidationException::withMessages([
            'actor' => 'You are not authorized to create Talent Pool submissions.',
        ]);
    }

    private function isAdminSubmitter(User $actor): bool
    {
        return $actor->isAdmin()
            && ($actor->hasPermission(PermissionName::CandidatesManage->value)
                || $actor->hasPermission(PermissionName::SubmissionsManage->value));
    }
}
