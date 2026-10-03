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
        Schema::create('replacements', function (Blueprint $table) {
            $table->id();
            $table->string('business_id', 32)->unique();
            $table->foreignId('original_employee_id')->constrained('employees')->restrictOnDelete();
            $table->foreignId('original_placement_id')->constrained('placements')->restrictOnDelete();
            $table->foreignId('employer_profile_id')->constrained('employer_profiles')->restrictOnDelete();
            $table->foreignId('job_id')->constrained('job_listings')->restrictOnDelete();
            $table->foreignId('original_application_id')->constrained('applications')->restrictOnDelete();
            $table->foreignId('original_submission_id')->constrained('employer_submissions')->restrictOnDelete();
            $table->string('reason');
            $table->string('status');
            $table->text('notes')->nullable();
            $table->foreignId('replacement_application_id')->nullable()->constrained('applications')->nullOnDelete();
            $table->foreignId('replacement_submission_id')->nullable()->constrained('employer_submissions')->nullOnDelete();
            $table->foreignId('replacement_placement_id')->nullable()->constrained('placements')->nullOnDelete();
            $table->foreignId('replacement_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('requested_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('requested_at');
            $table->index('original_employee_id');
        });

        Schema::table('recruitment_activities', function (Blueprint $table) {
            $table->foreignId('replacement_id')
                ->nullable()
                ->constrained('replacements')
                ->nullOnDelete();
        });

        $now = now();
        DB::table('business_id_sequences')->insertOrIgnore([
            'prefix' => BusinessIdPrefix::Replacement->value,
            'next_number' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        Schema::table('recruitment_activities', function (Blueprint $table) {
            $table->dropConstrainedForeignId('replacement_id');
        });

        Schema::dropIfExists('replacements');
    }
};
