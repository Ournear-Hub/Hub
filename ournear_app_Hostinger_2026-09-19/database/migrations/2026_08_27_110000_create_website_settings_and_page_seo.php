<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_settings', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('office_phone')->nullable();
            $table->string('office_email')->nullable();
            $table->string('website_url')->nullable();
            $table->string('office_address', 1000)->nullable();
            $table->string('copyright_text')->nullable();
            $table->string('default_seo_title')->nullable();
            $table->string('default_meta_description', 500)->nullable();
            $table->timestamps();
        });

        Schema::table('website_pages', function (Blueprint $table) {
            $table->string('seo_title')->nullable()->after('title');
            $table->string('meta_description', 500)->nullable()->after('seo_title');
        });
    }

    public function down(): void
    {
        Schema::table('website_pages', function (Blueprint $table) {
            $table->dropColumn(['seo_title', 'meta_description']);
        });

        Schema::dropIfExists('website_settings');
    }
};
