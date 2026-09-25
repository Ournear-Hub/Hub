<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('candidate_profiles')) {
            Schema::table('candidate_profiles', function (Blueprint $table) {
                if (! Schema::hasColumn('candidate_profiles', 'current_designation')) {
                    $table->string('current_designation')->nullable();
                }
                if (! Schema::hasColumn('candidate_profiles', 'current_company')) {
                    $table->string('current_company')->nullable();
                }
                if (! Schema::hasColumn('candidate_profiles', 'industry_id')) {
                    $table->foreignId('industry_id')->nullable()->constrained('industries')->nullOnDelete();
                }
                if (! Schema::hasColumn('candidate_profiles', 'job_category_id')) {
                    $table->foreignId('job_category_id')->nullable()->constrained('job_categories')->nullOnDelete();
                }
                if (! Schema::hasColumn('candidate_profiles', 'current_salary')) {
                    $table->decimal('current_salary', 12, 2)->nullable();
                }
                if (! Schema::hasColumn('candidate_profiles', 'expected_salary')) {
                    $table->decimal('expected_salary', 12, 2)->nullable();
                }
                if (! Schema::hasColumn('candidate_profiles', 'notice_period')) {
                    $table->string('notice_period', 32)->nullable();
                }
                if (! Schema::hasColumn('candidate_profiles', 'employment_preference')) {
                    $table->text('employment_preference')->nullable();
                }
            });
        }

        if (! Schema::hasTable('skills')) {
            Schema::create('skills', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('normalized_name');
                $table->timestamps();

                $table->unique('normalized_name');
            });
        }

        if (! Schema::hasTable('candidate_skill')) {
            Schema::create('candidate_skill', function (Blueprint $table) {
                $table->id();
                $table->foreignId('candidate_profile_id')->constrained('candidate_profiles')->cascadeOnDelete();
                $table->foreignId('skill_id')->constrained('skills')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['candidate_profile_id', 'skill_id']);
            });
        }

        if (! Schema::hasTable('candidate_employment_histories')) {
            Schema::create('candidate_employment_histories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('candidate_profile_id')->constrained('candidate_profiles')->cascadeOnDelete();
                $table->string('company');
                $table->string('designation');
                $table->foreignId('industry_id')->nullable()->constrained('industries')->nullOnDelete();
                $table->date('started_on');
                $table->date('ended_on')->nullable();
                $table->boolean('is_current')->default(false);
                $table->text('description')->nullable();
                $table->timestamps();

                $table->index(['candidate_profile_id', 'is_current'], 'candidate_history_current_idx');
            });
        }

        if (! Schema::hasTable('candidate_preferred_location')) {
            Schema::create('candidate_preferred_location', function (Blueprint $table) {
                $table->id();
                $table->foreignId('candidate_profile_id')->constrained('candidate_profiles')->cascadeOnDelete();
                $table->foreignId('location_id')->constrained('locations')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['candidate_profile_id', 'location_id'], 'candidate_preferred_location_unique');
            });
        }

        if (! Schema::hasTable('candidate_preferred_employment_type')) {
            Schema::create('candidate_preferred_employment_type', function (Blueprint $table) {
                $table->id();
                $table->foreignId('candidate_profile_id')->constrained('candidate_profiles')->cascadeOnDelete();
                $table->foreignId('employment_type_id')->constrained('employment_types')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['candidate_profile_id', 'employment_type_id'], 'candidate_preferred_type_unique');
            });
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'profile_completion')) {
            User::query()->orderBy('id')->chunkById(100, function ($users): void {
                foreach ($users as $user) {
                    $user->unsetRelation('candidateProfile');
                    $user->forceFill([
                        'profile_completion' => $user->calculateProfileCompletion(),
                    ])->saveQuietly();
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_preferred_employment_type');
        Schema::dropIfExists('candidate_preferred_location');
        Schema::dropIfExists('candidate_employment_histories');
        Schema::dropIfExists('candidate_skill');
        Schema::dropIfExists('skills');

        if (! Schema::hasTable('candidate_profiles')) {
            return;
        }

        Schema::table('candidate_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('candidate_profiles', 'job_category_id')) {
                $table->dropConstrainedForeignId('job_category_id');
            }
            if (Schema::hasColumn('candidate_profiles', 'industry_id')) {
                $table->dropConstrainedForeignId('industry_id');
            }

            $columns = array_values(array_filter([
                Schema::hasColumn('candidate_profiles', 'current_designation') ? 'current_designation' : null,
                Schema::hasColumn('candidate_profiles', 'current_company') ? 'current_company' : null,
                Schema::hasColumn('candidate_profiles', 'current_salary') ? 'current_salary' : null,
                Schema::hasColumn('candidate_profiles', 'expected_salary') ? 'expected_salary' : null,
                Schema::hasColumn('candidate_profiles', 'notice_period') ? 'notice_period' : null,
                Schema::hasColumn('candidate_profiles', 'employment_preference') ? 'employment_preference' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
