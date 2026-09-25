<?php

use App\Enums\AccountStatus;
use App\Enums\BusinessIdPrefix;
use App\Enums\CommunicationChannel;
use App\Enums\PreferredLanguage;
use App\Enums\UserActivityType;
use App\Enums\UserRole;
use App\Enums\UserSegmentSlug;
use App\Services\AuthorizationBootstrapper;
use App\Services\MasterData\BusinessIdGenerator;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('brands')) {
            Schema::create('brands', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->boolean('is_primary')->default(false);
                $table->string('logo_path')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('brands') && DB::table('brands')->where('slug', 'ournear')->doesntExist()) {
            $now = now();
            DB::table('brands')->insert([
                'name' => 'OurNear',
                'slug' => 'ournear',
                'is_primary' => true,
                'logo_path' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $primaryBrandId = DB::table('brands')->where('is_primary', true)->value('id');

        Schema::table('users', function (Blueprint $table) use ($primaryBrandId) {
            if (! Schema::hasColumn('users', 'business_id')) {
                $table->string('business_id', 32)->nullable()->unique();
            }
            if (! Schema::hasColumn('users', 'username')) {
                $table->string('username', 64)->nullable()->unique();
            }
            if (! Schema::hasColumn('users', 'photo_path')) {
                $table->string('photo_path')->nullable();
            }
            if (! Schema::hasColumn('users', 'mobile')) {
                $table->string('mobile', 32)->nullable();
            }
            if (! Schema::hasColumn('users', 'mobile_verified_at')) {
                $table->timestamp('mobile_verified_at')->nullable();
            }
            if (! Schema::hasColumn('users', 'preferred_language')) {
                $table->string('preferred_language', 8)->default(PreferredLanguage::English->value);
            }
            if (! Schema::hasColumn('users', 'location')) {
                $table->string('location')->nullable();
            }
            if (! Schema::hasColumn('users', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable();
            }
            if (! Schema::hasColumn('users', 'account_status')) {
                $table->string('account_status', 32)->default(AccountStatus::Active->value)->index();
            }
            if (! Schema::hasColumn('users', 'profile_completion')) {
                $table->unsignedTinyInteger('profile_completion')->default(0);
            }
            if (! Schema::hasColumn('users', 'must_change_password')) {
                $table->boolean('must_change_password')->default(false);
            }
            if (! Schema::hasColumn('users', 'password_changed_at')) {
                $table->timestamp('password_changed_at')->nullable();
            }
            if (! Schema::hasColumn('users', 'brand_id') && Schema::hasTable('brands')) {
                $table->foreignId('brand_id')->nullable()->constrained('brands')->nullOnDelete();
            }
        });

        if (! Schema::hasTable('admin_profiles')) {
            Schema::create('admin_profiles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
                $table->string('department')->nullable();
                $table->string('designation')->nullable();
                $table->date('joined_at')->nullable();
                $table->text('professional_details')->nullable();
                $table->text('qualifications')->nullable();
                $table->text('skills')->nullable();
                $table->text('employment_information')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('user_communication_preferences')) {
            Schema::create('user_communication_preferences', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('channel', 32);
                $table->boolean('opted_in')->default(false);
                $table->timestamp('consented_at')->nullable();
                $table->string('source')->nullable();
                $table->timestamp('opted_out_at')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'channel']);
            });
        }

        if (! Schema::hasTable('user_activities')) {
            Schema::create('user_activities', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('type', 64);
                $table->string('description');
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'created_at']);
                $table->index(['type', 'created_at']);
            });
        }

        if (! Schema::hasTable('user_segments')) {
            Schema::create('user_segments', function (Blueprint $table) {
                $table->id();
                $table->string('slug')->unique();
                $table->string('name');
                $table->string('description')->nullable();
                $table->boolean('is_navigation')->default(false);
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        $now = now();
        if (Schema::hasTable('business_id_sequences')) {
            DB::table('business_id_sequences')->insertOrIgnore([
                'prefix' => BusinessIdPrefix::User->value,
                'next_number' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $generator = app(BusinessIdGenerator::class);
        $usedUsernames = [];

        foreach (DB::table('users')->orderBy('id')->get() as $row) {
            $updates = [];

            if (blank($row->business_id ?? null)) {
                $updates['business_id'] = $generator->next(BusinessIdPrefix::User);
            }

            if (blank($row->username ?? null)) {
                $base = Str::slug((string) Str::before((string) $row->email, '@')) ?: 'user';
                $candidate = $base;
                $i = 1;
                while (isset($usedUsernames[$candidate]) || DB::table('users')->where('username', $candidate)->where('id', '!=', $row->id)->exists()) {
                    $candidate = $base.$i;
                    $i++;
                }
                $usedUsernames[$candidate] = true;
                $updates['username'] = $candidate;
            } else {
                $usedUsernames[(string) $row->username] = true;
            }

            $isActive = (bool) ($row->is_active ?? true);
            $status = $row->account_status ?? null;
            if (blank($status)) {
                $updates['account_status'] = $isActive ? AccountStatus::Active->value : AccountStatus::Inactive->value;
            }

            if (blank($row->preferred_language ?? null)) {
                $updates['preferred_language'] = PreferredLanguage::English->value;
            }

            if ($primaryBrandId && blank($row->brand_id ?? null)) {
                $updates['brand_id'] = $primaryBrandId;
            }

            $candidate = Schema::hasTable('candidate_profiles')
                ? DB::table('candidate_profiles')->where('user_id', $row->id)->first()
                : null;
            $employer = Schema::hasTable('employer_profiles')
                ? DB::table('employer_profiles')->where('user_id', $row->id)->first()
                : null;

            if (blank($row->mobile ?? null)) {
                $mobile = $candidate->mobile ?? $employer->mobile_number ?? null;
                if (filled($mobile)) {
                    $updates['mobile'] = $mobile;
                }
            }

            if (blank($row->location ?? null) && filled($candidate->location ?? null)) {
                $updates['location'] = $candidate->location;
            }

            if (blank($row->last_login_at ?? null) && Schema::hasTable('login_logs')) {
                $lastLogin = DB::table('login_logs')
                    ->where('user_id', $row->id)
                    ->where('event', 'login_success')
                    ->orderByDesc('id')
                    ->value('created_at');
                if ($lastLogin) {
                    $updates['last_login_at'] = $lastLogin;
                }
            }

            $completion = 0;
            if (filled($row->name)) {
                $completion += 15;
            }
            if (filled($row->email)) {
                $completion += 15;
            }
            if (filled($row->email_verified_at ?? null)) {
                $completion += 10;
            }
            $mobileValue = $updates['mobile'] ?? $row->mobile ?? null;
            if (filled($mobileValue)) {
                $completion += 10;
            }
            $locationValue = $updates['location'] ?? $row->location ?? null;
            if (filled($locationValue)) {
                $completion += 10;
            }
            if (filled($updates['username'] ?? $row->username ?? null)) {
                $completion += 10;
            }
            if ($candidate || $employer) {
                $completion += 10;
            }
            $updates['profile_completion'] = min(100, $completion);

            if ($updates !== []) {
                $updates['updated_at'] = $now;
                DB::table('users')->where('id', $row->id)->update($updates);
            }

            if (Schema::hasTable('user_communication_preferences')) {
                foreach (CommunicationChannel::cases() as $channel) {
                    $exists = DB::table('user_communication_preferences')
                        ->where('user_id', $row->id)
                        ->where('channel', $channel->value)
                        ->exists();
                    if ($exists) {
                        continue;
                    }

                    $optedIn = $channel->defaultOptIn();
                    DB::table('user_communication_preferences')->insert([
                        'user_id' => $row->id,
                        'channel' => $channel->value,
                        'opted_in' => $optedIn,
                        'consented_at' => $optedIn ? $row->created_at : null,
                        'source' => 'system_default',
                        'opted_out_at' => $optedIn ? null : $now,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }

            if (
                Schema::hasTable('admin_profiles')
                && ($row->role ?? null) === UserRole::Admin->value
                && DB::table('admin_profiles')->where('user_id', $row->id)->doesntExist()
            ) {
                DB::table('admin_profiles')->insert([
                    'user_id' => $row->id,
                    'joined_at' => $row->created_at,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            if (
                Schema::hasTable('user_activities')
                && DB::table('user_activities')->where('user_id', $row->id)->where('type', UserActivityType::Registered->value)->doesntExist()
            ) {
                DB::table('user_activities')->insert([
                    'user_id' => $row->id,
                    'actor_id' => null,
                    'type' => UserActivityType::Registered->value,
                    'description' => 'Registered on OurNear.',
                    'metadata' => json_encode(['source' => 'backfill']),
                    'created_at' => $row->created_at ?? $now,
                    'updated_at' => $now,
                ]);
            }
        }

        if (Schema::hasTable('user_segments')) {
            foreach (UserSegmentSlug::cases() as $index => $segment) {
                DB::table('user_segments')->updateOrInsert(
                    ['slug' => $segment->value],
                    [
                        'name' => $segment->label(),
                        'description' => $segment->description(),
                        'is_navigation' => in_array($segment, UserSegmentSlug::navigation(), true),
                        'sort_order' => $index,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                );
            }
        }

        app(AuthorizationBootstrapper::class)->run();
    }

    public function down(): void
    {
        Schema::dropIfExists('user_segments');
        Schema::dropIfExists('user_activities');
        Schema::dropIfExists('user_communication_preferences');
        Schema::dropIfExists('admin_profiles');

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'brand_id')) {
                $table->dropConstrainedForeignId('brand_id');
            }
            foreach ([
                'business_id',
                'username',
                'photo_path',
                'mobile',
                'mobile_verified_at',
                'preferred_language',
                'location',
                'last_login_at',
                'account_status',
                'profile_completion',
                'must_change_password',
                'password_changed_at',
            ] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::dropIfExists('brands');
    }
};
