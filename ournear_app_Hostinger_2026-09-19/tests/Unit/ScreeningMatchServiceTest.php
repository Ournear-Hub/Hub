<?php

namespace Tests\Unit;

use App\Models\Application;
use App\Models\Job;
use App\Services\ScreeningMatchService;
use Tests\TestCase;

class ScreeningMatchServiceTest extends TestCase
{
    public function test_matching_location_and_experience_produce_a_transparent_score(): void
    {
        $job = Job::factory()->create([
            'location' => 'Manchester',
            'experience' => '1-3 years',
        ]);
        $application = Application::factory()->create([
            'job_id' => $job->id,
            'current_location' => 'Manchester',
            'experience' => '1-3 years',
        ]);

        $result = app(ScreeningMatchService::class)->evaluate($application);

        $this->assertSame('scored', $result['state']);
        $this->assertSame(100, $result['percent']);
        $this->assertSame('100%', $result['label']);
        $this->assertSame(2, $result['comparable_count']);
        $this->assertTrue(collect($result['factors'])->firstWhere('key', 'location')['contributed']);
        $this->assertTrue(collect($result['factors'])->firstWhere('key', 'skills')['state'] === 'unavailable');
        $this->assertStringContainsString('does not determine suitability', $result['disclaimer']);
    }

    public function test_missing_comparable_fields_return_not_enough_data(): void
    {
        $job = Job::factory()->create([
            'location' => '',
            'experience' => '',
        ]);
        $application = Application::factory()->create([
            'job_id' => $job->id,
            'current_location' => '',
            'experience' => '',
        ]);

        $result = app(ScreeningMatchService::class)->evaluate($application);

        $this->assertSame('not_enough_data', $result['state']);
        $this->assertNull($result['percent']);
        $this->assertSame('Not enough data', $result['label']);
    }
}
