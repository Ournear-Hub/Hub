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

        Schema::table('website_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('website_settings', 'mail_mailer')) {
                $table->string('mail_mailer', 32)->nullable()->after('notification_emails');
            }
            if (! Schema::hasColumn('website_settings', 'mail_enabled')) {
                $table->boolean('mail_enabled')->default(false)->after('mail_mailer');
            }
            if (! Schema::hasColumn('website_settings', 'smtp_host')) {
                $table->string('smtp_host')->nullable()->after('mail_enabled');
            }
            if (! Schema::hasColumn('website_settings', 'smtp_port')) {
                $table->unsignedSmallInteger('smtp_port')->nullable()->after('smtp_host');
            }
            if (! Schema::hasColumn('website_settings', 'smtp_encryption')) {
                $table->string('smtp_encryption', 16)->nullable()->after('smtp_port');
            }
            if (! Schema::hasColumn('website_settings', 'smtp_username')) {
                $table->string('smtp_username')->nullable()->after('smtp_encryption');
            }
            if (! Schema::hasColumn('website_settings', 'smtp_password')) {
                $table->text('smtp_password')->nullable()->after('smtp_username');
            }
            if (! Schema::hasColumn('website_settings', 'smtp_timeout')) {
                $table->unsignedSmallInteger('smtp_timeout')->nullable()->after('smtp_password');
            }
            if (! Schema::hasColumn('website_settings', 'mail_use_queue')) {
                $table->boolean('mail_use_queue')->default(true)->after('smtp_timeout');
            }
            if (! Schema::hasColumn('website_settings', 'mail_last_diagnostic')) {
                $table->json('mail_last_diagnostic')->nullable()->after('mail_use_queue');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('website_settings')) {
            return;
        }

        Schema::table('website_settings', function (Blueprint $table) {
            $columns = [
                'mail_mailer',
                'mail_enabled',
                'smtp_host',
                'smtp_port',
                'smtp_encryption',
                'smtp_username',
                'smtp_password',
                'smtp_timeout',
                'mail_use_queue',
                'mail_last_diagnostic',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('website_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
