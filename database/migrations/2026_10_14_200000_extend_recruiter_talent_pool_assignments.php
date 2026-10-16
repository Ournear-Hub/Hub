<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recruiter_talent_pool_assignments', function (Blueprint $table) {
            if (! Schema::hasColumn('recruiter_talent_pool_assignments', 'assignment_type')) {
                $table->string('assignment_type', 32)->default('job')->after('employer_enquiry_id');
            }
            if (! Schema::hasColumn('recruiter_talent_pool_assignments', 'progress_stage')) {
                $table->string('progress_stage', 64)->default('assigned')->after('status');
            }
            if (! Schema::hasColumn('recruiter_talent_pool_assignments', 'follow_up_at')) {
                $table->timestamp('follow_up_at')->nullable()->after('recruiter_notes');
            }
            if (! Schema::hasColumn('recruiter_talent_pool_assignments', 'interview_at')) {
                $table->timestamp('interview_at')->nullable()->after('follow_up_at');
            }
            if (! Schema::hasColumn('recruiter_talent_pool_assignments', 'interview_details')) {
                $table->text('interview_details')->nullable()->after('interview_at');
            }
            if (! Schema::hasColumn('recruiter_talent_pool_assignments', 'employer_submission_id')) {
                $table->foreignId('employer_submission_id')
                    ->nullable()
                    ->after('interview_details')
                    ->constrained('employer_submissions')
                    ->nullOnDelete();
            }
        });

        if (Schema::hasTable('recruiter_talent_pool_assignment_status_events')
            && ! Schema::hasColumn('recruiter_talent_pool_assignment_status_events', 'progress_stage')) {
            Schema::table('recruiter_talent_pool_assignment_status_events', function (Blueprint $table) {
                $table->string('progress_stage', 64)->nullable()->after('to_status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('recruiter_talent_pool_assignment_status_events', 'progress_stage')) {
            Schema::table('recruiter_talent_pool_assignment_status_events', function (Blueprint $table) {
                $table->dropColumn('progress_stage');
            });
        }

        Schema::table('recruiter_talent_pool_assignments', function (Blueprint $table) {
            if (Schema::hasColumn('recruiter_talent_pool_assignments', 'employer_submission_id')) {
                $table->dropConstrainedForeignId('employer_submission_id');
            }
            $drops = array_values(array_filter([
                Schema::hasColumn('recruiter_talent_pool_assignments', 'assignment_type') ? 'assignment_type' : null,
                Schema::hasColumn('recruiter_talent_pool_assignments', 'progress_stage') ? 'progress_stage' : null,
                Schema::hasColumn('recruiter_talent_pool_assignments', 'follow_up_at') ? 'follow_up_at' : null,
                Schema::hasColumn('recruiter_talent_pool_assignments', 'interview_at') ? 'interview_at' : null,
                Schema::hasColumn('recruiter_talent_pool_assignments', 'interview_details') ? 'interview_details' : null,
            ]));
            if ($drops !== []) {
                $table->dropColumn($drops);
            }
        });
    }
};
