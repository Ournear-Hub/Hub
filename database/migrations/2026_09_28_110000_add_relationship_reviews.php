<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('relationship_reviews')) {
            return;
        }

        Schema::create('relationship_reviews', function (Blueprint $table) {
            $table->id();
            $table->string('business_id')->unique();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('employer_profile_id')->constrained('employer_profiles')->cascadeOnDelete();
            $table->foreignId('candidate_profile_id')->constrained('candidate_profiles')->cascadeOnDelete();
            $table->foreignId('reviewer_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('direction', 32);
            $table->unsignedTinyInteger('rating');
            $table->text('body');
            $table->string('visibility', 32)->default('private');
            $table->string('status', 32)->default('submitted');
            $table->foreignId('hidden_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('hidden_at')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'direction'], 'relationship_reviews_employee_direction_unique');
            $table->unique(
                ['employee_id', 'reviewer_user_id', 'direction'],
                'relationship_reviews_reviewer_direction_unique',
            );
            $table->index(['employer_profile_id', 'status'], 'relationship_reviews_employer_status_idx');
            $table->index(['candidate_profile_id', 'status'], 'relationship_reviews_candidate_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('relationship_reviews');
    }
};
