<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\AuthorizationBootstrapper;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        app(AuthorizationBootstrapper::class)->run();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->call(WebsiteContentSeeder::class);
    }
}
