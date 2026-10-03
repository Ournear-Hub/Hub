<?php

use App\Enums\BusinessIdPrefix;
use App\Services\AuthorizationBootstrapper;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('freelancers', function (Blueprint $table) {
            $table->id();
            $table->string('business_id')->unique();
            $table->string('submission_token', 64)->nullable()->unique();
            $table->string('name');
            $table->string('mobile', 40);
            $table->string('email');
            $table->string('gender', 32)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->foreignId('current_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->string('primary_skill');
            $table->string('current_designation')->nullable();
            $table->string('current_company')->nullable();
            $table->foreignId('industry_id')->nullable()->constrained('industries')->nullOnDelete();
            $table->foreignId('job_category_id')->nullable()->constrained('job_categories')->nullOnDelete();
            $table->foreignId('experience_range_id')->nullable()->constrained('experience_ranges')->nullOnDelete();
            $table->foreignId('employment_type_id')->nullable()->constrained('employment_types')->nullOnDelete();
            $table->string('preferred_work_type', 32)->nullable();
            $table->string('expected_rate')->nullable();
            $table->string('notice_period', 32)->nullable();
            $table->string('availability')->nullable();
            $table->json('skills')->nullable();
            $table->text('summary')->nullable();
            $table->string('resume_disk')->nullable();
            $table->string('resume_path')->nullable();
            $table->string('resume_original_name')->nullable();
            $table->string('resume_mime')->nullable();
            $table->unsignedInteger('resume_size')->nullable();
            $table->string('certificate_disk')->nullable();
            $table->string('certificate_path')->nullable();
            $table->string('certificate_original_name')->nullable();
            $table->string('certificate_mime')->nullable();
            $table->unsignedInteger('certificate_size')->nullable();
            $table->boolean('consent')->default(false);
            $table->string('status', 32)->default('new');
            $table->json('status_history')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->index('email');
            $table->index('mobile');
            $table->index('primary_skill');
            $table->index('status');
            $table->index('submitted_at');
        });

        Schema::create('freelancer_preferred_location', function (Blueprint $table) {
            $table->id();
            $table->foreignId('freelancer_id')->constrained('freelancers')->cascadeOnDelete();
            $table->foreignId('location_id')->constrained('locations')->cascadeOnDelete();
            $table->unique(['freelancer_id', 'location_id']);
        });

        if (Schema::hasTable('business_id_sequences')) {
            $now = now();
            DB::table('business_id_sequences')->insertOrIgnore([
                'prefix' => BusinessIdPrefix::Freelancer->value,
                'next_number' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        app(AuthorizationBootstrapper::class)->run();
    }

    public function down(): void
    {
        Schema::dropIfExists('freelancer_preferred_location');
        Schema::dropIfExists('freelancers');
    }
};
