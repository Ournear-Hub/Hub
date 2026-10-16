<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('company_relationship_recruiter_assignments')) {
            return;
        }

        Schema::create('company_relationship_recruiter_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employer_profile_id')
                ->constrained('employer_profiles', indexName: 'crr_assign_employer_fk')
                ->cascadeOnDelete();
            $table->foreignId('assigned_recruiter_user_id')
                ->constrained('users', indexName: 'crr_assign_recruiter_fk');
            $table->foreignId('assigned_by_user_id')
                ->nullable()
                ->constrained('users', indexName: 'crr_assign_by_fk');
            $table->string('status', 32)->default('active');
            $table->timestamp('ended_at')->nullable();
            $table->foreignId('ended_by_user_id')
                ->nullable()
                ->constrained('users', indexName: 'crr_assign_ended_by_fk');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['employer_profile_id', 'status'], 'crr_assign_employer_status_idx');
            $table->index(['assigned_recruiter_user_id', 'status'], 'crr_assign_recruiter_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_relationship_recruiter_assignments');
    }
};
