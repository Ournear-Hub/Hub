<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LocalDevelopmentSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_local_admin_when_password_configured(): void
    {
        $this->app->detectEnvironment(fn () => 'local');

        config(['app.env' => 'local']);
        foreach ([
            'LOCAL_ADMIN_EMAIL' => 'admin@ournear.test',
            'LOCAL_ADMIN_PASSWORD' => 'local-test-password-123',
        ] as $key => $value) {
            putenv($key.'='.$value);
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }

        $this->artisan('db:seed', ['--class' => 'LocalDevelopmentSeeder'])
            ->assertSuccessful();

        $user = User::query()->where('email', 'admin@ournear.test')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->isAdmin());
        $this->assertTrue(Hash::check('local-test-password-123', $user->password));
    }
}
