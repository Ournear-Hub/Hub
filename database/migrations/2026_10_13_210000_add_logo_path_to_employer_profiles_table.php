<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employer_profiles', function (Blueprint $table) {
            $table->string('logo_path', 512)->nullable()->after('public_share');
        });

        if (! Schema::hasColumn('employer_profiles', 'logo_path')) {
            return;
        }

        $rows = DB::table('employer_profiles')
            ->join('users', 'users.id', '=', 'employer_profiles.user_id')
            ->where('users.role', 'employer')
            ->whereNotNull('users.photo_path')
            ->whereNull('employer_profiles.logo_path')
            ->select(['employer_profiles.id as profile_id', 'users.photo_path'])
            ->orderBy('employer_profiles.id')
            ->get();

        foreach ($rows as $row) {
            $source = (string) $row->photo_path;
            $logoPath = null;

            if ($source !== '' && Storage::disk('public')->exists($source)) {
                $extension = pathinfo($source, PATHINFO_EXTENSION) ?: 'jpg';
                $destination = 'employers/'.Str::uuid()->toString().'.'.strtolower($extension);
                if (Storage::disk('public')->copy($source, $destination)) {
                    $logoPath = $destination;
                }
            }

            if ($logoPath !== null) {
                DB::table('employer_profiles')
                    ->where('id', $row->profile_id)
                    ->update(['logo_path' => $logoPath]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('employer_profiles', function (Blueprint $table) {
            $table->dropColumn('logo_path');
        });
    }
};
