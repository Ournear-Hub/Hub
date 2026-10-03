<?php

use App\Services\AuthorizationBootstrapper;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('recruitment_automations')) {
            Schema::create('recruitment_automations', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('trigger');
                $table->string('module');
                $table->string('scope')->default('global');
                $table->foreignId('job_id')->nullable()->constrained('job_listings')->nullOnDelete();
                $table->foreignId('employer_profile_id')->nullable()->constrained('employer_profiles')->nullOnDelete();
                $table->boolean('is_enabled')->default(true);
                $table->json('conditions')->nullable();
                $table->json('actions');
                $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
                $table->timestamps();
                $table->index(['trigger', 'is_enabled']);
            });
        }

        if (! Schema::hasTable('recruitment_automation_executions')) {
            Schema::create('recruitment_automation_executions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('recruitment_automation_id');
                $table->string('trigger');
                $table->string('status');
                $table->string('idempotency_key');
                $table->unsignedBigInteger('application_id')->nullable();
                $table->unsignedBigInteger('job_id')->nullable();
                $table->unsignedBigInteger('employer_profile_id')->nullable();
                $table->unsignedBigInteger('interview_id')->nullable();
                $table->unsignedBigInteger('employer_submission_id')->nullable();
                $table->unsignedBigInteger('placement_id')->nullable();
                $table->unsignedBigInteger('replacement_id')->nullable();
                $table->unsignedBigInteger('task_id')->nullable();
                $table->unsignedTinyInteger('match_percent')->nullable();
                $table->string('match_state')->nullable();
                $table->json('result')->nullable();
                $table->text('error_message')->nullable();
                $table->timestamp('executed_at')->nullable();
                $table->timestamps();
                $table->unique('idempotency_key', 'ra_exec_idempotency_uq');
                $table->index(['recruitment_automation_id', 'status'], 'ra_exec_automation_status_idx');

                $table->foreign('recruitment_automation_id', 'ra_exec_automation_fk')
                    ->references('id')->on('recruitment_automations')->cascadeOnDelete();
                $table->foreign('application_id', 'ra_exec_application_fk')
                    ->references('id')->on('applications')->nullOnDelete();
                $table->foreign('job_id', 'ra_exec_job_fk')
                    ->references('id')->on('job_listings')->nullOnDelete();
                $table->foreign('employer_profile_id', 'ra_exec_employer_fk')
                    ->references('id')->on('employer_profiles')->nullOnDelete();
                $table->foreign('interview_id', 'ra_exec_interview_fk')
                    ->references('id')->on('application_interviews')->nullOnDelete();
                $table->foreign('employer_submission_id', 'ra_exec_submission_fk')
                    ->references('id')->on('employer_submissions')->nullOnDelete();
                $table->foreign('placement_id', 'ra_exec_placement_fk')
                    ->references('id')->on('placements')->nullOnDelete();
                $table->foreign('replacement_id', 'ra_exec_replacement_fk')
                    ->references('id')->on('replacements')->nullOnDelete();
                if (Schema::hasTable('tasks')) {
                    $table->foreign('task_id', 'ra_exec_task_fk')
                        ->references('id')->on('tasks')->nullOnDelete();
                }
            });
        }

        app(AuthorizationBootstrapper::class)->run();
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment_automation_executions');
        Schema::dropIfExists('recruitment_automations');
    }
};
