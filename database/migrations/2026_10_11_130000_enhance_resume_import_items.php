<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resume_import_items', function (Blueprint $table) {
            $table->string('extracted_name')->nullable()->after('original_filename');
            $table->string('extracted_email')->nullable()->after('extracted_name');
            $table->string('storage_path')->nullable()->after('extracted_email');
            $table->string('file_extension', 8)->nullable()->after('storage_path');
        });
    }

    public function down(): void
    {
        Schema::table('resume_import_items', function (Blueprint $table) {
            $table->dropColumn(['extracted_name', 'extracted_email', 'storage_path', 'file_extension']);
        });
    }
};
