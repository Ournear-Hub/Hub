<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'whatsapp_number')) {
                $table->string('whatsapp_number', 32)->nullable()->after('mobile_verified_at');
            }
            if (! Schema::hasColumn('users', 'whatsapp_verified_at')) {
                $table->timestamp('whatsapp_verified_at')->nullable()->after('whatsapp_number');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'whatsapp_verified_at')) {
                $table->dropColumn('whatsapp_verified_at');
            }
            if (Schema::hasColumn('users', 'whatsapp_number')) {
                $table->dropColumn('whatsapp_number');
            }
        });
    }
};
