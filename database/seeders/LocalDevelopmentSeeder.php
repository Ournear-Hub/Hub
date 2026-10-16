<?php

namespace Database\Seeders;

use App\Enums\StaffRoleSlug;
use App\Enums\UserRole;
use App\Models\Role;
use App\Models\User;
use App\Services\AuthorizationBootstrapper;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * LOCAL DEVELOPMENT ONLY.
 *
 * Run explicitly after migrate on a local database:
 *   php artisan db:seed --class=LocalDevelopmentSeeder
 *
 * Requires APP_ENV=local and LOCAL_ADMIN_PASSWORD in .env (never commit .env).
 */
class LocalDevelopmentSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            $this->command?->warn('LocalDevelopmentSeeder skipped: APP_ENV is not local.');

            return;
        }

        app(AuthorizationBootstrapper::class)->run();

        $email = strtolower(trim((string) env('LOCAL_ADMIN_EMAIL', 'admin@ournear.test')));
        $password = env('LOCAL_ADMIN_PASSWORD');

        if (! is_string($password) || trim($password) === '') {
            $this->command?->error('Set LOCAL_ADMIN_PASSWORD in .env before running LocalDevelopmentSeeder.');

            return;
        }

        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Local Admin',
                'role' => UserRole::Admin,
                'is_active' => true,
                'email_verified_at' => now(),
                'password' => Hash::make($password),
                'must_change_password' => false,
            ],
        );

        $superAdmin = Role::query()->where('slug', StaffRoleSlug::SuperAdmin->value)->first();
        if ($superAdmin) {
            $user->roles()->sync([$superAdmin->id]);
            $user->forgetPermissionCache();
        }

        $this->call(WebsiteContentSeeder::class);
        $this->call(DemoJobListingSeeder::class);

        $this->command?->info('Local admin ready: '.$email.' (password from LOCAL_ADMIN_PASSWORD in .env).');
    }
}
