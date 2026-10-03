<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'bio')) {
                $table->text('bio')->nullable()->after('location');
            }
        });

        $now = now();

        foreach (DB::table('users')->orderBy('id')->get() as $row) {
            $score = 0;

            if (filled($row->name ?? null)) {
                $score += 15;
            }
            if (filled($row->email ?? null)) {
                $score += 15;
            }
            if (filled($row->email_verified_at ?? null)) {
                $score += 10;
            }
            if (filled($row->mobile ?? null)) {
                $score += 10;
            }
            if (filled($row->photo_path ?? null)) {
                $score += 10;
            }
            if (filled($row->location ?? null)) {
                $score += 10;
            }
            if (filled($row->username ?? null)) {
                $score += 10;
            }
            if (filled($row->bio ?? null)) {
                $score += 10;
            }

            $hasProfile = (Schema::hasTable('candidate_profiles') && DB::table('candidate_profiles')->where('user_id', $row->id)->exists())
                || (Schema::hasTable('employer_profiles') && DB::table('employer_profiles')->where('user_id', $row->id)->exists())
                || (Schema::hasTable('admin_profiles') && DB::table('admin_profiles')->where('user_id', $row->id)->exists());

            if ($hasProfile) {
                $score += 10;
            }

            DB::table('users')->where('id', $row->id)->update([
                'profile_completion' => min(100, $score),
                'updated_at' => $row->updated_at ?? $now,
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'bio')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('bio');
        });
    }
};
