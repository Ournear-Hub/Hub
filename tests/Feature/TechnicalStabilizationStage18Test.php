<?php

namespace Tests\Feature;

use App\Models\Job;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TechnicalStabilizationStage18Test extends TestCase
{
    public function test_public_job_detail_route_resolves_for_published_job(): void
    {
        $job = Job::factory()->create(['active' => true]);

        $this->get(route('jobs.show', $job))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Jobs/Show'));
    }

    public function test_jobs_index_lists_public_jobs_and_detail_page_loads(): void
    {
        $job = Job::factory()->create(['active' => true]);

        $this->get(route('jobs.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Jobs/Index')
                ->where('jobs.data.0.slug', $job->slug));

        $this->get(route('jobs.show', $job))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Jobs/Show')
                ->where('job.slug', $job->slug)
                ->has('job.company_profile'));
    }
}
