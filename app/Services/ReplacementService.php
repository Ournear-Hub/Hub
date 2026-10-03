<?php

namespace App\Services;

use App\Enums\PlacementStatus;
use App\Enums\RecruitmentActivityType;
use App\Enums\ReplacementReason;
use App\Enums\ReplacementStatus;
use App\Models\Application;
use App\Models\Employee;
use App\Models\EmployerSubmission;
use App\Models\Placement;
use App\Models\Replacement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReplacementService
{
    public function __construct(
        private RecruitmentActivityService $activities,
        private AdminAlertService $alerts,
    ) {}

    public function request(Employee $employee, User $actor, ReplacementReason $reason, ?string $notes = null): Replacement
    {
        return DB::transaction(function () use ($employee, $actor, $reason, $notes) {
            $employee = Employee::query()->lockForUpdate()->with(['placement', 'application', 'submission', 'employer', 'job'])->findOrFail($employee->id);
            $this->assertCanRequest($employee);

            $replacement = Replacement::query()->create([
                'original_employee_id' => $employee->id,
                'original_placement_id' => $employee->placement_id,
                'employer_profile_id' => $employee->employer_profile_id,
                'job_id' => $employee->job_id,
                'original_application_id' => $employee->application_id,
                'original_submission_id' => $employee->employer_submission_id,
                'reason' => $reason,
                'status' => ReplacementStatus::Requested,
                'notes' => $notes,
                'requested_by' => $actor->id,
                'requested_at' => now(),
            ]);

            $this->record(
                $actor,
                RecruitmentActivityType::ReplacementRequested,
                $replacement,
                'Replacement requested for '.$employee->application?->full_name.'. Reason: '.$reason->value.'.',
                null,
                ReplacementStatus::Requested->value,
                $employee,
                $employee->placement,
            );

            $created = $replacement->fresh() ?? $replacement;
            $this->alerts->replacementRequested($created);
            RecruitmentAutomationHooks::fire(\App\Enums\AutomationTrigger::ReplacementRequested, [
                'replacement' => $created,
                'application' => $created->originalApplication,
                'actor' => $actor,
                'employer_profile_id' => $created->employer_profile_id,
                'job_id' => $created->job_id,
            ]);

            return $created;
        });
    }

    public function identifyApplication(Replacement $replacement, Application $application, User $actor): Replacement
    {
        return DB::transaction(function () use ($replacement, $application, $actor) {
            $replacement = Replacement::query()->lockForUpdate()->with(['replacementPlacement', 'replacementSubmission', 'job', 'employer'])->findOrFail($replacement->id);
            $application = Application::query()->lockForUpdate()->with(['job.employer', 'screening', 'submissions'])->findOrFail($application->id);

            if (! $replacement->canIdentifyCandidate()) {
                throw ValidationException::withMessages([
                    'status' => 'A replacement candidate cannot be changed while a live replacement placement is in progress.',
                ]);
            }

            $this->assertApplicationEligible($replacement, $application);

            $from = $replacement->status;
            $submission = $application->submissions
                ->first(fn ($item) => (int) $item->job_id === (int) $replacement->job_id
                    && (int) $item->employer_profile_id === (int) $replacement->employer_profile_id);

            $replacement->replacement_application_id = $application->id;
            $replacement->replacement_submission_id = $submission?->id;
            $replacement->replacement_placement_id = null;
            $replacement->replacement_employee_id = null;
            $replacement->status = ReplacementStatus::InProgress;
            $replacement->save();

            $this->record(
                $actor,
                RecruitmentActivityType::ReplacementCandidateIdentified,
                $replacement,
                'Replacement candidate identified: '.$application->full_name.'.',
                $from->value,
                ReplacementStatus::InProgress->value,
                $replacement->originalEmployee,
                $replacement->originalPlacement,
                $application,
                $submission,
            );

            $identified = $replacement->fresh() ?? $replacement;
            $this->alerts->replacementCandidateIdentified($identified);

            return $identified;
        });
    }

    public function syncSubmissionCreated(EmployerSubmission $submission, User $actor): void
    {
        $replacement = Replacement::query()
            ->where('replacement_application_id', $submission->application_id)
            ->whereIn('status', [ReplacementStatus::Requested->value, ReplacementStatus::InProgress->value])
            ->orderByDesc('id')
            ->first();

        if ($replacement === null || $replacement->status->isTerminal()) {
            return;
        }

        $replacement->replacement_submission_id = $submission->id;
        $replacement->status = ReplacementStatus::InProgress;
        $replacement->save();
    }

    public function cancel(Replacement $replacement, User $actor, ?string $notes = null): Replacement
    {
        return DB::transaction(function () use ($replacement, $actor, $notes) {
            $replacement = Replacement::query()->lockForUpdate()->findOrFail($replacement->id);
            $from = $replacement->status;

            if (! $replacement->canCancel()) {
                throw ValidationException::withMessages([
                    'status' => 'This replacement case cannot be cancelled.',
                ]);
            }

            $replacement->status = ReplacementStatus::Cancelled;
            $replacement->cancelled_at = now();
            $replacement->notes = $notes ?: $replacement->notes;
            $replacement->save();

            $this->record(
                $actor,
                RecruitmentActivityType::ReplacementCancelled,
                $replacement,
                'Replacement cancelled.'.($notes ? ' '.$notes : ''),
                $from->value,
                ReplacementStatus::Cancelled->value,
                $replacement->originalEmployee,
                $replacement->originalPlacement,
            );

            $cancelled = $replacement->fresh() ?? $replacement;
            $this->alerts->replacementCancelled($cancelled);

            return $cancelled;
        });
    }

    public function syncPlacementCreated(Placement $placement, User $actor): void
    {
        $replacement = Replacement::query()
            ->where('replacement_application_id', $placement->application_id)
            ->whereIn('status', [ReplacementStatus::Requested->value, ReplacementStatus::InProgress->value, ReplacementStatus::Placed->value])
            ->orderByDesc('id')
            ->first();

        if ($replacement === null) {
            return;
        }

        if ($replacement->status === ReplacementStatus::Completed || $replacement->status === ReplacementStatus::Cancelled) {
            return;
        }

        $from = $replacement->status;
        $replacement->replacement_placement_id = $placement->id;
        $replacement->replacement_submission_id = $placement->employer_submission_id;
        $replacement->status = ReplacementStatus::Placed;
        $replacement->save();

        $this->record(
            $actor,
            RecruitmentActivityType::ReplacementPlacementLinked,
            $replacement,
            'Replacement placement created. Original placement was not changed.',
            $from->value,
            ReplacementStatus::Placed->value,
            $replacement->originalEmployee,
            $placement,
            $placement->application,
            $placement->submission,
        );
    }

    public function syncEmployeeJoined(Placement $placement, Employee $employee, User $actor): void
    {
        $replacement = Replacement::query()
            ->where('replacement_placement_id', $placement->id)
            ->where('status', ReplacementStatus::Placed->value)
            ->first();

        if ($replacement === null) {
            return;
        }

        $from = $replacement->status;
        $replacement->replacement_employee_id = $employee->id;
        $replacement->status = ReplacementStatus::Completed;
        $replacement->completed_at = now();
        $replacement->save();

        $this->record(
            $actor,
            RecruitmentActivityType::ReplacementCompleted,
            $replacement,
            'Replacement completed after the new employee joined. Original employee remains on file.',
            $from->value,
            ReplacementStatus::Completed->value,
            $employee,
            $placement,
            $placement->application,
            $placement->submission,
        );

        $this->alerts->replacementCompleted($replacement->fresh() ?? $replacement);
    }

    private function assertCanRequest(Employee $employee): void
    {
        $placement = $employee->placement;
        if ($placement === null || $placement->status !== PlacementStatus::Joined) {
            throw ValidationException::withMessages([
                'employee' => 'A replacement can only be created from a joined employee.',
            ]);
        }

        if ($employee->employer === null || $employee->job === null || $employee->application === null || $employee->submission === null) {
            throw ValidationException::withMessages([
                'employee' => 'The employee must retain a complete employer, job, application, and submission lineage.',
            ]);
        }

        $open = Replacement::query()
            ->where('original_employee_id', $employee->id)
            ->whereIn('status', [
                ReplacementStatus::Requested->value,
                ReplacementStatus::InProgress->value,
                ReplacementStatus::Placed->value,
            ])
            ->exists();

        if ($open) {
            throw ValidationException::withMessages([
                'employee' => 'An open replacement case already exists for this employee.',
            ]);
        }

        $completed = Replacement::query()
            ->where('original_employee_id', $employee->id)
            ->where('status', ReplacementStatus::Completed->value)
            ->exists();

        if ($completed) {
            throw ValidationException::withMessages([
                'employee' => 'This employee has already been replaced. Start the next replacement from the current joined employee.',
            ]);
        }
    }

    private function assertApplicationEligible(Replacement $replacement, Application $application): void
    {
        if ((int) $application->job_id !== (int) $replacement->job_id) {
            throw ValidationException::withMessages([
                'application' => 'The replacement candidate must apply for the original job.',
            ]);
        }

        if ((int) $application->id === (int) $replacement->original_application_id) {
            throw ValidationException::withMessages([
                'application' => 'The original employee application cannot be reused as the replacement candidate.',
            ]);
        }

        $hasSubmission = $application->submissions
            ->contains(fn ($item) => (int) $item->job_id === (int) $replacement->job_id
                && (int) $item->employer_profile_id === (int) $replacement->employer_profile_id);

        if (! $hasSubmission && ! $application->isEligibleForEmployerSubmission()) {
            throw ValidationException::withMessages([
                'application' => 'Only a shortlisted application for this job can be identified as the replacement candidate.',
            ]);
        }
    }

    private function record(
        User $actor,
        RecruitmentActivityType $type,
        Replacement $replacement,
        string $body,
        ?string $fromStatus,
        ?string $toStatus,
        ?Employee $employee = null,
        ?Placement $placement = null,
        ?Application $application = null,
        $submission = null,
    ): void {
        $replacement->loadMissing(['originalEmployee', 'originalPlacement', 'employer']);

        $this->activities->record(
            actor: $actor,
            type: $type,
            application: $application ?? $replacement->originalPlacement?->application,
            body: $body,
            fromStatus: $fromStatus,
            toStatus: $toStatus,
            submission: $submission ?? $replacement->originalPlacement?->submission,
            employer: $replacement->employer,
            placement: $placement ?? $replacement->originalPlacement,
            employee: $employee ?? $replacement->originalEmployee,
            replacement: $replacement,
        );
    }
}
