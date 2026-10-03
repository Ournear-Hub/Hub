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
        Schema::create('placements', function (Blueprint $table) {
            $table->id();
            $table->string('business_id', 32)->unique();
            $table->foreignId('employer_submission_id')->unique()->constrained('employer_submissions')->restrictOnDelete();
            $table->foreignId('employer_profile_id')->constrained('employer_profiles')->restrictOnDelete();
            $table->foreignId('job_id')->constrained('job_listings')->restrictOnDelete();
            $table->foreignId('application_id')->constrained('applications')->restrictOnDelete();
            $table->string('status');
            $table->text('notes')->nullable();
            $table->date('expected_joining_date')->nullable();
            $table->timestamp('joining_confirmed_at')->nullable();
            $table->foreignId('joining_confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('actual_joining_date')->nullable();
            $table->text('joining_notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index('status');
            $table->index('created_at');
        });

        Schema::create('placement_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('placement_id')->unique()->constrained('placements')->restrictOnDelete();
            $table->date('offered_at');
            $table->string('salary')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('declined_at')->nullable();
            $table->text('decline_notes')->nullable();
            $table->timestamp('withdrawn_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('business_id', 32)->unique();
            $table->foreignId('placement_id')->unique()->constrained('placements')->restrictOnDelete();
            $table->foreignId('employer_profile_id')->constrained('employer_profiles')->restrictOnDelete();
            $table->foreignId('job_id')->constrained('job_listings')->restrictOnDelete();
            $table->foreignId('application_id')->constrained('applications')->restrictOnDelete();
            $table->foreignId('employer_submission_id')->constrained('employer_submissions')->restrictOnDelete();
            $table->foreignId('candidate_profile_id')->nullable()->constrained('candidate_profiles')->nullOnDelete();
            $table->timestamp('joined_at');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::table('recruitment_activities', function (Blueprint $table) {
            $table->foreignId('placement_id')
                ->nullable()
                ->constrained('placements')
                ->nullOnDelete();
            $table->foreignId('employee_id')
                ->nullable()
                ->constrained('employees')
                ->nullOnDelete();
        });

        $now = now();
        foreach ([BusinessIdPrefix::Placement, BusinessIdPrefix::Employee] as $prefix) {
            DB::table('business_id_sequences')->insertOrIgnore([
                'prefix' => $prefix->value,
                'next_number' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('recruitment_activities', function (Blueprint $table) {
            $table->dropConstrainedForeignId('placement_id');
            $table->dropConstrainedForeignId('employee_id');
        });

        Schema::dropIfExists('employees');
        Schema::dropIfExists('placement_offers');
        Schema::dropIfExists('placements');
    }
};
