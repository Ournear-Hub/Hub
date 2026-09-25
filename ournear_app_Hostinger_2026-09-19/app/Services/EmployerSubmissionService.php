<?php

namespace App\Services;

use App\Enums\EmployerSubmissionStatus;
use App\Enums\RecruitmentActivityType;
use App\Enums\ScreeningStage;
use App\Models\Application;
use App\Models\EmployerSubmission;
use App\Models\Job;
use App\Models\RejectionReason;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmployerSubmissionService
{
    public function __construct(
        private RecruitmentActivityService $activities,
        private ReplacementService $replacements,
        private AdminAlertService $alerts,
    ) {}

    public function submit(Application $application, User $actor): EmployerSubmission
    {
        return DB::transaction(function () use ($application, $actor) {
            $application = Application::query()->lockForUpdate()->findOrFail($application->id);
            $application->load(['job.employer', 'screening']);

            $this->assertCanSubmit($application);

            $job = $application->job;
            $employer = $job->employer;

            $submission = EmployerSubmission::query()->create([
                'employer_profile_id' => $employer->id,
                'job_id' => $job->id,
                'application_id' => $application->id,
                'status' => EmployerSubmissionStatus::Submitted,
                'submitted_by' => $actor->id,
                'submitted_at' => now(),
            ]);

            $this->activities->record(
                actor: $actor,
                type: RecruitmentActivityType::CandidateSubmitted,
                application: $application,
                body: 'Submitted to '.$employer->displayCompany().' for '.$job->title.'.',
                submission: $submission,
                employer: $employer,
            );

            if ($application->screening) {
                $application->screening->update([
                    'stage' => ScreeningStage::Submitted,
                ]);
            }

            $this->replacements->syncSubmissionCreated($submission->fresh() ?? $submission, $actor);

            $created = $submission->fresh() ?? $submission;
            $this->alerts->submissionCreated($created);

            return $created;
        });
    }

    public function select(EmployerSubmission $submission, User $actor, ?string $notes = null): EmployerSubmission
    {
        $decided = $this->decide(
            $submission,
            $actor,
            EmployerSubmissionStatus::Selected,
            RecruitmentActivityType::EmployerSelected,
            'Employer selected this candidate for the job.',
            $notes,
        );
        $this->alerts->employerSelected($decided);

        return $decided;
    }

    public function reject(
        EmployerSubmission $submission,
        User $actor,
        ?int $rejectionReasonId = null,
        ?string $notes = null,
    ): EmployerSubmission {
        $reason = null;
        if ($rejectionReasonId) {
            $reason = RejectionReason::query()->find($rejectionReasonId);
            if ($reason === null || ! $reason->is_active) {
                throw ValidationException::withMessages([
                    'rejection_reason_id' => 'A valid rejection reason is required.',
                ]);
            }
        }

        $body = 'Employer rejected this candidate for the job.';
        if ($reason) {
            $body .= ' Reason: '.$reason->name.'.';
        }

        $decided = $this->decide(
            $submission,
            $actor,
            EmployerSubmissionStatus::Rejected,
            RecruitmentActivityType::EmployerRejected,
            trim($body.($notes ? ' '.$notes : '')),
            $notes,
            $reason?->id,
        );
        $this->alerts->employerRejected($decided);

        return $decided;
    }

    public function hold(EmployerSubmission $submission, User $actor, ?string $notes = null): EmployerSubmission
    {
        return $this->decide(
            $submission,
            $actor,
            EmployerSubmissionStatus::OnHold,
            RecruitmentActivityType::SubmissionStatusChanged,
            'Employer put this submission on hold.',
            $notes,
        );
    }

    public function review(EmployerSubmission $submission, User $actor, ?string $notes = null): EmployerSubmission
    {
        return $this->decide(
            $submission,
            $actor,
            EmployerSubmissionStatus::UnderReview,
            RecruitmentActivityType::SubmissionStatusChanged,
            'Employer marked this submission as under review.',
            $notes,
        );
    }

    private function decide(
        EmployerSubmission $submission,
        User $actor,
        EmployerSubmissionStatus $status,
        RecruitmentActivityType $activity,
        string $body,
        ?string $notes,
        ?int $rejectionReasonId = null,
    ): EmployerSubmission {
        return DB::transaction(function () use ($submission, $actor, $status, $activity, $body, $notes, $rejectionReasonId) {
            $submission = EmployerSubmission::query()->lockForUpdate()->findOrFail($submission->id);
            $from = $submission->status;

            if ($from === $status) {
                throw ValidationException::withMessages([
                    'status' => 'This submission is already '.$status->value.'.',
                ]);
            }

            if ($from->isTerminal()) {
                throw ValidationException::withMessages([
                    'status' => 'A final employer decision has already been recorded for this submission.',
                ]);
            }

            $submission->status = $status;
            $submission->notes = $notes ?: $submission->notes;
            $submission->rejection_reason_id = $status === EmployerSubmissionStatus::Rejected ? $rejectionReasonId : null;
            $submission->decided_by = $actor->id;
            $submission->decided_at = now();
            $submission->save();

            $this->activities->record(
                actor: $actor,
                type: $activity,
                application: $submission->application,
                body: $body,
                fromStatus: $from->value,
                toStatus: $status->value,
                submission: $submission,
                employer: $submission->employer,
            );

            return $submission->fresh() ?? $submission;
        });
    }

    private function assertCanSubmit(Application $application): void
    {
        $reason = $application->submitBlockedReason();
        if ($reason !== null) {
            throw ValidationException::withMessages([
                'application' => $reason,
            ]);
        }

        $job = $application->job;
        if (! $job instanceof Job || $job->employer === null) {
            throw ValidationException::withMessages([
                'application' => 'This job is not assigned to an employer yet.',
            ]);
        }

        if ((int) $application->job_id !== (int) $job->id) {
            throw ValidationException::withMessages([
                'application' => 'The application does not belong to this job.',
            ]);
        }
    }
}
