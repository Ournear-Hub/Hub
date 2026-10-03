<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Enums\AutomationTrigger;
use App\Enums\EmployerSubmissionStatus;
use App\Enums\InvoiceStatus;
use App\Enums\InterviewLifecycleStatus;
use App\Enums\PlacementStatus;
use App\Enums\ReplacementStatus;
use App\Models\Application;
use App\Models\ApplicationInterview;
use App\Models\EmployerSubmission;
use App\Models\Invoice;
use App\Models\Placement;
use App\Models\RecruitmentAutomation;
use App\Models\Replacement;

class RecruitmentAutomationProcessor
{
    public function __construct(
        private RecruitmentAutomationDispatcher $dispatcher,
    ) {}

    public function processDue(): int
    {
        $processed = 0;

        $rules = RecruitmentAutomation::query()
            ->where('is_enabled', true)
            ->orderBy('id')
            ->get()
            ->filter(fn (RecruitmentAutomation $rule) => $rule->trigger?->isScheduled() === true);

        foreach ($rules as $rule) {
            $trigger = $rule->trigger;
            if (! $trigger instanceof AutomationTrigger) {
                continue;
            }

            $delay = (int) (($rule->conditions['delay_hours'] ?? null) ?: $trigger->defaultDelayHours());

            foreach ($this->subjects($trigger, $delay, $rule) as $context) {
                $this->dispatcher->dispatch($trigger, $context);
                $processed++;
            }
        }

        return $processed;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function subjects(AutomationTrigger $trigger, int $delay, RecruitmentAutomation $rule): array
    {
        $until = now()->addHours(max($delay, 1));
        $since = now()->subHours(max($delay, 1));

        return match ($trigger) {
            AutomationTrigger::InterviewApproaching => ApplicationInterview::query()
                ->with('application.job')
                ->whereIn('lifecycle_status', [
                    InterviewLifecycleStatus::Scheduled->value,
                    InterviewLifecycleStatus::Confirmed->value,
                ])
                ->whereNotNull('scheduled_at')
                ->whereBetween('scheduled_at', [now(), $until])
                ->orderBy('id')
                ->get()
                ->filter(fn (ApplicationInterview $interview) => $this->inScope($rule, $interview->application?->job_id, $interview->application?->job?->employer_id))
                ->map(fn (ApplicationInterview $interview) => [
                    'interview' => $interview,
                    'application' => $interview->application,
                ])
                ->values()
                ->all(),
            AutomationTrigger::JoiningDue => Placement::query()
                ->whereNotIn('status', [
                    PlacementStatus::Joined->value,
                    PlacementStatus::Withdrawn->value,
                    PlacementStatus::Declined->value,
                ])
                ->whereNotNull('expected_joining_date')
                ->whereBetween('expected_joining_date', [now()->toDateString(), $until->toDateString()])
                ->orderBy('id')
                ->get()
                ->filter(fn (Placement $placement) => $this->inScope($rule, $placement->job_id, $placement->employer_profile_id))
                ->map(fn (Placement $placement) => [
                    'placement' => $placement,
                    'application' => $placement->application,
                    'employer_profile_id' => $placement->employer_profile_id,
                    'job_id' => $placement->job_id,
                ])
                ->values()
                ->all(),
            AutomationTrigger::ReplacementAging => Replacement::query()
                ->where('status', ReplacementStatus::Requested->value)
                ->where('requested_at', '<=', $since)
                ->orderBy('id')
                ->get()
                ->filter(fn (Replacement $replacement) => $this->inScope($rule, $replacement->job_id, $replacement->employer_profile_id))
                ->map(fn (Replacement $replacement) => [
                    'replacement' => $replacement,
                    'application' => $replacement->originalApplication,
                    'employer_profile_id' => $replacement->employer_profile_id,
                    'job_id' => $replacement->job_id,
                ])
                ->values()
                ->all(),
            AutomationTrigger::ClientFollowupDue => EmployerSubmission::query()
                ->whereIn('status', [
                    EmployerSubmissionStatus::Submitted->value,
                    EmployerSubmissionStatus::UnderReview->value,
                    EmployerSubmissionStatus::OnHold->value,
                ])
                ->where('submitted_at', '<=', $since)
                ->orderBy('id')
                ->get()
                ->filter(fn (EmployerSubmission $submission) => $this->inScope($rule, $submission->job_id, $submission->employer_profile_id))
                ->map(fn (EmployerSubmission $submission) => [
                    'submission' => $submission,
                    'application' => $submission->application,
                    'employer_profile_id' => $submission->employer_profile_id,
                    'job_id' => $submission->job_id,
                ])
                ->values()
                ->all(),
            AutomationTrigger::ApplicationStale => Application::query()
                ->with('job')
                ->where('status', ApplicationStatus::New->value)
                ->where('created_at', '<=', $since)
                ->orderBy('id')
                ->get()
                ->filter(fn (Application $application) => $this->inScope($rule, $application->job_id, $application->job?->employer_id))
                ->map(fn (Application $application) => [
                    'application' => $application,
                    'job_id' => $application->job_id,
                ])
                ->values()
                ->all(),
            AutomationTrigger::InvoiceDue => Invoice::query()
                ->with('application')
                ->whereIn('status', [
                    InvoiceStatus::Issued->value,
                    InvoiceStatus::PartiallyPaid->value,
                ])
                ->whereNotNull('due_at')
                ->whereBetween('due_at', [now()->toDateString(), $until->toDateString()])
                ->orderBy('id')
                ->get()
                ->filter(fn (Invoice $invoice) => $this->inScope($rule, $invoice->job_id, $invoice->employer_profile_id))
                ->map(fn (Invoice $invoice) => [
                    'invoice' => $invoice,
                    'application' => $invoice->application,
                    'employer_profile_id' => $invoice->employer_profile_id,
                    'job_id' => $invoice->job_id,
                    'placement_id' => $invoice->placement_id,
                ])
                ->values()
                ->all(),
            default => [],
        };
    }

    private function inScope(RecruitmentAutomation $rule, ?int $jobId, ?int $employerId): bool
    {
        return match ($rule->scope->value) {
            'job' => $rule->job_id !== null && (int) $jobId === (int) $rule->job_id,
            'employer' => $rule->employer_profile_id !== null && (int) $employerId === (int) $rule->employer_profile_id,
            default => true,
        };
    }
}
