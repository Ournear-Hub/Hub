<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('recruitment_activities')) {
            return;
        }

        Schema::table('recruitment_activities', function (Blueprint $table) {
            if (! Schema::hasColumn('recruitment_activities', 'recruiter_talent_pool_assignment_id')) {
                $table->foreignId('recruiter_talent_pool_assignment_id')
                    ->nullable()
                    ->constrained('recruiter_talent_pool_assignments', indexName: 'rec_act_tp_assign_fk')
                    ->nullOnDelete();
            }
            if (! Schema::hasColumn('recruitment_activities', 'visibility')) {
                $table->string('visibility', 32)->nullable()->index();
            }
            if (! Schema::hasColumn('recruitment_activities', 'operational_meta')) {
                $table->json('operational_meta')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('recruitment_activities')) {
            return;
        }

        Schema::table('recruitment_activities', function (Blueprint $table) {
            if (Schema::hasColumn('recruitment_activities', 'recruiter_talent_pool_assignment_id')) {
                $table->dropForeign('rec_act_tp_assign_fk');
                $table->dropColumn('recruiter_talent_pool_assignment_id');
            }
            if (Schema::hasColumn('recruitment_activities', 'visibility')) {
                $table->dropColumn('visibility');
            }
            if (Schema::hasColumn('recruitment_activities', 'operational_meta')) {
                $table->dropColumn('operational_meta');
            }
        });
    }
};
