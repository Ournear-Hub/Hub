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
        Schema::table('job_listings', function (Blueprint $table) {
            $table->foreignId('employer_id')
                ->nullable()
                ->constrained('employer_profiles')
                ->nullOnDelete();
        });

        Schema::create('employer_submissions', function (Blueprint $table) {
            $table->id();
            $table->string('business_id', 32)->unique();
            $table->foreignId('employer_profile_id')->constrained('employer_profiles')->restrictOnDelete();
            $table->foreignId('job_id')->constrained('job_listings')->restrictOnDelete();
            $table->foreignId('application_id')->constrained('applications')->restrictOnDelete();
            $table->string('status');
            $table->text('notes')->nullable();
            $table->foreignId('rejection_reason_id')->nullable()->constrained('rejection_reasons')->nullOnDelete();
            $table->foreignId('submitted_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('submitted_at');
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->unique(['employer_profile_id', 'job_id', 'application_id'], 'employer_submissions_unique');
            $table->index('status');
            $table->index('submitted_at');
        });

        Schema::table('recruitment_activities', function (Blueprint $table) {
            $table->foreignId('employer_submission_id')
                ->nullable()
                ->constrained('employer_submissions')
                ->nullOnDelete();
            $table->foreignId('employer_profile_id')
                ->nullable()
                ->constrained('employer_profiles')
                ->nullOnDelete();
        });

        $now = now();
        DB::table('business_id_sequences')->insertOrIgnore([
            'prefix' => BusinessIdPrefix::Submission->value,
            'next_number' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        Schema::table('recruitment_activities', function (Blueprint $table) {
            $table->dropConstrainedForeignId('employer_submission_id');
            $table->dropConstrainedForeignId('employer_profile_id');
        });

        Schema::dropIfExists('employer_submissions');

        Schema::table('job_listings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('employer_id');
        });
    }
};
