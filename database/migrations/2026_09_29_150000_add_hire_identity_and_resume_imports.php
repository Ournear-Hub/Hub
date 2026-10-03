<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'person_number')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->unsignedInteger('person_number')->nullable()->unique()->after('id');
            });

            $number = 1;
            foreach (DB::table('users')->orderBy('id')->pluck('id') as $id) {
                DB::table('users')->where('id', $id)->update(['person_number' => $number]);
                $number++;
            }
        }

        if (Schema::hasTable('website_settings') && ! Schema::hasColumn('website_settings', 'brand_mark')) {
            Schema::table('website_settings', function (Blueprint $table): void {
                $table->string('brand_mark', 8)->default('^')->after('website_name');
                $table->string('wordmark_path')->nullable()->after('logo_path');
            });
        }

        if (! Schema::hasTable('resume_import_batches')) {
            Schema::create('resume_import_batches', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->unsignedInteger('total_files')->default(0);
                $table->unsignedInteger('successful')->default(0);
                $table->unsignedInteger('matched')->default(0);
                $table->unsignedInteger('needs_review')->default(0);
                $table->unsignedInteger('failed')->default(0);
                $table->unsignedInteger('emails_queued')->default(0);
                $table->string('status', 32)->default('completed');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('resume_import_items')) {
            Schema::create('resume_import_items', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('resume_import_batch_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('original_filename');
                $table->string('content_hash', 64)->nullable()->index();
                $table->string('status', 32);
                $table->string('email_status', 32)->default('not_applicable');
                $table->string('message')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('resume_import_items');
        Schema::dropIfExists('resume_import_batches');

        if (Schema::hasColumn('website_settings', 'brand_mark')) {
            Schema::table('website_settings', function (Blueprint $table): void {
                $table->dropColumn(['brand_mark', 'wordmark_path']);
            });
        }

        if (Schema::hasColumn('users', 'person_number')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropUnique(['person_number']);
                $table->dropColumn('person_number');
            });
        }
    }
};
