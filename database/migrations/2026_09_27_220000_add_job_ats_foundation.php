<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_listings', function (Blueprint $table) {
            if (! Schema::hasColumn('job_listings', 'urgent')) {
                $table->boolean('urgent')->default(false)->after('featured');
            }
            if (! Schema::hasColumn('job_listings', 'joining_date')) {
                $table->date('joining_date')->nullable()->after('closing_date');
            }
            if (! Schema::hasColumn('job_listings', 'site')) {
                $table->string('site')->nullable()->after('joining_date');
            }
            if (! Schema::hasColumn('job_listings', 'accommodation')) {
                $table->text('accommodation')->nullable()->after('site');
            }
        });
    }

    public function down(): void
    {
        Schema::table('job_listings', function (Blueprint $table) {
            foreach (['urgent', 'joining_date', 'site', 'accommodation'] as $column) {
                if (Schema::hasColumn('job_listings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
