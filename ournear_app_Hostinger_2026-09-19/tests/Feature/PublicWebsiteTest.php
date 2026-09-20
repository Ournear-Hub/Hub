<?php

namespace Tests\Feature;

use App\Models\Job;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicWebsiteTest extends TestCase
{
    public function test_homepage_loads(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Home'));
    }

    public function test_jobs_index_loads(): void
    {
        Job::factory()->create(['title' => 'Site Engineer']);

        $this->get(route('jobs.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Jobs/Index')
                ->has('jobs.data', 1)
                ->where('jobs.data.0.title', 'Site Engineer'));
    }

    public function test_published_job_detail_loads(): void
    {
        $job = Job::factory()->create(['title' => 'Quantity Surveyor']);

        $this->get(route('jobs.show', $job))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Jobs/Show')
                ->where('job.title', 'Quantity Surveyor')
                ->where('job.slug', $job->slug));
    }

    public function test_draft_job_is_not_public(): void
    {
        $job = Job::factory()->draft()->create();

        $this->get(route('jobs.show', $job))->assertNotFound();
        $this->get(route('jobs.apply', $job))->assertNotFound();
    }

    public function test_job_application_page_loads(): void
    {
        $job = Job::factory()->create();

        $this->get(route('jobs.apply', $job))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Jobs/Apply')
                ->where('canApply', true)
                ->where('job.slug', $job->slug));
    }

    public function test_employer_enquiry_page_loads(): void
    {
        $this->get(route('for-employers'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('ForEmployers'));
    }

    public function test_contact_page_loads(): void
    {
        $this->get(route('contact'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Contact'));
    }
}
