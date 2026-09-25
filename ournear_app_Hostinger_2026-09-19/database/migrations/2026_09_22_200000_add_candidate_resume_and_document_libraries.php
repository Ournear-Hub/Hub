<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('candidate_resumes')) {
            Schema::create('candidate_resumes', function (Blueprint $table) {
                $table->id();
                $table->string('business_id')->unique();
                $table->foreignId('candidate_profile_id')->constrained('candidate_profiles')->cascadeOnDelete();
                $table->string('title');
                $table->string('original_filename');
                $table->string('storage_disk', 64);
                $table->string('storage_path');
                $table->string('mime_type', 128)->nullable();
                $table->string('extension', 16);
                $table->unsignedBigInteger('file_size');
                $table->boolean('is_primary')->default(false);
                $table->unsignedBigInteger('primary_owner_id')->nullable();
                $table->string('visibility', 32)->default('private');
                $table->string('status', 32)->default('active');
                $table->timestamps();

                $table->index('candidate_profile_id');
                $table->unique('primary_owner_id');
            });
        }

        if (! Schema::hasTable('candidate_documents')) {
            Schema::create('candidate_documents', function (Blueprint $table) {
                $table->id();
                $table->string('business_id')->unique();
                $table->foreignId('candidate_profile_id')->constrained('candidate_profiles')->cascadeOnDelete();
                $table->string('document_type', 32);
                $table->string('title');
                $table->string('original_filename');
                $table->string('storage_disk', 64);
                $table->string('storage_path');
                $table->string('mime_type', 128)->nullable();
                $table->string('extension', 16);
                $table->unsignedBigInteger('file_size');
                $table->date('issue_date')->nullable();
                $table->date('expiry_date')->nullable();
                $table->string('visibility', 32)->default('private');
                $table->string('status', 32)->default('active');
                $table->string('verification_status', 32)->default('unverified');
                $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('verified_at')->nullable();
                $table->timestamps();

                $table->index(['candidate_profile_id', 'document_type'], 'candidate_documents_type_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_documents');
        Schema::dropIfExists('candidate_resumes');
    }
};
