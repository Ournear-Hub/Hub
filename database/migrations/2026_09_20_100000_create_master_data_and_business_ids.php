<?php

use App\Enums\BusinessIdPrefix;
use App\Services\MasterData\MasterDataBootstrapper;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('industries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique('name');
            $table->index('is_active');
        });

        Schema::create('job_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('industry_id')->constrained('industries')->restrictOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['industry_id', 'name']);
            $table->index('is_active');
        });

        Schema::create('job_titles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_category_id')->constrained('job_categories')->restrictOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['job_category_id', 'name']);
            $table->index('is_active');
        });

        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique('name');
            $table->index('is_active');
        });

        Schema::create('employment_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique('name');
            $table->index('is_active');
        });

        Schema::create('experience_ranges', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedTinyInteger('min_years')->nullable();
            $table->unsignedTinyInteger('max_years')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique('name');
            $table->index('is_active');
        });

        Schema::create('qualifications', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique('name');
            $table->index('is_active');
        });

        Schema::create('job_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique('code');
            $table->unique('name');
            $table->index('is_active');
        });

        Schema::create('application_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique('code');
            $table->unique('name');
            $table->index('is_active');
        });

        Schema::create('rejection_reasons', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique('name');
            $table->index('is_active');
        });

        Schema::create('business_id_sequences', function (Blueprint $table) {
            $table->string('prefix', 16)->primary();
            $table->unsignedInteger('next_number');
            $table->timestamps();
        });

        Schema::create('master_data_changes', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id');
            $table->string('action');
            $table->string('body');
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['entity_type', 'entity_id']);
        });

        Schema::table('job_listings', function (Blueprint $table) {
            $table->string('business_id', 32)->nullable()->unique();
            $table->foreignId('industry_id')->nullable()->constrained('industries')->nullOnDelete();
            $table->foreignId('job_category_id')->nullable()->constrained('job_categories')->nullOnDelete();
            $table->foreignId('job_title_id')->nullable()->constrained('job_titles')->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('employment_type_id')->nullable()->constrained('employment_types')->nullOnDelete();
            $table->foreignId('experience_range_id')->nullable()->constrained('experience_ranges')->nullOnDelete();
        });

        Schema::table('applications', function (Blueprint $table) {
            $table->string('business_id', 32)->nullable()->unique();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('experience_range_id')->nullable()->constrained('experience_ranges')->nullOnDelete();
            $table->foreignId('qualification_id')->nullable()->constrained('qualifications')->nullOnDelete();
        });

        Schema::table('candidate_profiles', function (Blueprint $table) {
            $table->string('business_id', 32)->nullable()->unique();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('experience_range_id')->nullable()->constrained('experience_ranges')->nullOnDelete();
            $table->foreignId('qualification_id')->nullable()->constrained('qualifications')->nullOnDelete();
        });

        Schema::table('employer_profiles', function (Blueprint $table) {
            $table->string('business_id', 32)->nullable()->unique();
            $table->foreignId('industry_id')->nullable()->constrained('industries')->nullOnDelete();
        });

        Schema::table('employer_enquiries', function (Blueprint $table) {
            $table->foreignId('industry_id')->nullable()->constrained('industries')->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
        });

        $now = now();

        foreach (BusinessIdPrefix::cases() as $prefix) {
            DB::table('business_id_sequences')->insert([
                'prefix' => $prefix->value,
                'next_number' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        app(MasterDataBootstrapper::class)->run();
    }

    public function down(): void
    {
        Schema::table('employer_enquiries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('industry_id');
            $table->dropConstrainedForeignId('location_id');
        });

        Schema::table('employer_profiles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('industry_id');
            $table->dropUnique(['business_id']);
            $table->dropColumn('business_id');
        });

        Schema::table('candidate_profiles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('location_id');
            $table->dropConstrainedForeignId('experience_range_id');
            $table->dropConstrainedForeignId('qualification_id');
            $table->dropUnique(['business_id']);
            $table->dropColumn('business_id');
        });

        Schema::table('applications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('location_id');
            $table->dropConstrainedForeignId('experience_range_id');
            $table->dropConstrainedForeignId('qualification_id');
            $table->dropUnique(['business_id']);
            $table->dropColumn('business_id');
        });

        Schema::table('job_listings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('industry_id');
            $table->dropConstrainedForeignId('job_category_id');
            $table->dropConstrainedForeignId('job_title_id');
            $table->dropConstrainedForeignId('location_id');
            $table->dropConstrainedForeignId('employment_type_id');
            $table->dropConstrainedForeignId('experience_range_id');
            $table->dropUnique(['business_id']);
            $table->dropColumn('business_id');
        });

        Schema::dropIfExists('master_data_changes');
        Schema::dropIfExists('business_id_sequences');
        Schema::dropIfExists('rejection_reasons');
        Schema::dropIfExists('application_statuses');
        Schema::dropIfExists('job_statuses');
        Schema::dropIfExists('qualifications');
        Schema::dropIfExists('experience_ranges');
        Schema::dropIfExists('employment_types');
        Schema::dropIfExists('job_titles');
        Schema::dropIfExists('job_categories');
        Schema::dropIfExists('locations');
        Schema::dropIfExists('industries');
    }
};
