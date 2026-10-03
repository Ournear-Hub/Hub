<?php

namespace App\Console\Commands;

use App\Services\RecruitmentReminderService;
use Illuminate\Console\Command;

class SendRecruitmentReminders extends Command
{
    protected $signature = 'recruitment:send-reminders';

    protected $description = 'Send upcoming interview and due task reminders';

    public function handle(RecruitmentReminderService $reminders): int
    {
        $result = $reminders->sendDue();

        $this->info('Interview reminders: '.$result['interviews']);
        $this->info('Task reminders: '.$result['tasks']);
        $this->info('Automation checks: '.$result['automations']);

        return self::SUCCESS;
    }
}
