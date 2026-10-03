<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->timestamps();
        });

        Schema::create('website_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained('website_pages')->cascadeOnDelete();
            $table->string('section_key');
            $table->string('label');
            $table->string('heading')->nullable();
            $table->string('subtitle')->nullable();
            $table->text('body')->nullable();
            $table->string('primary_button_label')->nullable();
            $table->string('secondary_button_label')->nullable();
            $table->string('image_path')->nullable();
            $table->json('extra')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['page_id', 'section_key']);
            $table->index(['page_id', 'sort_order']);
        });

        Schema::create('content_items', function (Blueprint $table) {
            $table->id();
            $table->string('group_key');
            $table->string('type');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->string('image_path')->nullable();
            $table->json('bullets')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['group_key', 'sort_order']);
            $table->index('type');
        });

        Schema::create('page_section_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained('website_sections')->cascadeOnDelete();
            $table->foreignId('content_item_id')->constrained('content_items')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['section_id', 'content_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_section_items');
        Schema::dropIfExists('content_items');
        Schema::dropIfExists('website_sections');
        Schema::dropIfExists('website_pages');
    }
};
