<?php

namespace App\Services;

use App\Enums\AutomationActionType;
use App\Enums\AutomationExecutionStatus;
use App\Enums\AutomationScope;
use App\Enums\AutomationTrigger;
use App\Enums\RecruitmentActivityType;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\ApplicationInterview;
use App\Models\EmployerSubmission;
use App\Models\Invoice;
use App\Models\Placement;
use App\Models\RecruitmentAutomation;
use App\Models\RecruitmentAutomationExecution;
use App\Models\Replacement;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

class RecruitmentAutomationDispatcher
{
    public function __construct(
        private RecruitmentAutomationScreeningAdvisor $advisor,
        private TaskService $tasks,
        private AdminAlertService $alerts,
        private RecruitmentActivityService $activities,
    ) {}

    /**
     * @param  array<string, mixed>  $context
     */
    public function dispatch(AutomationTrigger $trigger, array $context): void
    {
        $rules = RecruitmentAutomation::query()
            ->where('is_enabled', true)
            ->where('trigger', $trigger->value)
            ->orderBy('id')
            ->get();

        foreach ($rules as $rule) {
            $this->executeRule($rule, $trigger, $context);
        }
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function executeRule(RecruitmentAutomation $rule, AutomationTrigger $trigger, array $context): void
    {
        if (! $this->matchesScope($rule, $context)) {
            return;
        }

        $key = $this->idempotencyKey($rule, $trigger, $context);

        try {
            $execution = RecruitmentAutomationExecution::query()->create([
                'recruitment_automation_id' => $rule->id,
                'trigger' => $trigger->value,
                'status' => AutomationExecutionStatus::Skipped->value,
                'idempotency_key' => $key,
                'application_id' => $this->application($context)?->id,
                'job_id' => $this->jobId($context),
                'employer_profile_id' => $this->employerId($context) ?? $rule->employer_profile_id,
                'interview_id' => ($context['interview'] ?? null) instanceof ApplicationInterview
                    ? $context['interview']->id
                    : null,
                'employer_submission_id' => ($context['submission'] ?? null) instanceof EmployerSubmission
                    ? $context['submission']->id
                    : null,
                'placement_id' => ($context['placement'] ?? null) instanceof Placement
                    ? $context['placement']->id
                    : null,
                'replacement_id' => ($context['replacement'] ?? null) instanceof Replacement
                    ? $context['replacement']->id
                    : null,
            ]);
        } catch (QueryException) {
            return;
        }

        try {
            $application = $this->application($context);
            $evaluation = $this->evaluate($rule, $context);

            if (! $evaluation['ok']) {
                $execution->update([
                    'status' => AutomationExecutionStatus::Skipped->value,
                    'match_percent' => $evaluation['match']['percent'] ?? null,
                    'match_state' => $evaluation['match']['state'] ?? null,
                    'result' => $this->resultPayload($evaluation, $application, $evaluation['reason']),
                    'executed_at' => now(),
                ]);

                return;
            }

            $actions = $this->runActions($rule, $context, $evaluation);

            $execution->update([
                'status' => AutomationExecutionStatus::Executed->value,
                'task_id' => $actions['task']?->id,
                'match_percent' => $evaluation['match']['percent'] ?? null,
                'match_state' => $evaluation['match']['state'] ?? null,
                'result' => $this->resultPayload($evaluation, $application, null),
                'executed_at' => now(),
            ]);

            $this->activities->record(
                actor: $this->actor($rule, $context),
                type: RecruitmentActivityType::AutomationExecuted,
                application: $application,
                task: $actions['task'],
                body: 'Automation "'.$rule->name.'" recommended recruiter review. Human decision remains final.',
            );
        } catch (\Throwable $exception) {
            Log::warning('Recruitment automation execution failed.', [
                'automation_id' => $rule->id,
                'message' => $exception->getMessage(),
            ]);
            $execution->update([
                'status' => AutomationExecutionStatus::Failed->value,
                'error_message' => $exception->getMessage(),
                'executed_at' => now(),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array{
     *     ok: bool,
     *     reason: string|null,
     *     match: array<string, mixed>|null,
     *     comparison: array<string, mixed>|null,
     *     recommendation: string|null
     * }
     */
    private function evaluate(RecruitmentAutomation $rule, array $context): array
    {
        $conditions = $rule->conditions ?? [];
        $application = $this->application($context);
        $match = null;
        $comparison = null;
        $recommendation = null;
        $evaluationExtra = [];

        $status = $conditions['application_status'] ?? null;
        if (is_string($status) && $status !== '' && $application instanceof Application) {
            if ($application->status->value !== $status) {
                return $this->fail('application_status_mismatch', $match, $comparison);
            }
        }

        $origin = $conditions['application_origin'] ?? null;
        if (is_string($origin) && $origin !== '' && $application instanceof Application) {
            $actual = $application->user_id === null ? 'guest' : 'account';
            if ($actual !== $origin) {
                return $this->fail('application_origin_mismatch', $match, $comparison);
            }
        }

        $needsMatch = array_key_exists('min_match_percent', $conditions)
            || $rule->hasAction(AutomationActionType::RecommendScreening);

        if ($needsMatch && $application instanceof Application) {
            $advice = $this->advisor->recommend($application);
            $match = $advice['match'];
            $comparison = $advice['job_comparison'];
            $recommendation = $advice['recommendation'];
            $evaluationExtra = [
                'evidence' => $advice['evidence'],
                'application_origin' => $advice['application_origin'],
            ];

            if (array_key_exists('min_match_percent', $conditions)) {
                $min = (int) $conditions['min_match_percent'];
                $percent = $match['percent'] ?? null;
                if ($percent === null) {
                    if (! ($conditions['allow_insufficient_data'] ?? false)) {
                        return $this->fail('not_enough_match_data', $match, $comparison, $recommendation);
                    }
                } elseif ((int) $percent < $min) {
                    return $this->fail('match_below_threshold', $match, $comparison, $recommendation);
                }
            }
        }

        $origin = $application instanceof Application
            ? ($application->user_id === null ? 'guest' : 'account')
            : null;

        return [
            'ok' => true,
            'reason' => null,
            'match' => $match,
            'comparison' => $comparison,
            'recommendation' => $recommendation,
            'evidence' => $evaluationExtra['evidence'] ?? $this->advisor->evidenceFromMatch($match),
            'application_origin' => $evaluationExtra['application_origin'] ?? $origin,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $match
     * @param  array<string, mixed>|null  $comparison
     * @return array{
     *     ok: bool,
     *     reason: string,
     *     match: array<string, mixed>|null,
     *     comparison: array<string, mixed>|null,
     *     recommendation: string|null
     * }
     */
    private function fail(string $reason, ?array $match, ?array $comparison, ?string $recommendation = null): array
    {
        return [
            'ok' => false,
            'reason' => $reason,
            'match' => $match,
            'comparison' => $comparison,
            'recommendation' => $recommendation,
            'evidence' => $this->advisor->evidenceFromMatch($match),
            'application_origin' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $evaluation
     * @return array<string, mixed>
     */
    private function resultPayload(array $evaluation, ?Application $application, ?string $reason): array
    {
        return [
            'reason' => $reason,
            'recommendation' => $evaluation['recommendation'] ?? null,
            'match' => $evaluation['match'],
            'job_comparison' => $evaluation['comparison'],
            'evidence' => $evaluation['evidence'] ?? $this->advisor->evidenceFromMatch($evaluation['match'] ?? null),
            'application_origin' => $evaluation['application_origin']
                ?? ($application instanceof Application
                    ? ($application->user_id === null ? 'guest' : 'account')
                    : null),
            'auto_shortlist' => false,
            'screening_decision' => $application?->screeningDecision()->value,
            'application_status' => $application?->status?->value,
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $evaluation
     * @return array{task: Task|null}
     */
    private function runActions(
        RecruitmentAutomation $rule,
        array $context,
        array $evaluation,
    ): array {
        $task = null;
        $application = $this->application($context);
        $actor = $this->actor($rule, $context);

        if ($rule->hasAction(AutomationActionType::RecommendScreening) || $rule->hasAction(AutomationActionType::CreateTask)) {
            $task = $this->createReviewTask($rule, $context, $evaluation, $actor);
        }

        if ($rule->hasAction(AutomationActionType::NotifyAdmin)) {
            $this->alerts->automationRecommended(
                $rule,
                $application,
                $evaluation['match'] ?? null,
                $task,
            );
        }

        return ['task' => $task];
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $evaluation
     */
    private function createReviewTask(
        RecruitmentAutomation $rule,
        array $context,
        array $evaluation,
        User $actor,
    ): Task {
        $application = $this->application($context);
        $match = $evaluation['match'] ?? [];
        $label = is_array($match) ? ($match['label'] ?? 'Not scored') : 'Not scored';
        $disclaimer = is_array($match) ? ($match['disclaimer'] ?? ScreeningMatchService::DISCLAIMER) : ScreeningMatchService::DISCLAIMER;
        $recommendation = $evaluation['recommendation'] ?? 'Recruiter review required.';

        $title = 'Review screening match ('.$label.') — human decision remains final';
        if (! $rule->hasAction(AutomationActionType::RecommendScreening)) {
            $title = 'Follow up: '.$rule->name;
        }

        $body = implode("\n\n", array_filter([
            $recommendation,
            $disclaimer,
            'Automation does not shortlist, reject, or make an offer. Recruiter review is required.',
            $application instanceof Application
                ? 'Current screening decision: '.$application->screeningDecision()->value.'. Status: '.$application->status->value.'.'
                : null,
        ]));

        return $this->tasks->create([
            'title' => $title,
            'body' => $body,
            'application_id' => $application?->id,
            'job_id' => $this->jobId($context),
            'candidate_profile_id' => $application?->user?->candidateProfile?->id,
            'employer_profile_id' => $this->employerId($context),
        ], $actor);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function matchesScope(RecruitmentAutomation $rule, array $context): bool
    {
        return match ($rule->scope) {
            AutomationScope::Global => true,
            AutomationScope::Job => $rule->job_id !== null && (int) $this->jobId($context) === (int) $rule->job_id,
            AutomationScope::Employer => $rule->employer_profile_id !== null
                && (int) $this->employerId($context) === (int) $rule->employer_profile_id,
            default => false,
        };
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function idempotencyKey(RecruitmentAutomation $rule, AutomationTrigger $trigger, array $context): string
    {
        $subject = 'none';
        if (($context['invoice'] ?? null) instanceof Invoice) {
            $subject = 'invoice:'.$context['invoice']->id;
        } elseif (($context['interview'] ?? null) instanceof ApplicationInterview) {
            $subject = 'interview:'.$context['interview']->id;
        } elseif (($context['submission'] ?? null) instanceof EmployerSubmission) {
            $subject = 'submission:'.$context['submission']->id;
        } elseif (($context['placement'] ?? null) instanceof Placement) {
            $subject = 'placement:'.$context['placement']->id;
        } elseif (($context['replacement'] ?? null) instanceof Replacement) {
            $subject = 'replacement:'.$context['replacement']->id;
        } elseif ($this->application($context) instanceof Application) {
            $to = $context['to_status'] ?? $this->application($context)->status->value;
            $subject = 'application:'.$this->application($context)->id.':'.$to;
        }

        return 'auto:'.$rule->id.':'.$trigger->value.':'.$subject;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function application(array $context): ?Application
    {
        $application = $context['application'] ?? null;
        if ($application instanceof Application) {
            return $application->loadMissing(['job.employer', 'user.candidateProfile', 'screening']);
        }

        $interview = $context['interview'] ?? null;
        if ($interview instanceof ApplicationInterview) {
            return $interview->application;
        }

        $submission = $context['submission'] ?? null;
        if ($submission instanceof EmployerSubmission) {
            return $submission->application;
        }

        $placement = $context['placement'] ?? null;
        if ($placement instanceof Placement) {
            return $placement->application;
        }

        $replacement = $context['replacement'] ?? null;
        if ($replacement instanceof Replacement) {
            return $replacement->originalApplication;
        }

        $invoice = $context['invoice'] ?? null;
        if ($invoice instanceof Invoice) {
            return $invoice->application;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function jobId(array $context): ?int
    {
        if (isset($context['job_id'])) {
            return (int) $context['job_id'];
        }

        $application = $this->application($context);

        return $application?->job_id;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function employerId(array $context): ?int
    {
        if (isset($context['employer_profile_id'])) {
            return (int) $context['employer_profile_id'];
        }

        $application = $this->application($context);
        $application?->loadMissing('job');

        return $application?->job?->employer_id;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function actor(RecruitmentAutomation $rule, array $context): User
    {
        $actor = $context['actor'] ?? null;
        if ($actor instanceof User && $actor->isAdmin()) {
            return $actor;
        }

        $creator = $rule->creator ?? User::query()->find($rule->created_by);
        if ($creator instanceof User) {
            return $creator;
        }

        return User::query()->where('role', UserRole::Admin->value)->orderBy('id')->firstOrFail();
    }
}
