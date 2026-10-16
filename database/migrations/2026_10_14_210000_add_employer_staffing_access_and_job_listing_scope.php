<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employer_profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('employer_profiles', 'staffing_access_status')) {
                $table->string('staffing_access_status', 32)->default('not_requested')->after('job_posting_approved_by');
            }
            if (! Schema::hasColumn('employer_profiles', 'staffing_access_requested_at')) {
                $table->timestamp('staffing_access_requested_at')->nullable()->after('staffing_access_status');
            }
            if (! Schema::hasColumn('employer_profiles', 'staffing_access_decided_at')) {
                $table->timestamp('staffing_access_decided_at')->nullable()->after('staffing_access_requested_at');
            }
            if (! Schema::hasColumn('employer_profiles', 'staffing_access_decided_by_user_id')) {
                $table->foreignId('staffing_access_decided_by_user_id')
                    ->nullable()
                    ->after('staffing_access_decided_at')
                    ->constrained('users')
                    ->nullOnDelete();
            }
            if (! Schema::hasColumn('employer_profiles', 'staffing_access_rejection_reason')) {
                $table->text('staffing_access_rejection_reason')->nullable()->after('staffing_access_decided_by_user_id');
            }
        });

        if (! Schema::hasTable('employer_staffing_access_events')) {
            Schema::create('employer_staffing_access_events', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employer_profile_id')->constrained('employer_profiles')->cascadeOnDelete();
                $table->string('from_status', 32)->nullable();
                $table->string('to_status', 32);
                $table->text('notes')->nullable();
                $table->foreignId('actor_id')->constrained('users')->cascadeOnDelete();
                $table->timestamps();

                $table->index(['employer_profile_id', 'created_at'], 'emp_staff_access_events_timeline_idx');
            });
        }

        Schema::table('job_listings', function (Blueprint $table) {
            if (! Schema::hasColumn('job_listings', 'listing_scope')) {
                $table->string('listing_scope', 32)->default('employer_public')->after('listing_priority');
                $table->index('listing_scope');
            }
        });
    }

    public function down(): void
    {
        Schema::table('job_listings', function (Blueprint $table) {
            if (Schema::hasColumn('job_listings', 'listing_scope')) {
                $table->dropIndex(['listing_scope']);
                $table->dropColumn('listing_scope');
            }
        });

        Schema::dropIfExists('employer_staffing_access_events');

        Schema::table('employer_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('employer_profiles', 'staffing_access_decided_by_user_id')) {
                $table->dropConstrainedForeignId('staffing_access_decided_by_user_id');
            }
            foreach ([
                'staffing_access_rejection_reason',
                'staffing_access_decided_at',
                'staffing_access_requested_at',
                'staffing_access_status',
            ] as $column) {
                if (Schema::hasColumn('employer_profiles', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
