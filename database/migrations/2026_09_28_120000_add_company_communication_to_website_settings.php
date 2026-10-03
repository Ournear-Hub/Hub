<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('website_settings')) {
            return;
        }

        Schema::table('website_settings', function (Blueprint $table): void {
            if (! Schema::hasColumn('website_settings', 'support_email')) {
                $table->string('support_email')->nullable()->after('office_email');
            }
            if (! Schema::hasColumn('website_settings', 'recruitment_email')) {
                $table->string('recruitment_email')->nullable()->after('support_email');
            }
            if (! Schema::hasColumn('website_settings', 'reply_to_email')) {
                $table->string('reply_to_email')->nullable()->after('recruitment_email');
            }
            if (! Schema::hasColumn('website_settings', 'primary_whatsapp')) {
                $table->string('primary_whatsapp', 20)->nullable()->after('office_phone');
            }
            if (! Schema::hasColumn('website_settings', 'recruitment_whatsapp')) {
                $table->string('recruitment_whatsapp', 20)->nullable()->after('primary_whatsapp');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('website_settings')) {
            return;
        }

        Schema::table('website_settings', function (Blueprint $table): void {
            foreach (['support_email', 'recruitment_email', 'reply_to_email', 'primary_whatsapp', 'recruitment_whatsapp'] as $column) {
                if (Schema::hasColumn('website_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
