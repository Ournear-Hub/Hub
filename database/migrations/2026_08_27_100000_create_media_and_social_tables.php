<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_assets', function (Blueprint $table) {
            $table->id();
            $table->string('disk')->default('public');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type');
            $table->string('extension', 16);
            $table->unsignedBigInteger('size_bytes');
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->timestamps();

            $table->index('created_at');
        });

        Schema::create('social_links', function (Blueprint $table) {
            $table->id();
            $table->string('platform')->unique();
            $table->string('label');
            $table->string('url')->nullable();
            $table->boolean('is_enabled')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('website_sections', function (Blueprint $table) {
            $table->foreignId('media_id')->nullable()->after('image_path')->constrained('media_assets')->nullOnDelete();
        });

        Schema::table('content_items', function (Blueprint $table) {
            $table->foreignId('media_id')->nullable()->after('image_path')->constrained('media_assets')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('content_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('media_id');
        });

        Schema::table('website_sections', function (Blueprint $table) {
            $table->dropConstrainedForeignId('media_id');
        });

        Schema::dropIfExists('social_links');
        Schema::dropIfExists('media_assets');
    }
};
