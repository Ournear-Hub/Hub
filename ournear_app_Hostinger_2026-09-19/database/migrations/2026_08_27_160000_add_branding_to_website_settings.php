<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('website_settings', function (Blueprint $table) {
            $table->string('website_name')->default('OurNear Talent')->after('company_name');
            $table->string('logo_path')->nullable()->after('website_name');
            $table->string('icon_path')->nullable()->after('logo_path');
            $table->string('favicon_path')->nullable()->after('icon_path');
        });
    }

    public function down(): void
    {
        Schema::table('website_settings', function (Blueprint $table) {
            $table->dropColumn(['website_name', 'logo_path', 'icon_path', 'favicon_path']);
        });
    }
};
