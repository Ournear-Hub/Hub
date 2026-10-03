<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidate_profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('candidate_profiles', 'willing_to_relocate')) {
                $table->boolean('willing_to_relocate')->default(false)->after('employment_preference');
            }
            if (! Schema::hasColumn('candidate_profiles', 'availability')) {
                $table->string('availability', 255)->nullable()->after('willing_to_relocate');
            }
            if (! Schema::hasColumn('candidate_profiles', 'public_share')) {
                $table->json('public_share')->nullable()->after('availability');
            }
        });

        if (! Schema::hasTable('candidate_certifications')) {
            Schema::create('candidate_certifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('candidate_profile_id')->constrained('candidate_profiles')->cascadeOnDelete();
                $table->string('name');
                $table->string('issuer')->nullable();
                $table->date('issued_on')->nullable();
                $table->date('expires_on')->nullable();
                $table->foreignId('candidate_document_id')->nullable()->constrained('candidate_documents')->nullOnDelete();
                $table->timestamps();

                $table->index('candidate_profile_id');
            });
        }

        if (! Schema::hasTable('candidate_languages')) {
            Schema::create('candidate_languages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('candidate_profile_id')->constrained('candidate_profiles')->cascadeOnDelete();
                $table->string('name');
                $table->string('proficiency', 32)->nullable();
                $table->timestamps();

                $table->unique(['candidate_profile_id', 'name']);
            });
        }

        if (! Schema::hasTable('saved_searches')) {
            Schema::create('saved_searches', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->json('filters');
                $table->timestamps();

                $table->unique(['user_id', 'name']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_searches');
        Schema::dropIfExists('candidate_languages');
        Schema::dropIfExists('candidate_certifications');

        Schema::table('candidate_profiles', function (Blueprint $table) {
            $columns = array_values(array_filter([
                Schema::hasColumn('candidate_profiles', 'public_share') ? 'public_share' : null,
                Schema::hasColumn('candidate_profiles', 'availability') ? 'availability' : null,
                Schema::hasColumn('candidate_profiles', 'willing_to_relocate') ? 'willing_to_relocate' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
