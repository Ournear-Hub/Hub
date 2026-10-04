<?php

namespace App\Console\Commands;

use App\Support\RecoveredForeignKeyAudit;
use Illuminate\Console\Command;

class AuditRecoveredFksCommand extends Command
{
    protected $signature = 'ournear:audit-recovered-fks
                            {--sample=50 : Maximum orphan row IDs to list per check}';

    protected $description = 'Read-only audit of recovered-data foreign keys and orphan references.';

    public function handle(): int
    {
        $sample = max(1, (int) $this->option('sample'));
        $failures = 0;

        foreach (RecoveredForeignKeyAudit::checks() as $check) {
            $result = RecoveredForeignKeyAudit::auditCheck($check, $sample);

            if ($result['skipped'] ?? false) {
                $this->line(sprintf('[SKIP] %s.%s (table/column unavailable)', $result['table'], $result['column']));

                continue;
            }

            $orphanCount = (int) $result['orphan_count'];
            if ($orphanCount === 0) {
                $this->info(sprintf('[OK] %s.%s → %s.%s (0 orphans)', $result['table'], $result['column'], $result['referenced_table'], 'id'));

                continue;
            }

            $failures++;
            $this->error(sprintf(
                '[ORPHAN] %s.%s → %s.id | count=%d | nullable=%s',
                $result['table'],
                $result['column'],
                $result['referenced_table'],
                $orphanCount,
                $result['nullable'] ? 'yes' : 'no',
            ));
            $this->line('  deterministic_mapping: '.$result['deterministic_mapping']);
            $this->line('  affected_row_ids (sample): '.implode(', ', array_map('strval', $result['orphan_ids'])));
        }

        if ($failures > 0) {
            $this->newLine();
            $this->warn(sprintf('%d FK check(s) reported orphans. No database changes were made.', $failures));

            return self::FAILURE;
        }

        $this->info('FK audit complete — no orphans detected.');

        return self::SUCCESS;
    }
}
