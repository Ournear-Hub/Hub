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
        if (! Schema::hasTable('staffing_candidate_submissions')) {
            Schema::create('staffing_candidate_submissions', function (Blueprint $table) {
                $table->id();
                $table->string('business_id', 32)->unique();
                $table->unsignedBigInteger('employer_enquiry_id');
                $table->unsignedBigInteger('candidate_user_id');
                $table->unsignedBigInteger('responsible_recruiter_user_id')->nullable();
                $table->unsignedBigInteger('submitted_by_user_id');
                $table->unsignedBigInteger('recruiter_talent_pool_assignment_id')->nullable();
                $table->string('status', 64);
                $table->text('internal_notes')->nullable();
                $table->text('client_feedback')->nullable();
                $table->unsignedBigInteger('rejection_reason_id')->nullable();
                $table->timestamp('submitted_at');
                $table->unsignedBigInteger('decided_by_user_id')->nullable();
                $table->timestamp('decided_at')->nullable();
                $table->date('joining_date')->nullable();
                $table->text('joining_confirmation_notes')->nullable();
                $table->unsignedBigInteger('joining_confirmed_by_user_id')->nullable();
                $table->timestamp('joining_confirmed_at')->nullable();
                $table->timestamp('fulfilment_counted_at')->nullable();
                $table->timestamps();

                $table->index(['employer_enquiry_id', 'status'], 'stf_sub_enquiry_status_idx');
                $table->index(['candidate_user_id', 'employer_enquiry_id'], 'stf_sub_candidate_enquiry_idx');
                $table->index(['responsible_recruiter_user_id', 'status'], 'stf_sub_recruiter_status_idx');

                $table->foreign('employer_enquiry_id', 'stf_sub_enquiry_fk')->references('id')->on('employer_enquiries')->restrictOnDelete();
                $table->foreign('candidate_user_id', 'stf_sub_candidate_fk')->references('id')->on('users')->restrictOnDelete();
                $table->foreign('responsible_recruiter_user_id', 'stf_sub_recruiter_fk')->references('id')->on('users')->nullOnDelete();
                $table->foreign('submitted_by_user_id', 'stf_sub_submitted_by_fk')->references('id')->on('users')->restrictOnDelete();
                $table->foreign('recruiter_talent_pool_assignment_id', 'stf_sub_assignment_fk')
                    ->references('id')->on('recruiter_talent_pool_assignments')->nullOnDelete();
                $table->foreign('rejection_reason_id', 'stf_sub_rejection_fk')->references('id')->on('rejection_reasons')->nullOnDelete();
                $table->foreign('decided_by_user_id', 'stf_sub_decided_by_fk')->references('id')->on('users')->nullOnDelete();
                $table->foreign('joining_confirmed_by_user_id', 'stf_sub_join_confirmed_fk')->references('id')->on('users')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('staffing_submission_status_events')) {
            Schema::create('staffing_submission_status_events', function (Blueprint $table) {
                $table->id();
                $table->foreignId('staffing_candidate_submission_id')
                    ->constrained('staffing_candidate_submissions', indexName: 'stf_sub_status_events_submission_fk')
                    ->cascadeOnDelete();
                $table->string('from_status')->nullable();
                $table->string('to_status');
                $table->text('notes')->nullable();
                $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['staffing_candidate_submission_id', 'created_at'], 'stf_sub_status_events_timeline_idx');
            });
        }

        if (Schema::hasTable('employer_enquiries') && ! Schema::hasColumn('employer_enquiries', 'fulfilled_joinings_count')) {
            Schema::table('employer_enquiries', function (Blueprint $table) {
                $table->unsignedInteger('fulfilled_joinings_count')->default(0)->after('number_of_people');
            });
        }

        if (Schema::hasTable('recruiter_talent_pool_assignments')
            && ! Schema::hasColumn('recruiter_talent_pool_assignments', 'staffing_candidate_submission_id')) {
            Schema::table('recruiter_talent_pool_assignments', function (Blueprint $table) {
                $table->foreignId('staffing_candidate_submission_id')
                    ->nullable()
                    ->after('talent_pool_company_submission_id')
                    ->constrained('staffing_candidate_submissions', indexName: 'rtp_assign_stf_submission_fk')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasTable('recruitment_activities')
            && ! Schema::hasColumn('recruitment_activities', 'staffing_candidate_submission_id')) {
            Schema::table('recruitment_activities', function (Blueprint $table) {
                $table->foreignId('staffing_candidate_submission_id')
                    ->nullable()
                    ->after('talent_pool_company_submission_id')
                    ->constrained('staffing_candidate_submissions', indexName: 'recruitment_activities_stf_submission_fk')
                    ->nullOnDelete();
            });
        }

        $now = now();
        DB::table('business_id_sequences')->insertOrIgnore([
            'prefix' => BusinessIdPrefix::StaffingSubmission->value,
            'next_number' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('recruitment_activities', 'staffing_candidate_submission_id')) {
            Schema::table('recruitment_activities', function (Blueprint $table) {
                $table->dropConstrainedForeignId('staffing_candidate_submission_id');
            });
        }

        if (Schema::hasColumn('recruiter_talent_pool_assignments', 'staffing_candidate_submission_id')) {
            Schema::table('recruiter_talent_pool_assignments', function (Blueprint $table) {
                $table->dropConstrainedForeignId('staffing_candidate_submission_id');
            });
        }

        if (Schema::hasColumn('employer_enquiries', 'fulfilled_joinings_count')) {
            Schema::table('employer_enquiries', function (Blueprint $table) {
                $table->dropColumn('fulfilled_joinings_count');
            });
        }

        Schema::dropIfExists('staffing_submission_status_events');
        Schema::dropIfExists('staffing_candidate_submissions');
    }
};
