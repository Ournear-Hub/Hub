<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Enums\InterviewLifecycleStatus;
use App\Enums\InterviewMode;
use App\Enums\InterviewOutcome;
use App\Enums\InterviewType;
use App\Enums\RecruitmentActivityType;
use App\Models\Application;
use App\Models\ApplicationInterview;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InterviewService
{
    public function __construct(
        private RecruitmentActivityService $activities,
        private RecruitmentNotificationService $notifications,
        private AdminAlertService $alerts,
    ) {}

    /**
     * @param  array{
     *     scheduled_at: string,
     *     mode: InterviewMode|string,
     *     location_or_link?: string|null,
     *     interviewer_name?: string|null,
     *     interviewer_user_id?: int|null,
     *     outcome?: InterviewOutcome|string,
     *     notes?: string|null,
     *     type?: InterviewType|string|null
     * }  $data
     */
    public function schedule(Application $application, array $data, User $actor): ApplicationInterview
    {
        $interview = DB::transaction(function () use ($application, $data, $actor) {
            $sortOrder = $this->nextSortOrder($application);

            $interviewer = $this->resolveInterviewer($data);
            $interview = ApplicationInterview::query()->create([
                'application_id' => $application->id,
                'sort_order' => $sortOrder,
                'type' => $this->enumOrNull($data['type'] ?? null, InterviewType::class),
                'lifecycle_status' => InterviewLifecycleStatus::Scheduled,
                'scheduled_at' => $data['scheduled_at'],
                'mode' => $this->enumValue($data['mode'], InterviewMode::class),
                'location_or_link' => $data['location_or_link'] ?? null,
                'interviewer_name' => $interviewer['name'],
                'interviewer_user_id' => $interviewer['user_id'],
                'outcome' => $this->enumValue($data['outcome'] ?? InterviewOutcome::Pending, InterviewOutcome::class),
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor->id,
            ]);

            $this->activities->record(
                actor: $actor,
                type: RecruitmentActivityType::InterviewScheduled,
                application: $application,
                interview: $interview,
                body: 'Interview #'.$sortOrder.' scheduled.',
            );

            $application->refresh();

            if (! $application->status->isTerminal() && $application->status !== ApplicationStatus::Interview) {
                app(ApplicationPipelineService::class)->changeStatus($application, ApplicationStatus::Interview, $actor);
            }

            return $interview->fresh() ?? $interview;
        });

        $this->notifications->notifyInterviewScheduled($application->fresh() ?? $application, $interview);
        $this->alerts->interviewScheduled($interview);
        RecruitmentAutomationHooks::fire(\App\Enums\AutomationTrigger::InterviewScheduled, [
            'interview' => $interview,
            'application' => $application->fresh() ?? $application,
            'actor' => $actor,
        ]);

        return $interview;
    }

    /**
     * @param  array{
     *     outcome?: InterviewOutcome|string,
     *     notes?: string|null,
     *     location_or_link?: string|null,
     *     interviewer_name?: string|null,
     *     interviewer_user_id?: int|null,
     *     type?: InterviewType|string|null
     * }  $data
     */
    public function updateDetails(ApplicationInterview $interview, array $data, User $actor): ApplicationInterview
    {
        $interview = DB::transaction(function () use ($interview, $data, $actor) {
            $interviewer = $this->resolveInterviewer($data, $interview);
            $outcome = array_key_exists('outcome', $data)
                ? $this->enumValue($data['outcome'], InterviewOutcome::class)
                : $interview->outcome;

            $attributes = [
                'outcome' => $outcome,
                'notes' => array_key_exists('notes', $data) ? $data['notes'] : $interview->notes,
                'location_or_link' => array_key_exists('location_or_link', $data)
                    ? $data['location_or_link']
                    : $interview->location_or_link,
                'interviewer_name' => $interviewer['name'],
                'interviewer_user_id' => $interviewer['user_id'],
            ];

            if (array_key_exists('type', $data)) {
                $attributes['type'] = $this->enumOrNull($data['type'], InterviewType::class);
            }

            $lifecycle = $interview->lifecycle_status ?? InterviewLifecycleStatus::Scheduled;
            if ($outcome !== InterviewOutcome::Pending && $lifecycle->isOpen()) {
                $attributes['lifecycle_status'] = InterviewLifecycleStatus::Completed;
            }

            $interview->update($attributes);

            $this->activities->record(
                actor: $actor,
                type: RecruitmentActivityType::InterviewUpdated,
                application: $interview->application,
                interview: $interview,
                body: 'Interview #'.$interview->sort_order.' updated.',
            );

            return $interview->fresh() ?? $interview;
        });

        if (($interview->lifecycle_status ?? null)?->value === InterviewLifecycleStatus::Completed->value
            || ($interview->outcome !== InterviewOutcome::Pending)) {
            RecruitmentAutomationHooks::fire(\App\Enums\AutomationTrigger::InterviewCompleted, [
                'interview' => $interview,
                'application' => $interview->application,
                'actor' => $actor,
            ]);
        }

        return $interview;
    }

    public function confirm(ApplicationInterview $interview, User $actor): ApplicationInterview
    {
        $lifecycle = $interview->lifecycle_status ?? InterviewLifecycleStatus::Scheduled;
        if (! $lifecycle->canConfirm()) {
            throw ValidationException::withMessages([
                'interview' => 'This interview cannot be confirmed in its current status.',
            ]);
        }

        return DB::transaction(function () use ($interview, $actor) {
            $interview->update([
                'lifecycle_status' => InterviewLifecycleStatus::Confirmed,
                'confirmed_at' => now(),
            ]);

            $this->activities->record(
                actor: $actor,
                type: RecruitmentActivityType::InterviewConfirmed,
                application: $interview->application,
                interview: $interview,
                body: 'Interview #'.$interview->sort_order.' confirmed.',
            );

            return $interview->fresh() ?? $interview;
        });
    }

    /**
     * @param  array{
     *     scheduled_at: string,
     *     mode: InterviewMode|string,
     *     location_or_link?: string|null,
     *     interviewer_name?: string|null,
     *     interviewer_user_id?: int|null,
     *     notes?: string|null,
     *     type?: InterviewType|string|null
     * }  $data
     */
    public function reschedule(ApplicationInterview $interview, array $data, User $actor): ApplicationInterview
    {
        $lifecycle = $interview->lifecycle_status ?? InterviewLifecycleStatus::Scheduled;
        if (! $lifecycle->canReschedule()) {
            throw ValidationException::withMessages([
                'interview' => 'This interview cannot be rescheduled in its current status.',
            ]);
        }

        $replacement = DB::transaction(function () use ($interview, $data, $actor) {
            $application = $interview->application;
            $sortOrder = $this->nextSortOrder($application);
            $interviewer = $this->resolveInterviewer($data, $interview);

            $replacement = ApplicationInterview::query()->create([
                'application_id' => $application->id,
                'sort_order' => $sortOrder,
                'type' => $this->enumOrNull($data['type'] ?? $interview->type, InterviewType::class),
                'lifecycle_status' => InterviewLifecycleStatus::Scheduled,
                'scheduled_at' => $data['scheduled_at'],
                'mode' => $this->enumValue($data['mode'], InterviewMode::class),
                'location_or_link' => $data['location_or_link'] ?? $interview->location_or_link,
                'interviewer_name' => $interviewer['name'],
                'interviewer_user_id' => $interviewer['user_id'],
                'outcome' => InterviewOutcome::Pending,
                'notes' => $data['notes'] ?? $interview->notes,
                'rescheduled_from_id' => $interview->id,
                'created_by' => $actor->id,
            ]);

            $interview->update([
                'lifecycle_status' => InterviewLifecycleStatus::Rescheduled,
            ]);

            $this->activities->record(
                actor: $actor,
                type: RecruitmentActivityType::InterviewRescheduled,
                application: $application,
                interview: $replacement,
                body: 'Interview #'.$interview->sort_order.' rescheduled as interview #'.$sortOrder.'.',
            );

            $application->refresh();

            if (! $application->status->isTerminal() && $application->status !== ApplicationStatus::Interview) {
                app(ApplicationPipelineService::class)->changeStatus($application, ApplicationStatus::Interview, $actor);
            }

            return $replacement->fresh() ?? $replacement;
        });

        $this->notifications->notifyInterviewRescheduled($interview->application()->first() ?? $interview->application, $replacement);
        $this->alerts->interviewScheduled($replacement);
        RecruitmentAutomationHooks::fire(\App\Enums\AutomationTrigger::InterviewScheduled, [
            'interview' => $replacement,
            'application' => $replacement->application,
            'actor' => $actor,
        ]);

        return $replacement;
    }

    public function cancel(ApplicationInterview $interview, User $actor, ?string $reason = null): ApplicationInterview
    {
        $lifecycle = $interview->lifecycle_status ?? InterviewLifecycleStatus::Scheduled;
        if (! $lifecycle->canCancel()) {
            throw ValidationException::withMessages([
                'interview' => 'This interview cannot be cancelled in its current status.',
            ]);
        }

        $interview = DB::transaction(function () use ($interview, $actor, $reason) {
            $interview->update([
                'lifecycle_status' => InterviewLifecycleStatus::Cancelled,
                'cancelled_at' => now(),
                'cancelled_reason' => $reason,
            ]);

            $this->activities->record(
                actor: $actor,
                type: RecruitmentActivityType::InterviewCancelled,
                application: $interview->application,
                interview: $interview,
                body: trim('Interview #'.$interview->sort_order.' cancelled.'.($reason ? ' '.$reason : '')),
            );

            return $interview->fresh() ?? $interview;
        });

        $this->notifications->notifyInterviewCancelled($interview->application, $interview);

        return $interview;
    }

    private function nextSortOrder(Application $application): int
    {
        $max = (int) ApplicationInterview::query()
            ->where('application_id', $application->id)
            ->max('sort_order');
        $count = ApplicationInterview::query()
            ->where('application_id', $application->id)
            ->count();

        return max($max + 1, $count + 1, 1);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{name: string, user_id: int|null}
     */
    private function resolveInterviewer(array $data, ?ApplicationInterview $existing = null): array
    {
        $userId = $data['interviewer_user_id'] ?? $existing?->interviewer_user_id;
        $userId = is_numeric($userId) ? (int) $userId : null;
        $user = $userId ? User::query()->find($userId) : null;

        $name = trim((string) ($data['interviewer_name'] ?? $existing?->interviewer_name ?? ''));
        if ($name === '' && $user) {
            $name = $user->name;
        }

        if ($name === '') {
            throw ValidationException::withMessages([
                'interviewer_name' => 'An interviewer name is required.',
            ]);
        }

        return [
            'name' => $name,
            'user_id' => $user?->id,
        ];
    }

    /**
     * @template T of \BackedEnum
     * @param  class-string<T>  $enum
     * @return T
     */
    private function enumValue(mixed $value, string $enum): mixed
    {
        if ($value instanceof $enum) {
            return $value;
        }

        return $enum::from((string) $value);
    }

    /**
     * @template T of \BackedEnum
     * @param  class-string<T>  $enum
     * @return T|null
     */
    private function enumOrNull(mixed $value, string $enum): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof $enum) {
            return $value;
        }

        return $enum::from((string) $value);
    }
}
