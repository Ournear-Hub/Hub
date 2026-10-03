<?php

use App\Enums\RegistrationSource;
use App\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'registration_source')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('registration_source', 32)->nullable()->after('account_status');
                $table->index('registration_source');
            });
        }

        if (! Schema::hasColumn('users', 'last_activity_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('last_activity_at')->nullable()->after('last_login_at');
                $table->index('last_activity_at');
            });
        }

        if (Schema::hasColumn('users', 'registration_source')) {
            DB::table('users')
                ->where('role', UserRole::Admin->value)
                ->whereNull('registration_source')
                ->update(['registration_source' => RegistrationSource::Admin->value]);

            DB::table('users')
                ->whereIn('role', [UserRole::Candidate->value, UserRole::Employer->value])
                ->whereNull('registration_source')
                ->update(['registration_source' => RegistrationSource::Website->value]);
        }

        if (Schema::hasColumn('users', 'last_activity_at') && Schema::hasColumn('users', 'last_login_at')) {
            DB::table('users')
                ->whereNull('last_activity_at')
                ->whereNotNull('last_login_at')
                ->update(['last_activity_at' => DB::raw('last_login_at')]);
        }

        if (! Schema::hasTable('tags')) {
            Schema::create('tags', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique('name');
            });
        }

        if (! Schema::hasTable('taggables')) {
            Schema::create('taggables', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
                $table->morphs('taggable');
                $table->timestamps();

                $table->unique(['tag_id', 'taggable_id', 'taggable_type'], 'taggables_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('taggables');
        Schema::dropIfExists('tags');

        if (Schema::hasColumn('users', 'registration_source')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex(['registration_source']);
                $table->dropColumn('registration_source');
            });
        }

        if (Schema::hasColumn('users', 'last_activity_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex(['last_activity_at']);
                $table->dropColumn('last_activity_at');
            });
        }
    }
};
