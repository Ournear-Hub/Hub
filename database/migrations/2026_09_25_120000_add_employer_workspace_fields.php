<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employer_profiles', function (Blueprint $table) {
            $table->text('about_company')->nullable();
            $table->string('company_size', 100)->nullable();
            $table->string('company_website', 2048)->nullable();
            $table->text('company_details')->nullable();
            $table->timestamp('trial_job_used_at')->nullable();
            $table->timestamp('job_posting_approved_at')->nullable();
            $table->foreignId('job_posting_approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
        });

        Schema::table('job_listings', function (Blueprint $table) {
            $table->string('posting_tier', 32)->default('basic')->index();
            $table->timestamp('trial_expires_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('job_listings', function (Blueprint $table) {
            $table->dropIndex(['trial_expires_at']);
            $table->dropIndex(['posting_tier']);
            $table->dropColumn(['posting_tier', 'trial_expires_at']);
        });

        Schema::table('employer_profiles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('job_posting_approved_by');
            $table->dropColumn([
                'about_company',
                'company_size',
                'company_website',
                'company_details',
                'trial_job_used_at',
                'job_posting_approved_at',
            ]);
        });
    }
};
