<?php

namespace App\Services;

use App\Enums\EmployerSubmissionStatus;
use App\Enums\PlacementStatus;
use App\Enums\RecruitmentActivityType;
use App\Models\Employee;
use App\Models\EmployerSubmission;
use App\Models\Placement;
use App\Models\PlacementOffer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PlacementService
{
    public function __construct(private RecruitmentActivityService $activities) {}

    public function createFromSubmission(EmployerSubmission $submission, User $actor, ?string $notes = null): Placement
    {
        return DB::transaction(function () use ($submission, $actor, $notes) {
            $submission = EmployerSubmission::query()->lockForUpdate()->findOrFail($submission->id);
            $submission->load(['employer', 'job', 'application.user.candidateProfile']);

            $this->assertCanCreate($submission);

            $placement = Placement::query()->create([
                'employer_submission_id' => $submission->id,
                'employer_profile_id' => $submission->employer_profile_id,
                'job_id' => $submission->job_id,
                'application_id' => $submission->application_id,
                'status' => PlacementStatus::Open,
                'notes' => $notes,
                'created_by' => $actor->id,
            ]);

            $this->record(
                $actor,
                RecruitmentActivityType::PlacementCreated,
                $placement,
                'Placement created from employer-selected submission.',
                null,
                PlacementStatus::Open->value,
            );

            return $placement->fresh() ?? $placement;
        });
    }

    public function offer(
        Placement $placement,
        User $actor,
        string $offeredAt,
        ?string $salary = null,
        ?string $notes = null,
    ): Placement {
        return DB::transaction(function () use ($placement, $actor, $offeredAt, $salary, $notes) {
            $placement = Placement::query()->lockForUpdate()->findOrFail($placement->id);
            $from = $placement->status;

            if (! $placement->canOffer()) {
                throw ValidationException::withMessages([
                    'status' => 'An offer can only be recorded for an open placement.',
                ]);
            }

            PlacementOffer::query()->create([
                'placement_id' => $placement->id,
                'offered_at' => $offeredAt,
                'salary' => $salary ?: null,
                'notes' => $notes,
                'created_by' => $actor->id,
            ]);

            $placement->status = PlacementStatus::Offered;
            $placement->save();

            $this->record(
                $actor,
                RecruitmentActivityType::OfferCreated,
                $placement,
                'Offer recorded for this placement.',
                $from->value,
                PlacementStatus::Offered->value,
            );

            return $placement->fresh() ?? $placement;
        });
    }

    public function accept(Placement $placement, User $actor, ?string $notes = null): Placement
    {
        return DB::transaction(function () use ($placement, $actor, $notes) {
            $placement = Placement::query()->lockForUpdate()->with('offer')->findOrFail($placement->id);
            $from = $placement->status;

            if (! $placement->canAccept()) {
                throw ValidationException::withMessages([
                    'status' => 'Only an offered placement can be accepted.',
                ]);
            }

            $offer = $placement->offer;
            $offer->accepted_at = now();
            if ($notes) {
                $offer->notes = trim((string) $offer->notes.($offer->notes ? "\n" : '').$notes);
            }
            $offer->save();

            $placement->status = PlacementStatus::Accepted;
            $placement->save();

            $this->record(
                $actor,
                RecruitmentActivityType::OfferAccepted,
                $placement,
                'Offer accepted.',
                $from->value,
                PlacementStatus::Accepted->value,
            );

            return $placement->fresh() ?? $placement;
        });
    }

    public function decline(Placement $placement, User $actor, ?string $notes = null): Placement
    {
        return DB::transaction(function () use ($placement, $actor, $notes) {
            $placement = Placement::query()->lockForUpdate()->with('offer')->findOrFail($placement->id);
            $from = $placement->status;

            if (! $placement->canDecline()) {
                throw ValidationException::withMessages([
                    'status' => 'Only an offered placement can be declined.',
                ]);
            }

            $offer = $placement->offer;
            $offer->declined_at = now();
            $offer->decline_notes = $notes;
            $offer->save();

            $placement->status = PlacementStatus::Declined;
            $placement->save();

            $this->record(
                $actor,
                RecruitmentActivityType::OfferDeclined,
                $placement,
                'Offer declined.'.($notes ? ' '.$notes : ''),
                $from->value,
                PlacementStatus::Declined->value,
            );

            return $placement->fresh() ?? $placement;
        });
    }

    public function confirmJoining(
        Placement $placement,
        User $actor,
        string $expectedJoiningDate,
        ?string $notes = null,
    ): Placement {
        return DB::transaction(function () use ($placement, $actor, $expectedJoiningDate, $notes) {
            $placement = Placement::query()->lockForUpdate()->findOrFail($placement->id);
            $from = $placement->status;

            if (! $placement->canConfirmJoining()) {
                throw ValidationException::withMessages([
                    'status' => 'Joining can only be confirmed after the offer is accepted.',
                ]);
            }

            $placement->status = PlacementStatus::JoiningConfirmed;
            $placement->expected_joining_date = $expectedJoiningDate;
            $placement->joining_confirmed_at = now();
            $placement->joining_confirmed_by = $actor->id;
            $placement->joining_notes = $notes ?: $placement->joining_notes;
            $placement->save();

            $this->record(
                $actor,
                RecruitmentActivityType::JoiningConfirmed,
                $placement,
                'Joining confirmed for '.$expectedJoiningDate.'.',
                $from->value,
                PlacementStatus::JoiningConfirmed->value,
            );

            return $placement->fresh() ?? $placement;
        });
    }

    public function join(Placement $placement, User $actor, string $actualJoiningDate, ?string $notes = null): Placement
    {
        return DB::transaction(function () use ($placement, $actor, $actualJoiningDate, $notes) {
            $placement = Placement::query()
                ->lockForUpdate()
                ->with(['application.user.candidateProfile', 'employee'])
                ->findOrFail($placement->id);
            $from = $placement->status;

            if (! $placement->canJoin()) {
                throw ValidationException::withMessages([
                    'status' => 'A candidate can only join after joining is confirmed.',
                ]);
            }

            if ($placement->employee) {
                throw ValidationException::withMessages([
                    'status' => 'An employee record already exists for this placement.',
                ]);
            }

            $placement->status = PlacementStatus::Joined;
            $placement->actual_joining_date = $actualJoiningDate;
            $placement->joining_notes = $notes ?: $placement->joining_notes;
            $placement->save();

            $employee = Employee::query()->create([
                'placement_id' => $placement->id,
                'employer_profile_id' => $placement->employer_profile_id,
                'job_id' => $placement->job_id,
                'application_id' => $placement->application_id,
                'employer_submission_id' => $placement->employer_submission_id,
                'candidate_profile_id' => $placement->application?->user?->candidateProfile?->id,
                'joined_at' => now(),
                'created_by' => $actor->id,
            ]);

            $this->record(
                $actor,
                RecruitmentActivityType::CandidateJoined,
                $placement,
                'Candidate joined. Employee record created.',
                $from->value,
                PlacementStatus::Joined->value,
                $employee,
            );

            return $placement->fresh() ?? $placement;
        });
    }

    public function withdraw(Placement $placement, User $actor, ?string $notes = null): Placement
    {
        return DB::transaction(function () use ($placement, $actor, $notes) {
            $placement = Placement::query()->lockForUpdate()->with('offer')->findOrFail($placement->id);
            $from = $placement->status;

            if (! $placement->canWithdraw()) {
                throw ValidationException::withMessages([
                    'status' => 'This placement can no longer be withdrawn.',
                ]);
            }

            if ($placement->offer) {
                $placement->offer->withdrawn_at = now();
                $placement->offer->save();
            }

            $placement->status = PlacementStatus::Withdrawn;
            $placement->notes = $notes ?: $placement->notes;
            $placement->save();

            $this->record(
                $actor,
                RecruitmentActivityType::OfferWithdrawn,
                $placement,
                'Placement withdrawn.'.($notes ? ' '.$notes : ''),
                $from->value,
                PlacementStatus::Withdrawn->value,
            );

            return $placement->fresh() ?? $placement;
        });
    }

    private function assertCanCreate(EmployerSubmission $submission): void
    {
        if ($submission->status !== EmployerSubmissionStatus::Selected) {
            throw ValidationException::withMessages([
                'submission' => 'Only an employer-selected submission can enter placement.',
            ]);
        }

        if ($submission->employer === null || $submission->job === null || $submission->application === null) {
            throw ValidationException::withMessages([
                'submission' => 'The selected submission must belong to a real employer, job, and application.',
            ]);
        }

        if ((int) $submission->application->job_id !== (int) $submission->job_id) {
            throw ValidationException::withMessages([
                'submission' => 'The application does not belong to this job.',
            ]);
        }

        if ((int) $submission->job->employer_id !== (int) $submission->employer_profile_id) {
            throw ValidationException::withMessages([
                'submission' => 'The job is not assigned to this employer.',
            ]);
        }

        if (Placement::query()->where('employer_submission_id', $submission->id)->exists()) {
            throw ValidationException::withMessages([
                'submission' => 'A placement already exists for this submission.',
            ]);
        }
    }

    private function record(
        User $actor,
        RecruitmentActivityType $type,
        Placement $placement,
        string $body,
        ?string $fromStatus,
        ?string $toStatus,
        ?Employee $employee = null,
    ): void {
        $placement->loadMissing(['application', 'submission', 'employer']);

        $this->activities->record(
            actor: $actor,
            type: $type,
            application: $placement->application,
            body: $body,
            fromStatus: $fromStatus,
            toStatus: $toStatus,
            submission: $placement->submission,
            employer: $placement->employer,
            placement: $placement,
            employee: $employee,
        );
    }
}
