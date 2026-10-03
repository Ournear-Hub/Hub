<?php

namespace Database\Seeders;

use App\Models\Job;
use Illuminate\Database\Seeder;

/**
 * DEVELOPMENT / DEMO DATA ONLY.
 *
 * Does not run with `php artisan db:seed` unless called explicitly:
 * php artisan db:seed --class=DemoJobListingSeeder
 *
 * Do not run against production.
 */
class DemoJobListingSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->warn('DemoJobListingSeeder skipped: production environment.');

            return;
        }

        Job::factory()->featured()->create([
            'title' => 'Warehouse Operative',
            'project' => 'OurNear Talent client site',
            'industry' => 'Logistics',
            'category' => 'Temporary',
            'location' => 'Birmingham',
            'employment_type' => 'Full-time',
            'experience' => '0-1 years',
            'salary' => 'Competitive',
        ]);

        Job::factory()->featured()->create([
            'title' => 'Facilities Supervisor',
            'project' => 'OurNear Talent client site',
            'industry' => 'Facilities',
            'category' => 'Permanent',
            'location' => 'Manchester',
            'employment_type' => 'Full-time',
            'experience' => '3-5 years',
            'salary' => '£32,000 - £36,000',
        ]);

        Job::factory()->count(4)->create();
        Job::factory()->draft()->create(['title' => 'Draft role (not public)']);
        Job::factory()->closed()->create(['title' => 'Closed role (not public)']);
    }
}
