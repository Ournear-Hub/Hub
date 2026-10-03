<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('internal_candidate_resumes')) {
            return;
        }

        Schema::create('internal_candidate_resumes', function (Blueprint $table): void {
            $table->id();
            $table->string('display_name');
            $table->string('source', 32);
            $table->text('notes')->nullable();
            $table->string('original_filename');
            $table->string('storage_disk', 64)->default('candidate_files');
            $table->string('storage_path');
            $table->string('mime_type')->nullable();
            $table->string('extension', 16)->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('source');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('internal_candidate_resumes');
    }
};
