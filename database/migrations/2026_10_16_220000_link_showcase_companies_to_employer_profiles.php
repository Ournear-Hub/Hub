<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('showcase_companies') || Schema::hasColumn('showcase_companies', 'employer_profile_id')) {
            return;
        }

        Schema::table('showcase_companies', function (Blueprint $table) {
            $table->foreignId('employer_profile_id')
                ->nullable()
                ->after('name')
                ->constrained('employer_profiles')
                ->nullOnDelete();
            $table->unique('employer_profile_id', 'showcase_companies_employer_unique');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('showcase_companies') || ! Schema::hasColumn('showcase_companies', 'employer_profile_id')) {
            return;
        }

        Schema::table('showcase_companies', function (Blueprint $table) {
            $table->dropUnique('showcase_companies_employer_unique');
            $table->dropConstrainedForeignId('employer_profile_id');
        });
    }
};
