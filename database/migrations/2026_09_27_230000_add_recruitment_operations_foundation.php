<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('application_interviews', function (Blueprint $table) {
            if (! Schema::hasColumn('application_interviews', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(1)->after('application_id');
            }
            if (! Schema::hasColumn('application_interviews', 'type')) {
                $table->string('type')->nullable()->after('sort_order');
            }
            if (! Schema::hasColumn('application_interviews', 'lifecycle_status')) {
                $table->string('lifecycle_status')->default('scheduled')->after('type');
            }
            if (! Schema::hasColumn('application_interviews', 'confirmed_at')) {
                $table->timestamp('confirmed_at')->nullable()->after('notes');
            }
            if (! Schema::hasColumn('application_interviews', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('confirmed_at');
            }
            if (! Schema::hasColumn('application_interviews', 'cancelled_reason')) {
                $table->string('cancelled_reason')->nullable()->after('cancelled_at');
            }
            if (! Schema::hasColumn('application_interviews', 'rescheduled_from_id')) {
                $table->foreignId('rescheduled_from_id')
                    ->nullable()
                    ->after('cancelled_reason')
                    ->constrained('application_interviews')
                    ->nullOnDelete();
            }
            if (! Schema::hasColumn('application_interviews', 'interviewer_user_id')) {
                $table->foreignId('interviewer_user_id')
                    ->nullable()
                    ->after('interviewer_name')
                    ->constrained('users')
                    ->nullOnDelete();
            }
            if (! Schema::hasColumn('application_interviews', 'reminder_sent_at')) {
                $table->timestamp('reminder_sent_at')->nullable()->after('created_by');
            }
        });

        $this->dropApplicationInterviewUniqueness();

        DB::table('application_interviews')->where(function ($query) {
            $query->whereNull('sort_order')->orWhere('sort_order', 0);
        })->update(['sort_order' => 1]);

        DB::table('application_interviews')->where(function ($query) {
            $query->whereNull('lifecycle_status')->orWhere('lifecycle_status', '');
        })->update(['lifecycle_status' => 'scheduled']);

        if (! Schema::hasTable('scorecard_templates')) {
            Schema::create('scorecard_templates', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->json('criteria');
                $table->boolean('is_active')->default(true);
                $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('interview_scorecards')) {
            Schema::create('interview_scorecards', function (Blueprint $table) {
                $table->id();
                $table->foreignId('application_interview_id')->constrained('application_interviews')->cascadeOnDelete();
                $table->foreignId('scorecard_template_id')->nullable()->constrained('scorecard_templates')->nullOnDelete();
                $table->json('qualitative')->nullable();
                $table->decimal('numeric_score', 5, 2)->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('submitted_by')->constrained('users')->restrictOnDelete();
                $table->timestamp('submitted_at')->nullable();
                $table->timestamps();
                $table->unique('application_interview_id');
            });
        }

        if (! Schema::hasTable('tasks')) {
            Schema::create('tasks', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->text('body')->nullable();
                $table->string('status')->default('open');
                $table->timestamp('due_at')->nullable();
                $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
                $table->foreignId('application_id')->nullable()->constrained('applications')->nullOnDelete();
                $table->foreignId('job_id')->nullable()->constrained('job_listings')->nullOnDelete();
                $table->foreignId('candidate_profile_id')->nullable()->constrained('candidate_profiles')->nullOnDelete();
                $table->timestamp('completed_at')->nullable();
                $table->timestamp('reminder_sent_at')->nullable();
                $table->timestamps();
            });
        }

        Schema::table('recruitment_activities', function (Blueprint $table) {
            if (! Schema::hasColumn('recruitment_activities', 'interview_id')) {
                $table->foreignId('interview_id')
                    ->nullable()
                    ->constrained('application_interviews')
                    ->nullOnDelete();
            }
            if (! Schema::hasColumn('recruitment_activities', 'task_id')) {
                $table->foreignId('task_id')
                    ->nullable()
                    ->constrained('tasks')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('recruitment_activities', function (Blueprint $table) {
            if (Schema::hasColumn('recruitment_activities', 'task_id')) {
                $table->dropConstrainedForeignId('task_id');
            }
            if (Schema::hasColumn('recruitment_activities', 'interview_id')) {
                $table->dropConstrainedForeignId('interview_id');
            }
        });

        Schema::dropIfExists('tasks');
        Schema::dropIfExists('interview_scorecards');
        Schema::dropIfExists('scorecard_templates');
    }

    private function dropApplicationInterviewUniqueness(): void
    {
        $indexes = Schema::getIndexes('application_interviews');
        $hasPlainIndex = false;

        foreach ($indexes as $index) {
            $columns = $index['columns'] ?? [];
            $unique = (bool) ($index['unique'] ?? false);
            $primary = (bool) ($index['primary'] ?? false);

            if (! $primary && ! $unique && $columns === ['application_id']) {
                $hasPlainIndex = true;
            }
        }

        if (! $hasPlainIndex) {
            Schema::table('application_interviews', function (Blueprint $table) {
                $table->index('application_id', 'application_interviews_application_id_index');
            });
        }

        foreach (Schema::getIndexes('application_interviews') as $index) {
            $columns = $index['columns'] ?? [];
            $unique = (bool) ($index['unique'] ?? false);
            $primary = (bool) ($index['primary'] ?? false);

            if (! $unique || $primary || $columns !== ['application_id']) {
                continue;
            }

            Schema::table('application_interviews', function (Blueprint $table) use ($index) {
                $table->dropUnique($index['name']);
            });
        }
    }
};
