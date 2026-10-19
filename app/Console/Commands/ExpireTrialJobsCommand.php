<?php

namespace App\Console\Commands;

use App\Enums\JobPublishingStatus;
use App\Enums\SecurityActivityType;
use App\Models\Job;
use App\Services\SecurityAuditService;
use Illuminate\Console\Command;

class ExpireTrialJobsCommand extends Command
{
    protected $signature = 'jobs:expire-trials';

    protected $description = 'Move published trial jobs with a missing or elapsed expiry back to draft and remove them from public listings.';

    public function handle(SecurityAuditService $audit): int
    {
        $expired = Job::query()
            ->where('posting_tier', 'trial')
            ->where('publishing_status', JobPublishingStatus::Published)
            ->where(function ($trial): void {
                $trial->whereNull('trial_expires_at')
                    ->orWhere('trial_expires_at', '<=', now());
            })
            ->get();

        $count = 0;
        foreach ($expired as $job) {
            if ($job->publishing_status !== JobPublishingStatus::Published) {
                continue;
            }

            $job->forceFill([
                'publishing_status' => JobPublishingStatus::Draft,
                'active' => false,
            ])->save();

            $missingExpiry = $job->trial_expires_at === null;

            $audit->record(
                action: SecurityActivityType::JobUnpublished,
                description: sprintf(
                    $missingExpiry
                        ? 'Trial job %s had no expiry and was moved to draft.'
                        : 'Trial job %s expired and was moved to draft.',
                    $job->business_id ?: $job->title,
                ),
                actor: null,
                subject: $job,
                metadata: [
                    'reason' => $missingExpiry ? 'trial_expiry_missing' : 'trial_expired',
                    'trial_expires_at' => $job->trial_expires_at?->toIso8601String(),
                ],
            );

            $count++;
        }

        $this->info("Expired {$count} trial job(s).");

        return self::SUCCESS;
    }
}
