<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Enums\AutomationTrigger;
use App\Enums\RecruitmentActivityType;
use App\Enums\ScreeningAction;
use App\Enums\ScreeningDecision;
use App\Enums\ScreeningStage;
use App\Models\Application;
use App\Models\ApplicationInterview;
use App\Models\ApplicationScreening;
use App\Models\RejectionReason;
use App\Models\User;
use App\Support\AppendsEnquiryNotes;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApplicationPipelineService
{
    use AppendsEnquiryNotes;

    public function __construct(
        private RecruitmentActivityService $activities,
        private RecruitmentNotificationService $notifications,
        private AdminAlertService $alerts,
    ) {}

    public function changeStatus(Application $application, ApplicationStatus $status, User $actor): Application
    {
        /** @var array{application: Application, from: ApplicationStatus, changed: bool} $result */
        $result = DB::transaction(function () use ($application, $status, $actor) {
            $locked = Application::query()
                ->whereKey($application->id)
                ->lockForUpdate()
                ->firstOrFail();

            $from = $locked->status;

            if ($from === $status) {
                return [
                    'application' => $locked,
                    'from' => $from,
                    'changed' => false,
                ];
            }

            $this->assertAllowedTransition($locked, $from, $status);

            $locked->update(['status' => $status]);

            $this->activities->record(
                actor: $actor,
                type: RecruitmentActivityType::StatusChanged,
                application: $locked,
                fromStatus: $from->value,
                toStatus: $status->value,
            );

            return [
                'application' => $locked->fresh() ?? $locked,
                'from' => $from,
                'changed' => true,
            ];
        });

        $fresh = $result['application'];
        if ($result['changed']) {
            $from = $result['from'];
            $this->notifications->notifyStatusChanged($fresh, $from, $status);
            RecruitmentAutomationHooks::fire(AutomationTrigger::ApplicationStatusChanged, [
                'application' => $fresh,
                'actor' => $actor,
                'from_status' => $from->value,
                'to_status' => $status->value,
            ]);
        }

        return $fresh;
    }

    public function recordScreeningAction(
        Application $application,
        ScreeningAction $action,
        User $actor,
        ?string $remarks = null,
    ): Application {
        $remarks = $this->normalizedRemarks($remarks);

        return match ($action) {
            ScreeningAction::Start => $this->startScreening($application, $actor, $remarks),
            ScreeningAction::Match => $this->markSuitable($application, $actor, $remarks),
            ScreeningAction::Contact => $this->advanceScreeningStage(
                $application,
                $actor,
                ScreeningStage::Contacted,
                RecruitmentActivityType::CandidateContacted,
                $remarks,
                'Candidate contacted.',
            ),
            ScreeningAction::Called => $this->advanceScreeningStage(
                $application,
                $actor,
                ScreeningStage::Called,
                RecruitmentActivityType::CandidateCalled,
                $remarks,
                'Call completed.',
            ),
            ScreeningAction::Interviewed => $this->advanceScreeningStage(
                $application,
                $actor,
                ScreeningStage::Interviewed,
                RecruitmentActivityType::CandidateInterviewed,
                $remarks,
                'Interview completed.',
            ),
            ScreeningAction::Hold => $this->holdScreening($application, $actor, $remarks),
        };
    }

    public function startScreening(Application $application, User $actor, ?string $remarks = null): Application
    {
        $application->loadMissing('screening');

        if (! $application->canStartScreening()) {
            throw ValidationException::withMessages([
                'action' => 'Screening has already started for this application.',
            ]);
        }

        DB::transaction(function () use ($application, $actor, $remarks) {
            $this->writeScreeningStage(
                $application,
                $actor,
                ScreeningStage::Started,
                ScreeningDecision::Pending,
                $remarks,
            );

            $this->activities->record(
                actor: $actor,
                type: RecruitmentActivityType::ScreeningStarted,
                application: $application,
                body: $remarks ?: 'Screening started.',
            );
        });

        $fresh = $application->fresh() ?? $application;

        if ($fresh->status === ApplicationStatus::New) {
            return $this->changeStatus($fresh, ApplicationStatus::Reviewing, $actor);
        }

        return $fresh;
    }

    public function holdScreening(Application $application, User $actor, ?string $remarks = null): Application
    {
        $application->loadMissing('screening');

        if (! $application->canHoldScreening()) {
            throw ValidationException::withMessages([
                'action' => 'This application cannot be put on hold in its current status.',
            ]);
        }

        DB::transaction(function () use ($application, $actor, $remarks) {
            $this->writeScreeningStage(
                $application,
                $actor,
                ScreeningStage::Held,
                $application->screeningDecision(),
                $remarks,
                hold: true,
            );

            $this->activities->record(
                actor: $actor,
                type: RecruitmentActivityType::ScreeningHeld,
                application: $application,
                body: $remarks ?: 'Put on hold.',
            );
        });

        return $application->fresh() ?? $application;
    }

    private function advanceScreeningStage(
        Application $application,
        User $actor,
        ScreeningStage $stage,
        RecruitmentActivityType $activityType,
        ?string $remarks,
        string $defaultBody,
    ): Application {
        $application->loadMissing('screening');

        if (! $application->canRecordScreeningProgress()) {
            throw ValidationException::withMessages([
                'action' => 'This application cannot be progressed in its current status.',
            ]);
        }

        $body = $remarks ?: $defaultBody;

        DB::transaction(function () use ($application, $actor, $stage, $activityType, $body, $remarks) {
            $this->writeScreeningStage(
                $application,
                $actor,
                $stage,
                $application->screening?->decision ?? ScreeningDecision::Pending,
                $remarks,
            );

            $this->activities->record(
                actor: $actor,
                type: $activityType,
                application: $application,
                body: $body,
            );
        });

        return $application->fresh() ?? $application;
    }

    private function writeScreeningStage(
        Application $application,
        User $actor,
        ScreeningStage $stage,
        ScreeningDecision $decision,
        ?string $remarks,
        bool $hold = false,
    ): ApplicationScreening {
        $existing = $application->screening;
        $attributes = [
            'decision' => $decision,
            'stage' => $stage,
            'screened_by' => $existing?->screened_by ?? $actor->id,
            'screened_at' => $existing?->screened_at ?? now(),
            'held_by' => $hold ? $actor->id : null,
            'held_at' => $hold ? now() : null,
        ];

        if ($remarks !== null && $remarks !== '') {
            $attributes['notes'] = $remarks;
        }

        if ($existing === null) {
            $saved = ApplicationScreening::query()->create([
                'application_id' => $application->id,
                'rejection_reason_id' => null,
                'shortlisted_by' => null,
                'shortlisted_at' => null,
                ...$attributes,
            ]);
        } else {
            $existing->fill($attributes)->save();
            $saved = $existing;
        }

        $application->setRelation('screening', $saved);

        return $saved;
    }

    private function normalizedRemarks(?string $remarks): ?string
    {
        $remarks = trim((string) $remarks);

        return $remarks === '' ? null : $remarks;
    }

    public function withdrawByCandidate(Application $application, User $candidate): Application
    {
        if (! $candidate->isCandidate() || (int) $application->user_id !== (int) $candidate->id) {
            abort(403);
        }

        if (! $application->candidateCanWithdraw()) {
            throw ValidationException::withMessages([
                'application' => $application->status === ApplicationStatus::Placed
                    ? 'Placed applications cannot be withdrawn.'
                    : 'This application cannot be withdrawn.',
            ]);
        }

        return $this->changeStatus($application, ApplicationStatus::Withdrawn, $candidate);
    }

    public function addNote(Application $application, string $note, User $actor): Application
    {
        DB::transaction(function () use ($application, $note, $actor) {
            $application->internal_notes = $this->appendNote($application->notes(), $note, $actor);
            $application->save();

            $this->activities->record(
                actor: $actor,
                type: RecruitmentActivityType::NoteAdded,
                application: $application,
                body: $note,
            );
        });

        return $application->fresh() ?? $application;
    }

    /**
     * @param  array{
     *     scheduled_at: string,
     *     mode: InterviewMode,
     *     location_or_link?: string|null,
     *     interviewer_name: string,
     *     outcome: InterviewOutcome,
     *     notes?: string|null
     * }  $data
     */
    public function saveInterview(Application $application, array $data, User $actor): ApplicationInterview
    {
        return app(InterviewService::class)->schedule($application, $data, $actor);
    }

    public function saveScreening(
        Application $application,
        ScreeningDecision $decision,
        User $actor,
        ?string $notes = null,
        ?int $rejectionReasonId = null,
    ): Application {
        return match ($decision) {
            ScreeningDecision::Shortlisted => $this->shortlist($application, $actor, $notes),
            ScreeningDecision::NotSuitable => $this->markNotSuitable($application, $actor, $rejectionReasonId, $notes),
            ScreeningDecision::Suitable => $this->markSuitable($application, $actor, $notes),
            ScreeningDecision::Pending => $application,
        };
    }

    public function selectForEmployer(Application $application, User $actor, ?string $notes = null): Application
    {
        if (! in_array($application->status, [ApplicationStatus::Shortlisted, ApplicationStatus::Interview], true)) {
            throw ValidationException::withMessages([
                'status' => 'Only shortlisted or interviewed applications can be selected.',
            ]);
        }

        return $this->changeStatus($application, ApplicationStatus::Selected, $actor);
    }

    public function shortlist(Application $application, User $actor, ?string $notes = null): Application
    {
        $application->loadMissing('screening');

        if ($application->screeningDecision() === ScreeningDecision::Shortlisted) {
            throw ValidationException::withMessages([
                'decision' => 'This application is already shortlisted for this job.',
            ]);
        }

        if (! $application->canShortlist()) {
            throw ValidationException::withMessages([
                'decision' => 'This application cannot be shortlisted in its current status.',
            ]);
        }

        DB::transaction(function () use ($application, $actor, $notes) {
            $this->upsertScreening($application, ScreeningDecision::Shortlisted, $actor, $notes, null, true);

            $this->activities->record(
                actor: $actor,
                type: RecruitmentActivityType::Shortlisted,
                application: $application,
                body: 'Shortlisted for this job.',
            );
        });

        $fresh = $application->fresh() ?? $application;

        if (in_array($fresh->status, [ApplicationStatus::New, ApplicationStatus::Reviewing], true)) {
            $fresh = $this->changeStatus($fresh, ApplicationStatus::Shortlisted, $actor);
        }

        $this->alerts->candidateShortlisted($fresh);

        return $fresh;
    }

    public function markSuitable(Application $application, User $actor, ?string $notes = null): Application
    {
        $application->loadMissing('screening');

        if (! $application->canMarkSuitable()) {
            throw ValidationException::withMessages([
                'decision' => 'This application cannot be marked suitable in its current status.',
            ]);
        }

        DB::transaction(function () use ($application, $actor, $notes) {
            $this->upsertScreening($application, ScreeningDecision::Suitable, $actor, $notes);

            $this->activities->record(
                actor: $actor,
                type: RecruitmentActivityType::MarkedSuitable,
                application: $application,
                body: 'Marked suitable for this job.',
            );
        });

        $fresh = $application->fresh() ?? $application;

        if ($fresh->status === ApplicationStatus::New) {
            return $this->changeStatus($fresh, ApplicationStatus::Reviewing, $actor);
        }

        return $fresh;
    }

    public function markNotSuitable(
        Application $application,
        User $actor,
        ?int $rejectionReasonId,
        ?string $notes = null,
    ): Application {
        $application->loadMissing('screening');

        if (! $application->canMarkNotSuitable()) {
            throw ValidationException::withMessages([
                'decision' => 'This application cannot be marked not suitable in its current status.',
            ]);
        }

        $reason = RejectionReason::query()->find($rejectionReasonId);
        if ($reason === null || ! $reason->is_active) {
            throw ValidationException::withMessages([
                'rejection_reason_id' => 'A rejection reason is required.',
            ]);
        }

        DB::transaction(function () use ($application, $actor, $reason, $notes) {
            $this->upsertScreening($application, ScreeningDecision::NotSuitable, $actor, $notes, $reason->id);

            $this->activities->record(
                actor: $actor,
                type: RecruitmentActivityType::MarkedNotSuitable,
                application: $application,
                body: trim('Marked not suitable: '.$reason->name.($notes ? '. '.$notes : '')),
            );
        });

        return $this->changeStatus($application->fresh() ?? $application, ApplicationStatus::Rejected, $actor);
    }

    private function upsertScreening(
        Application $application,
        ScreeningDecision $decision,
        User $actor,
        ?string $notes,
        ?int $rejectionReasonId = null,
        bool $isShortlist = false,
    ): ApplicationScreening {
        $attributes = [
            'decision' => $decision,
            'stage' => match ($decision) {
                ScreeningDecision::Shortlisted => ScreeningStage::Shortlisted,
                ScreeningDecision::NotSuitable => ScreeningStage::Rejected,
                ScreeningDecision::Suitable => ScreeningStage::Matched,
                default => ScreeningStage::Pending,
            },
            'notes' => $notes,
            'rejection_reason_id' => $decision === ScreeningDecision::NotSuitable ? $rejectionReasonId : null,
            'screened_by' => $actor->id,
            'screened_at' => now(),
            'held_by' => null,
            'held_at' => null,
        ];

        if ($isShortlist) {
            $attributes['shortlisted_by'] = $actor->id;
            $attributes['shortlisted_at'] = now();
        } else {
            $attributes['shortlisted_by'] = null;
            $attributes['shortlisted_at'] = null;
        }

        $saved = ApplicationScreening::query()->updateOrCreate(
            ['application_id' => $application->id],
            $attributes,
        );

        $application->setRelation('screening', $saved);

        return $saved;
    }

    private function assertAllowedTransition(Application $application, ApplicationStatus $from, ApplicationStatus $to): void
    {
        $allowed = match ($from) {
            ApplicationStatus::New => [
                ApplicationStatus::Reviewing,
                ApplicationStatus::Shortlisted,
                ApplicationStatus::Interview,
                ApplicationStatus::Rejected,
                ApplicationStatus::Withdrawn,
            ],
            ApplicationStatus::Reviewing => [
                ApplicationStatus::New,
                ApplicationStatus::Shortlisted,
                ApplicationStatus::Interview,
                ApplicationStatus::Rejected,
                ApplicationStatus::Withdrawn,
            ],
            ApplicationStatus::Shortlisted => [
                ApplicationStatus::Reviewing,
                ApplicationStatus::Interview,
                ApplicationStatus::Selected,
                ApplicationStatus::Rejected,
                ApplicationStatus::Withdrawn,
            ],
            ApplicationStatus::Interview => [
                ApplicationStatus::Reviewing,
                ApplicationStatus::Shortlisted,
                ApplicationStatus::Selected,
                ApplicationStatus::Rejected,
                ApplicationStatus::Withdrawn,
            ],
            ApplicationStatus::Selected => [
                ApplicationStatus::Interview,
                ApplicationStatus::Shortlisted,
                ApplicationStatus::Placed,
                ApplicationStatus::Rejected,
                ApplicationStatus::Withdrawn,
            ],
            ApplicationStatus::Placed => [],
            ApplicationStatus::Rejected, ApplicationStatus::Withdrawn => [
                ApplicationStatus::New,
                ApplicationStatus::Reviewing,
            ],
        };

        if (! in_array($to, $allowed, true)) {
            throw ValidationException::withMessages([
                'status' => 'This application cannot move from '.$from->value.' to '.$to->value.'.',
            ]);
        }

        if ($to === ApplicationStatus::Placed && ! $application->placements()->exists()) {
            throw ValidationException::withMessages([
                'status' => 'An application can only be marked placed after a placement record exists.',
            ]);
        }
    }

}
