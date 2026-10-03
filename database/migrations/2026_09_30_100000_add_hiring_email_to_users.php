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

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'hiring_email')) {
                $table->string('hiring_email')->nullable()->after('email');
            }
            if (! Schema::hasColumn('users', 'hiring_email_verified_at')) {
                $table->timestamp('hiring_email_verified_at')->nullable()->after('hiring_email');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $columns = array_values(array_filter([
                Schema::hasColumn('users', 'hiring_email_verified_at') ? 'hiring_email_verified_at' : null,
                Schema::hasColumn('users', 'hiring_email') ? 'hiring_email' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
