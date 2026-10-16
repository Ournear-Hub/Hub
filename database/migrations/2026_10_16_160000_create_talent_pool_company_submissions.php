<?php

use App\Enums\BusinessIdPrefix;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('talent_pool_company_submissions')) {
            Schema::create('talent_pool_company_submissions', function (Blueprint $table) {
                $table->id();
                $table->string('business_id', 32)->unique();
                $table->unsignedBigInteger('candidate_user_id');
                $table->unsignedBigInteger('employer_profile_id');
                $table->unsignedBigInteger('responsible_recruiter_user_id')->nullable();
                $table->unsignedBigInteger('candidate_owner_recruiter_user_id')->nullable();
                $table->unsignedBigInteger('company_owner_recruiter_user_id')->nullable();
                $table->string('owner_resolution', 64);
                $table->unsignedBigInteger('submitted_by_user_id');
                $table->unsignedBigInteger('recruiter_talent_pool_assignment_id')->nullable();
                $table->string('status');
                $table->text('internal_notes')->nullable();
                $table->text('employer_notes')->nullable();
                $table->unsignedBigInteger('rejection_reason_id')->nullable();
                $table->timestamp('submitted_at');
                $table->unsignedBigInteger('decided_by_user_id')->nullable();
                $table->timestamp('decided_at')->nullable();
                $table->timestamps();

                $table->index(['employer_profile_id', 'status'], 'tp_submission_employer_status_idx');
                $table->index(['candidate_user_id', 'employer_profile_id'], 'tp_submission_candidate_company_idx');
                $table->index(['responsible_recruiter_user_id', 'status'], 'tp_submission_recruiter_status_idx');

                $table->foreign('candidate_user_id', 'tp_sub_candidate_user_fk')->references('id')->on('users')->restrictOnDelete();
                $table->foreign('employer_profile_id', 'tp_sub_employer_fk')->references('id')->on('employer_profiles')->restrictOnDelete();
                $table->foreign('responsible_recruiter_user_id', 'tp_sub_resp_recruiter_fk')->references('id')->on('users')->nullOnDelete();
                $table->foreign('candidate_owner_recruiter_user_id', 'tp_sub_cand_owner_fk')->references('id')->on('users')->nullOnDelete();
                $table->foreign('company_owner_recruiter_user_id', 'tp_sub_co_owner_fk')->references('id')->on('users')->nullOnDelete();
                $table->foreign('submitted_by_user_id', 'tp_sub_submitted_by_fk')->references('id')->on('users')->restrictOnDelete();
                $table->foreign('recruiter_talent_pool_assignment_id', 'tp_sub_assignment_fk')
                    ->references('id')->on('recruiter_talent_pool_assignments')->nullOnDelete();
                $table->foreign('rejection_reason_id', 'tp_sub_rejection_fk')->references('id')->on('rejection_reasons')->nullOnDelete();
                $table->foreign('decided_by_user_id', 'tp_sub_decided_by_fk')->references('id')->on('users')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('talent_pool_submission_status_events')) {
            Schema::create('talent_pool_submission_status_events', function (Blueprint $table) {
                $table->id();
                $table->foreignId('talent_pool_company_submission_id')
                    ->constrained('talent_pool_company_submissions', indexName: 'tp_submission_status_events_submission_fk')
                    ->cascadeOnDelete();
                $table->string('from_status')->nullable();
                $table->string('to_status');
                $table->text('notes')->nullable();
                $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['talent_pool_company_submission_id', 'created_at'], 'tp_submission_status_events_timeline_idx');
            });
        }

        if (Schema::hasTable('recruiter_talent_pool_assignments')
            && ! Schema::hasColumn('recruiter_talent_pool_assignments', 'talent_pool_company_submission_id')) {
            Schema::table('recruiter_talent_pool_assignments', function (Blueprint $table) {
                $table->foreignId('talent_pool_company_submission_id')
                    ->nullable()
                    ->after('employer_submission_id')
                    ->constrained('talent_pool_company_submissions', indexName: 'rtp_assign_tp_submission_fk')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasTable('recruitment_activities')
            && ! Schema::hasColumn('recruitment_activities', 'talent_pool_company_submission_id')) {
            Schema::table('recruitment_activities', function (Blueprint $table) {
                $table->foreignId('talent_pool_company_submission_id')
                    ->nullable()
                    ->after('employer_submission_id')
                    ->constrained('talent_pool_company_submissions', indexName: 'recruitment_activities_tp_submission_fk')
                    ->nullOnDelete();
            });
        }

        $now = now();
        DB::table('business_id_sequences')->insertOrIgnore([
            'prefix' => BusinessIdPrefix::TalentPoolSubmission->value,
            'next_number' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('recruitment_activities', 'talent_pool_company_submission_id')) {
            Schema::table('recruitment_activities', function (Blueprint $table) {
                $table->dropConstrainedForeignId('talent_pool_company_submission_id');
            });
        }

        if (Schema::hasColumn('recruiter_talent_pool_assignments', 'talent_pool_company_submission_id')) {
            Schema::table('recruiter_talent_pool_assignments', function (Blueprint $table) {
                $table->dropConstrainedForeignId('talent_pool_company_submission_id');
            });
        }

        Schema::dropIfExists('talent_pool_submission_status_events');
        Schema::dropIfExists('talent_pool_company_submissions');
    }
};
