<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employer_profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('employer_profiles', 'benefits')) {
                $table->json('benefits')->nullable()->after('company_details');
            }
            if (! Schema::hasColumn('employer_profiles', 'socials')) {
                $table->json('socials')->nullable()->after('benefits');
            }
            if (! Schema::hasColumn('employer_profiles', 'public_share')) {
                $table->json('public_share')->nullable()->after('socials');
            }
            if (! Schema::hasColumn('employer_profiles', 'posting_restricted_at')) {
                $table->timestamp('posting_restricted_at')->nullable()->after('job_posting_approved_by');
            }
        });

        if (! Schema::hasTable('employer_location')) {
            Schema::create('employer_location', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employer_profile_id')->constrained('employer_profiles')->cascadeOnDelete();
                $table->foreignId('location_id')->constrained('locations')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['employer_profile_id', 'location_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('employer_location');

        Schema::table('employer_profiles', function (Blueprint $table) {
            $columns = array_values(array_filter([
                Schema::hasColumn('employer_profiles', 'posting_restricted_at') ? 'posting_restricted_at' : null,
                Schema::hasColumn('employer_profiles', 'public_share') ? 'public_share' : null,
                Schema::hasColumn('employer_profiles', 'socials') ? 'socials' : null,
                Schema::hasColumn('employer_profiles', 'benefits') ? 'benefits' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
