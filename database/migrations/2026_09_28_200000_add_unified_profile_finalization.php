<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'professional_headline')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('professional_headline', 160)->nullable()->after('bio');
            });
        }

        if (Schema::hasTable('candidate_profiles') && ! Schema::hasTable('candidate_educations')) {
            Schema::create('candidate_educations', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('candidate_profile_id')->constrained('candidate_profiles')->cascadeOnDelete();
                $table->string('institution');
                $table->string('qualification')->nullable();
                $table->string('field')->nullable();
                $table->date('started_on')->nullable();
                $table->date('ended_on')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('candidate_profiles') && ! Schema::hasTable('candidate_projects')) {
            Schema::create('candidate_projects', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('candidate_profile_id')->constrained('candidate_profiles')->cascadeOnDelete();
                $table->string('name');
                $table->string('role')->nullable();
                $table->string('company')->nullable();
                $table->text('description')->nullable();
                $table->date('started_on')->nullable();
                $table->date('ended_on')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_projects');
        Schema::dropIfExists('candidate_educations');

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'professional_headline')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('professional_headline');
            });
        }
    }
};
