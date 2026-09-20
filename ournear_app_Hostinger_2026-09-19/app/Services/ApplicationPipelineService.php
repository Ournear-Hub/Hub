<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Enums\InterviewMode;
use App\Enums\InterviewOutcome;
use App\Enums\RecruitmentActivityType;
use App\Enums\ScreeningDecision;
use App\Models\Application;
use App\Models\ApplicationInterview;
use App\Models\ApplicationScreening;
use App\Models\RejectionReason;
use App\Models\User;
use App\Support\AppendsEnquiryNotes;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApplicationPipelineService
{
    use AppendsEnquiryNotes;

    public function __construct(
        private RecruitmentActivityService $activities,
        private RecruitmentNotificationService $notifications,
    ) {}

    public function changeStatus(Application $application, ApplicationStatus $status, User $actor): Application
    {
        $from = $application->status;

        if ($from === $status) {
            return $application;
        }

        DB::transaction(function () use ($application, $status, $actor, $from) {
            $application->update(['status' => $status]);

            $this->activities->record(
                actor: $actor,
                type: RecruitmentActivityType::StatusChanged,
                application: $application,
                fromStatus: $from->value,
                toStatus: $status->value,
            );
        });

        $fresh = $application->fresh() ?? $application;
        $this->notifications->notifyStatusChanged($fresh, $from, $status);

        return $fresh;
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
        $application->loadMissing('interview');
        $existing = $application->interview;
        $scheduleChanged = $this->interviewScheduleChanged($existing, $data);

        $interview = DB::transaction(function () use ($application, $data, $actor, $existing) {
            $wasNew = $existing === null;

            $saved = ApplicationInterview::query()->updateOrCreate(
                ['application_id' => $application->id],
                [
                    'scheduled_at' => $data['scheduled_at'],
                    'mode' => $data['mode'],
                    'location_or_link' => $data['location_or_link'] ?? null,
                    'interviewer_name' => $data['interviewer_name'],
                    'outcome' => $data['outcome'],
                    'notes' => $data['notes'] ?? null,
                    'created_by' => $existing?->created_by ?? $actor->id,
                ]
            );

            $this->activities->record(
                actor: $actor,
                type: $wasNew
                    ? RecruitmentActivityType::InterviewScheduled
                    : RecruitmentActivityType::InterviewUpdated,
                application: $application,
                body: $wasNew ? 'Interview scheduled.' : 'Interview updated.',
            );

            $application->refresh();

            if (! $application->status->isTerminal() && $application->status !== ApplicationStatus::Interview) {
                $this->changeStatus($application, ApplicationStatus::Interview, $actor);
            }

            return $saved->fresh() ?? $saved;
        });

        if ($scheduleChanged) {
            $this->notifications->notifyInterviewScheduled($application->fresh() ?? $application, $interview);
        }

        return $interview;
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
            return $this->changeStatus($fresh, ApplicationStatus::Shortlisted, $actor);
        }

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
            'notes' => $notes,
            'rejection_reason_id' => $decision === ScreeningDecision::NotSuitable ? $rejectionReasonId : null,
            'screened_by' => $actor->id,
            'screened_at' => now(),
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
    private function interviewScheduleChanged(?ApplicationInterview $existing, array $data): bool
    {
        if ($existing === null) {
            return true;
        }

        $mode = $data['mode'] instanceof InterviewMode
            ? $data['mode']
            : InterviewMode::from((string) $data['mode']);

        $newAt = Carbon::parse($data['scheduled_at']);
        $timeChanged = $existing->scheduled_at === null || $existing->scheduled_at->format('Y-m-d H:i') !== $newAt->format('Y-m-d H:i');
        $modeChanged = $existing->mode !== $mode;
        $locationChanged = (string) ($existing->location_or_link ?? '') !== (string) ($data['location_or_link'] ?? '');

        return $timeChanged || $modeChanged || $locationChanged;
    }
}
