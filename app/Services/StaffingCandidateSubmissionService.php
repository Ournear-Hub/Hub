<?php

namespace App\Services;

use App\Enums\EmployerEnquiryStatus;
use App\Enums\PermissionName;
use App\Enums\RecruiterTalentPoolAssignmentProgressStage;
use App\Enums\RecruiterTalentPoolAssignmentType;
use App\Enums\RecruitmentActivityType;
use App\Enums\StaffingCandidateSubmissionStatus;
use App\Models\EmployerEnquiry;
use App\Models\RecruiterTalentPoolAssignment;
use App\Models\RecruiterTalentPoolAssignmentStatusEvent;
use App\Models\RejectionReason;
use App\Models\StaffingCandidateSubmission;
use App\Models\StaffingSubmissionStatusEvent;
use App\Models\User;
use App\Support\OurNearInternalRecruiters;
use App\Support\StaffingRequirementAllocationQuery;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StaffingCandidateSubmissionService
{
    public function __construct(
        private RecruitmentActivityService $activities,
    ) {}

    public function submitFromAssignment(
        RecruiterTalentPoolAssignment $assignment,
        User $actor,
        ?string $internalNotes = null,
    ): StaffingCandidateSubmission {
        return DB::transaction(function () use ($assignment, $actor, $internalNotes) {
            $assignment = RecruiterTalentPoolAssignment::query()->lockForUpdate()->findOrFail($assignment->id);

            if ($assignment->assignment_type !== RecruiterTalentPoolAssignmentType::Staffing) {
                throw ValidationException::withMessages([
                    'assignment' => 'Submit to client only for Staffing requirement assignments.',
                ]);
            }

            if ($assignment->staffing_candidate_submission_id !== null) {
                throw ValidationException::withMessages([
                    'submission' => 'This assignment was already submitted to the client.',
                ]);
            }

            $enquiry = $assignment->employerEnquiry;
            if ($enquiry === null) {
                throw ValidationException::withMessages([
                    'employer_enquiry_id' => 'Staffing assignment must be linked to a requirement.',
                ]);
            }

            $this->assertStaffActor($actor, $assignment);
            $this->assertEnquiryEligible($enquiry);

            $candidate = User::query()->findOrFail($assignment->candidate_user_id);
            $this->assertCandidate($candidate);

            if ($this->hasActiveSubmission($candidate->id, $enquiry->id)) {
                throw ValidationException::withMessages([
                    'submission' => 'An active staffing submission already exists for this candidate and requirement.',
                ]);
            }

            $recruiter = User::query()->find($assignment->assigned_recruiter_user_id);

            $submission = StaffingCandidateSubmission::query()->create([
                'employer_enquiry_id' => $enquiry->id,
                'candidate_user_id' => $candidate->id,
                'responsible_recruiter_user_id' => $recruiter?->id,
                'submitted_by_user_id' => $actor->id,
                'recruiter_talent_pool_assignment_id' => $assignment->id,
                'status' => StaffingCandidateSubmissionStatus::SubmittedToClient,
                'internal_notes' => $internalNotes,
                'submitted_at' => now(),
            ]);

            $this->recordStatusEvent(
                $submission,
                null,
                StaffingCandidateSubmissionStatus::SubmittedToClient,
                $actor,
                $internalNotes,
            );

            $this->linkAssignment($assignment, $submission, $actor);

            $this->activities->record(
                actor: $actor,
                type: RecruitmentActivityType::StaffingSubmittedToClient,
                enquiry: $enquiry,
                body: trim(($candidate->name ?? 'Candidate').' submitted to client for staffing requirement #'.$enquiry->id.'.'),
                staffingSubmission: $submission,
                talentPoolAssignment: $assignment,
            );

            if ($enquiry->status === EmployerEnquiryStatus::New) {
                $enquiry->update(['status' => EmployerEnquiryStatus::InProgress]);
            }

            app(EmployerNotificationService::class)->notifyStaffingCandidateSubmitted($submission->fresh() ?? $submission);

            return $submission->fresh() ?? $submission;
        });
    }

    public function clientTransition(
        StaffingCandidateSubmission $submission,
        User $client,
        StaffingCandidateSubmissionStatus $toStatus,
        ?string $feedback = null,
        ?int $rejectionReasonId = null,
        ?Carbon $joiningDate = null,
        ?string $joiningNotes = null,
    ): StaffingCandidateSubmission {
        $this->assertClientOwnsSubmission($client, $submission);

        if ($toStatus === StaffingCandidateSubmissionStatus::JoinedConfirmed) {
            return $this->confirmJoining($submission, $client, $joiningDate ?? now(), $joiningNotes);
        }

        if ($toStatus === StaffingCandidateSubmissionStatus::ClientRejected && $rejectionReasonId !== null) {
            $reason = RejectionReason::query()->find($rejectionReasonId);
            if ($reason === null || ! $reason->is_active) {
                throw ValidationException::withMessages([
                    'rejection_reason_id' => 'A valid rejection reason is required.',
                ]);
            }
        }

        return DB::transaction(function () use ($submission, $client, $toStatus, $feedback, $rejectionReasonId) {
            $submission = StaffingCandidateSubmission::query()->lockForUpdate()->findOrFail($submission->id);
            $from = $submission->status;

            if ($from->isTerminal()) {
                throw ValidationException::withMessages([
                    'status' => 'A final outcome has already been recorded.',
                ]);
            }

            if (! in_array($toStatus, $from->allowedClientTransitions(), true)) {
                throw ValidationException::withMessages([
                    'status' => 'This status change is not allowed from the current stage.',
                ]);
            }

            $submission->forceFill([
                'status' => $toStatus,
                'client_feedback' => $feedback ?: $submission->client_feedback,
                'rejection_reason_id' => $toStatus === StaffingCandidateSubmissionStatus::ClientRejected
                    ? $rejectionReasonId
                    : $submission->rejection_reason_id,
                'decided_by_user_id' => in_array($toStatus, [
                    StaffingCandidateSubmissionStatus::ClientSelected,
                    StaffingCandidateSubmissionStatus::ClientRejected,
                ], true) ? $client->id : $submission->decided_by_user_id,
                'decided_at' => in_array($toStatus, [
                    StaffingCandidateSubmissionStatus::ClientSelected,
                    StaffingCandidateSubmissionStatus::ClientRejected,
                ], true) ? now() : $submission->decided_at,
            ])->save();

            $this->recordStatusEvent($submission, $from, $toStatus, $client, $feedback);

            $this->activities->record(
                actor: $client,
                type: RecruitmentActivityType::StaffingSubmissionStatusChanged,
                enquiry: $submission->employerEnquiry,
                body: 'Client updated staffing candidate submission to '.$toStatus->label().'.',
                fromStatus: $from->value,
                toStatus: $toStatus->value,
                staffingSubmission: $submission,
            );

            return $submission->fresh() ?? $submission;
        });
    }

    public function confirmJoining(
        StaffingCandidateSubmission $submission,
        User $actor,
        Carbon $joiningDate,
        ?string $notes = null,
    ): StaffingCandidateSubmission {
        if (! $this->isClientOwner($actor, $submission) && ! $this->isStaffingOperationsAdmin($actor)) {
            throw ValidationException::withMessages([
                'actor' => 'Only the client or authorized operations staff may confirm joining.',
            ]);
        }

        if ($this->isStaffingOperationsAdmin($actor) && ! $this->isClientOwner($actor, $submission)) {
            throw ValidationException::withMessages([
                'joining' => 'OurNear staff cannot confirm joining without the client. The client must confirm joining on the submission.',
            ]);
        }

        return DB::transaction(function () use ($submission, $actor, $joiningDate, $notes) {
            $submission = StaffingCandidateSubmission::query()->lockForUpdate()->findOrFail($submission->id);
            $from = $submission->status;

            if ($from === StaffingCandidateSubmissionStatus::JoinedConfirmed) {
                return $submission;
            }

            if ($from->isTerminal()) {
                throw ValidationException::withMessages([
                    'status' => 'Joining cannot be confirmed from the current status.',
                ]);
            }

            if (! in_array($from, [
                StaffingCandidateSubmissionStatus::ClientSelected,
                StaffingCandidateSubmissionStatus::OfferInProgress,
            ], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Confirm selection or offer progress before recording joining.',
                ]);
            }

            $submission->forceFill([
                'status' => StaffingCandidateSubmissionStatus::JoinedConfirmed,
                'joining_date' => $joiningDate,
                'joining_confirmation_notes' => $notes,
                'joining_confirmed_by_user_id' => $actor->id,
                'joining_confirmed_at' => now(),
                'decided_by_user_id' => $actor->id,
                'decided_at' => now(),
            ])->save();

            $this->recordStatusEvent($submission, $from, StaffingCandidateSubmissionStatus::JoinedConfirmed, $actor, $notes);
            $this->applyFulfilmentOnce($submission);

            $this->activities->record(
                actor: $actor,
                type: RecruitmentActivityType::JoiningConfirmed,
                enquiry: $submission->employerEnquiry,
                body: 'Joining confirmed for staffing candidate submission '.$submission->business_id.'.',
                fromStatus: $from->value,
                toStatus: StaffingCandidateSubmissionStatus::JoinedConfirmed->value,
                staffingSubmission: $submission,
            );

            app(EmployerNotificationService::class)->notifyStaffingJoiningConfirmed($submission->fresh() ?? $submission);

            return $submission->fresh() ?? $submission;
        });
    }

    private function applyFulfilmentOnce(StaffingCandidateSubmission $submission): void
    {
        if ($submission->fulfilment_counted_at !== null) {
            return;
        }

        $enquiry = EmployerEnquiry::query()->lockForUpdate()->find($submission->employer_enquiry_id);
        if ($enquiry === null) {
            return;
        }

        $enquiry->increment('fulfilled_joinings_count');
        $submission->forceFill(['fulfilment_counted_at' => now()])->save();

        $filled = (int) $enquiry->fresh()->fulfilled_joinings_count;
        $needed = (int) $enquiry->number_of_people;
        if ($needed > 0 && $filled >= $needed && $enquiry->status !== EmployerEnquiryStatus::Completed) {
            $enquiry->update(['status' => EmployerEnquiryStatus::Completed]);
        }
    }

    private function linkAssignment(
        RecruiterTalentPoolAssignment $assignment,
        StaffingCandidateSubmission $submission,
        User $actor,
    ): void {
        $fromStatus = $assignment->status;
        $stage = RecruiterTalentPoolAssignmentProgressStage::SubmittedToEmployer;
        $toStatus = $stage->syncCoarseStatus();

        $assignment->update([
            'staffing_candidate_submission_id' => $submission->id,
            'progress_stage' => $stage,
            'status' => $toStatus,
        ]);

        RecruiterTalentPoolAssignmentStatusEvent::query()->create([
            'recruiter_talent_pool_assignment_id' => $assignment->id,
            'from_status' => $fromStatus->value,
            'to_status' => $toStatus->value,
            'progress_stage' => $stage->value,
            'notes' => 'Submitted to client via Staffing coordination.',
            'actor_id' => $actor->id,
        ]);
    }

    private function recordStatusEvent(
        StaffingCandidateSubmission $submission,
        ?StaffingCandidateSubmissionStatus $from,
        StaffingCandidateSubmissionStatus $to,
        User $actor,
        ?string $notes,
    ): void {
        StaffingSubmissionStatusEvent::query()->create([
            'staffing_candidate_submission_id' => $submission->id,
            'from_status' => $from?->value,
            'to_status' => $to->value,
            'notes' => $notes,
            'actor_id' => $actor->id,
        ]);
    }

    private function hasActiveSubmission(int $candidateUserId, int $enquiryId): bool
    {
        $terminal = array_map(
            fn (StaffingCandidateSubmissionStatus $status) => $status->value,
            array_filter(
                StaffingCandidateSubmissionStatus::cases(),
                fn (StaffingCandidateSubmissionStatus $status) => $status->isTerminal(),
            ),
        );

        return StaffingCandidateSubmission::query()
            ->where('candidate_user_id', $candidateUserId)
            ->where('employer_enquiry_id', $enquiryId)
            ->whereNotIn('status', $terminal)
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

    private function assertEnquiryEligible(EmployerEnquiry $enquiry): void
    {
        if (StaffingRequirementAllocationQuery::findEligible($enquiry->id) === null) {
            throw ValidationException::withMessages([
                'employer_enquiry_id' => 'The staffing requirement is not eligible for candidate submission.',
            ]);
        }
    }

    private function assertStaffActor(User $actor, RecruiterTalentPoolAssignment $assignment): void
    {
        if ($this->isStaffingOperationsAdmin($actor)) {
            return;
        }

        if (OurNearInternalRecruiters::canActAsInternalRecruiter($actor)
            && (int) $assignment->assigned_recruiter_user_id === (int) $actor->id) {
            return;
        }

        throw ValidationException::withMessages([
            'actor' => 'You are not authorized to submit staffing candidates to the client.',
        ]);
    }

    private function assertClientOwnsSubmission(User $client, StaffingCandidateSubmission $submission): void
    {
        if (! $this->isClientOwner($client, $submission)) {
            throw ValidationException::withMessages([
                'submission' => 'You are not authorized to update this staffing submission.',
            ]);
        }
    }

    private function isClientOwner(User $user, StaffingCandidateSubmission $submission): bool
    {
        $submission->loadMissing('employerEnquiry');

        return $submission->employerEnquiry !== null
            && $submission->employerEnquiry->user_id !== null
            && (int) $submission->employerEnquiry->user_id === (int) $user->id
            && $user->employerProfile?->staffing_access_status?->canUseStaffingWorkspace() === true;
    }

    private function isStaffingOperationsAdmin(User $user): bool
    {
        return $user->isAdmin()
            && ($user->hasPermission(PermissionName::CandidatesManage->value)
                || $user->hasPermission(PermissionName::EmployersManage->value));
    }
}
