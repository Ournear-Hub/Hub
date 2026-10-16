<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('placements', function (Blueprint $table) {
            $table->dropForeign(['employer_submission_id']);
        });

        Schema::table('placements', function (Blueprint $table) {
            $table->unsignedBigInteger('employer_submission_id')->nullable()->change();
            $table->foreign('employer_submission_id')
                ->references('id')
                ->on('employer_submissions')
                ->restrictOnDelete();
            $table->unique('application_id');
        });

        if (Schema::hasColumn('employees', 'employer_submission_id')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->dropForeign(['employer_submission_id']);
            });
            Schema::table('employees', function (Blueprint $table) {
                $table->unsignedBigInteger('employer_submission_id')->nullable()->change();
                $table->foreign('employer_submission_id')
                    ->references('id')
                    ->on('employer_submissions')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('placements', function (Blueprint $table) {
            $table->dropUnique(['application_id']);
            $table->dropForeign(['employer_submission_id']);
        });

        Schema::table('placements', function (Blueprint $table) {
            $table->unsignedBigInteger('employer_submission_id')->nullable(false)->change();
            $table->foreign('employer_submission_id')
                ->references('id')
                ->on('employer_submissions')
                ->restrictOnDelete();
            $table->unique('employer_submission_id');
        });
    }
};
