<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('employer_documents')) {
            return;
        }

        Schema::create('employer_documents', function (Blueprint $table) {
            $table->id();
            $table->string('business_id')->unique();
            $table->foreignId('employer_profile_id')->constrained('employer_profiles')->cascadeOnDelete();
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
            $table->string('status', 32)->default('active');
            $table->string('verification_status', 32)->default('unverified');
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();

            $table->index(['employer_profile_id', 'document_type'], 'employer_documents_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employer_documents');
    }
};
