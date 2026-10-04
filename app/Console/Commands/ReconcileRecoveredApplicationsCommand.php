<?php

namespace App\Console\Commands;

use App\Support\RecoveredApplicationReconciler;
use Illuminate\Console\Command;

class ReconcileRecoveredApplicationsCommand extends Command
{
    protected $signature = 'ournear:reconcile-recovered-applications
                            {--apply : Update application user_id/job_id for deterministically recoverable rows}
                            {--verbose-details : Print per-application analysis rows}';

    protected $description = 'Analyze or repair application FKs after backup recovery (dry-run by default).';

    public function handle(RecoveredApplicationReconciler $reconciler): int
    {
        $apply = (bool) $this->option('apply');
        $verbose = (bool) $this->option('verbose-details');

        if ($apply && app()->environment('production')) {
            if (! $this->confirm('Apply application FK reconciliation on PRODUCTION?', false)) {
                $this->warn('Aborted.');

                return self::FAILURE;
            }
        }

        $analysis = $reconciler->analyze(limitDetails: $verbose || $apply, detailLimit: $verbose ? 10_000 : 100);

        $this->table(
            ['Metric', 'Count'],
            [
                ['Already valid (user + job)', $analysis['skipped_valid']],
                ['Recoverable', $analysis['recoverable']],
                ['Unrecoverable', $analysis['unrecoverable']],
                ['Would duplicate existing application', $analysis['skipped_duplicate']],
            ],
        );

        if ($verbose) {
            foreach ($analysis['details'] as $row) {
                $this->line(json_encode($row, JSON_UNESCAPED_UNICODE));
            }
        }

        if ($apply) {
            $result = $reconciler->apply();
            $this->info(sprintf('Updated %d application row(s). Skipped %d.', $result['updated'], $result['skipped']));

            return $analysis['unrecoverable'] > 0 ? self::FAILURE : self::SUCCESS;
        }

        $this->newLine();
        $this->info('Dry run only. Use --apply to persist recoverable application FK updates.');
        $this->line('Job mappings: storage/app/recovery/job_id_map.json');
        $this->line('User mappings: storage/app/recovery/user_id_map.json or application email match');

        return $analysis['unrecoverable'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
