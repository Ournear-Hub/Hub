<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('showcase_companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('media_asset_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('show_on_home')->default(false);
            $table->boolean('show_on_talent_pool')->default(false);
            $table->boolean('show_on_staffing')->default(false);
            $table->timestamps();
        });

        Schema::create('homepage_statistics', function (Blueprint $table) {
            $table->id();
            $table->string('lock_key')->default('default')->unique();
            $table->unsignedInteger('registered_companies')->default(0);
            $table->unsignedInteger('talent_pool_companies')->default(0);
            $table->unsignedInteger('staffing_companies')->default(0);
            $table->unsignedInteger('registered_users')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('homepage_statistics');
        Schema::dropIfExists('showcase_companies');
    }
};
