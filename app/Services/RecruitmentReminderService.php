<?php

namespace App\Services;

use App\Enums\InterviewLifecycleStatus;
use App\Enums\TaskStatus;
use App\Models\ApplicationInterview;
use App\Models\Task;

class RecruitmentReminderService
{
    public function __construct(
        private AdminAlertService $alerts,
        private RecruitmentNotificationService $notifications,
    ) {}

    /**
     * @return array{interviews: int, tasks: int, automations: int}
     */
    public function sendDue(): array
    {
        $interviews = 0;
        $tasks = 0;

        ApplicationInterview::query()
            ->whereNull('reminder_sent_at')
            ->whereIn('lifecycle_status', [
                InterviewLifecycleStatus::Scheduled->value,
                InterviewLifecycleStatus::Confirmed->value,
            ])
            ->whereNotNull('scheduled_at')
            ->whereBetween('scheduled_at', [now(), now()->addDay()])
            ->orderBy('id')
            ->each(function (ApplicationInterview $interview) use (&$interviews) {
                $this->alerts->interviewReminder($interview);
                $this->notifications->notifyInterviewReminder($interview->application, $interview);
                $interview->update(['reminder_sent_at' => now()]);
                $interviews++;
            });

        Task::query()
            ->whereNull('reminder_sent_at')
            ->where('status', TaskStatus::Open)
            ->whereNotNull('due_at')
            ->where('due_at', '<=', now()->endOfDay())
            ->orderBy('id')
            ->each(function (Task $task) use (&$tasks) {
                $this->alerts->taskDue($task);
                $task->update(['reminder_sent_at' => now()]);
                $tasks++;
            });

        return [
            'interviews' => $interviews,
            'tasks' => $tasks,
            'automations' => app(RecruitmentAutomationProcessor::class)->processDue(),
        ];
    }
}
