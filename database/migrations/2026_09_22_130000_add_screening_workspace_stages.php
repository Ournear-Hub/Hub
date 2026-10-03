<?php

use App\Enums\ScreeningDecision;
use App\Enums\ScreeningStage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('application_screenings')) {
            return;
        }

        Schema::table('application_screenings', function (Blueprint $table) {
            if (! Schema::hasColumn('application_screenings', 'stage')) {
                $table->string('stage')->default(ScreeningStage::Pending->value);
                $table->index('stage');
            }

            if (! Schema::hasColumn('application_screenings', 'held_by')) {
                $table->foreignId('held_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('application_screenings', 'held_at')) {
                $table->timestamp('held_at')->nullable();
            }
        });

        DB::table('application_screenings')
            ->orderBy('id')
            ->get(['id', 'decision', 'stage'])
            ->each(function (object $row): void {
                $current = (string) ($row->stage ?? '');
                if ($current !== '' && $current !== ScreeningStage::Pending->value) {
                    return;
                }

                $stage = match ((string) $row->decision) {
                    ScreeningDecision::Shortlisted->value => ScreeningStage::Shortlisted->value,
                    ScreeningDecision::NotSuitable->value => ScreeningStage::Rejected->value,
                    ScreeningDecision::Suitable->value => ScreeningStage::Matched->value,
                    default => ScreeningStage::Pending->value,
                };

                DB::table('application_screenings')->where('id', $row->id)->update(['stage' => $stage]);
            });
    }

    public function down(): void
    {
        if (! Schema::hasTable('application_screenings')) {
            return;
        }

        Schema::table('application_screenings', function (Blueprint $table) {
            if (Schema::hasColumn('application_screenings', 'held_by')) {
                $table->dropConstrainedForeignId('held_by');
            }

            if (Schema::hasColumn('application_screenings', 'held_at')) {
                $table->dropColumn('held_at');
            }

            if (Schema::hasColumn('application_screenings', 'stage')) {
                $table->dropIndex(['stage']);
                $table->dropColumn('stage');
            }
        });
    }
};
