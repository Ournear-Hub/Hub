<?php

namespace Tests\Feature;

use App\Models\Job;
use Tests\TestCase;

class GlobalDiscoverySearchTest extends TestCase
{
    public function test_suggest_excludes_job_listings(): void
    {
        $employer = $this->makeEmployer();
        $profile = $employer->employerProfile;
        $this->assertNotNull($profile);
        $profile->update(['company_name' => 'Discovery Exclude Jobs Co']);
        $profile->forceFill([
            'public_share' => ['enabled' => true, 'industry_size' => true, 'locations' => true],
        ])->save();

        Job::factory()->create([
            'employer_id' => $profile->id,
            'title' => 'Discovery Exclude Jobs Co Engineer',
            'active' => true,
        ]);

        $this->getJson(route('search.suggest', ['q' => 'Discovery Exclude Jobs']))
            ->assertOk()
            ->assertJsonPath('results.jobs', [])
            ->assertJsonPath('results.companies.0.name', 'Discovery Exclude Jobs Co');
    }
}
