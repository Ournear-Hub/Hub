<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 32)->default('admin')->after('email');
            $table->index('role');
        });

        DB::table('users')->update(['role' => 'admin']);

        Schema::create('candidate_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('mobile')->nullable();
            $table->string('location')->nullable();
            $table->string('experience')->nullable();
            $table->string('qualification')->nullable();
            $table->timestamps();
        });

        Schema::create('employer_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('company_name')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('mobile_number')->nullable();
            $table->string('industry')->nullable();
            $table->timestamps();
        });

        Schema::table('applications', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->nullable()
                ->after('job_id')
                ->constrained()
                ->nullOnDelete();
        });

        Schema::table('employer_enquiries', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->nullable()
                ->after('id')
                ->constrained()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('employer_enquiries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });

        Schema::table('applications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });

        Schema::dropIfExists('employer_profiles');
        Schema::dropIfExists('candidate_profiles');

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropColumn('role');
        });
    }
};
