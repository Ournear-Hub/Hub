<?php

namespace App\Console\Commands;

use App\Support\RecoveredEnumValues;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RepairRecoveredDataCommand extends Command
{
    protected $signature = 'ournear:repair-recovered-data
                            {--apply : Persist normalized enum values to the database}
                            {--table= : Limit to one table name}';

    protected $description = 'Audit and normalize legacy enum column values after backup recovery (dry-run by default).';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $tableFilter = is_string($this->option('table')) && $this->option('table') !== ''
            ? $this->option('table')
            : null;

        if ($apply && app()->environment('production')) {
            if (! $this->confirm('Apply enum repairs on PRODUCTION database?', false)) {
                $this->warn('Aborted.');

                return self::FAILURE;
            }
        }

        $issues = 0;
        $updates = 0;

        foreach (RecoveredEnumValues::repairTargets() as $target) {
            $table = $target['table'];
            $column = $target['column'];
            $enumClass = $target['enum'];

            if ($tableFilter !== null && $tableFilter !== $table) {
                continue;
            }

            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }

            $distinct = DB::table($table)
                ->select($column)
                ->whereNotNull($column)
                ->where($column, '!=', '')
                ->distinct()
                ->pluck($column);

            foreach ($distinct as $raw) {
                $canonical = RecoveredEnumValues::normalizeValue($enumClass, $raw);
                if ($canonical === null) {
                    $issues++;
                    $count = (int) DB::table($table)->where($column, $raw)->count();
                    $this->error(sprintf(
                        '[%s.%s] UNKNOWN / MANUAL REVIEW REQUIRED | value=%s | rows=%d | enum=%s',
                        $table,
                        $column,
                        (string) $raw,
                        $count,
                        $enumClass,
                    ));

                    continue;
                }

                if ((string) $raw === $canonical) {
                    continue;
                }

                $count = (int) DB::table($table)->where($column, $raw)->count();
                $updates += $count;
                $this->line(sprintf(
                    '[%s.%s] %s -> %s (%d row%s)',
                    $table,
                    $column,
                    (string) $raw,
                    $canonical,
                    $count,
                    $count === 1 ? '' : 's',
                ));

                if ($apply) {
                    DB::table($table)->where($column, $raw)->update([$column => $canonical]);
                }
            }
        }

        if ($issues > 0) {
            $this->warn(sprintf('%d unknown enum value(s) need manual review.', $issues));
        }

        if ($updates === 0 && $issues === 0) {
            $this->info('No enum repairs required.');

            return self::SUCCESS;
        }

        if (! $apply) {
            $this->newLine();
            $this->info(sprintf('Dry run: %d row(s) would be updated. Re-run with --apply to persist.', $updates));

            return $issues > 0 ? self::FAILURE : self::SUCCESS;
        }

        $this->info(sprintf('Applied enum repairs to %d row(s).', $updates));

        return $issues > 0 ? self::FAILURE : self::SUCCESS;
    }
}
