<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('candidate_employment_histories')) {
            return;
        }

        Schema::table('candidate_employment_histories', function (Blueprint $table) {
            if (! Schema::hasColumn('candidate_employment_histories', 'is_internship')) {
                $table->boolean('is_internship')->default(false)->after('is_current');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('candidate_employment_histories')) {
            return;
        }

        Schema::table('candidate_employment_histories', function (Blueprint $table) {
            if (Schema::hasColumn('candidate_employment_histories', 'is_internship')) {
                $table->dropColumn('is_internship');
            }
        });
    }
};
