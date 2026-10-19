<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('public_page_images')) {
            return;
        }

        Schema::create('public_page_images', function (Blueprint $table) {
            $table->id();
            $table->string('page_slug', 64);
            $table->string('slot_key', 64);
            $table->string('title');
            $table->string('alt_text');
            $table->boolean('is_active')->default(true);
            $table->foreignId('media_id')->constrained('media_assets')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['page_slug', 'slot_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('public_page_images');
    }
};
