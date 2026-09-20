<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->json('internal_notes')->nullable()->after('status');
        });

        Schema::table('website_settings', function (Blueprint $table) {
            $table->json('notification_emails')->nullable()->after('default_meta_description');
            $table->string('lock_key', 16)->nullable()->after('notification_emails');
        });

        DB::table('website_settings')->whereNull('lock_key')->update(['lock_key' => 'default']);

        Schema::table('website_settings', function (Blueprint $table) {
            $table->unique('lock_key');
        });
    }

    public function down(): void
    {
        Schema::table('website_settings', function (Blueprint $table) {
            $table->dropUnique(['lock_key']);
            $table->dropColumn(['notification_emails', 'lock_key']);
        });

        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn('internal_notes');
        });
    }
};
