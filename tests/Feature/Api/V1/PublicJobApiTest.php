<?php

namespace Tests\Feature\Api\V1;

use App\Models\Job;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicJobApiTest extends TestCase
{
    public function test_published_jobs_are_returned_as_json(): void
    {
        $job = Job::factory()->create([
            'title' => 'Mobile Visible Electrician',
            'slug' => 'mobile-visible-electrician',
            'location' => 'Dubai',
        ]);

        $response = $this->getJson(route('api.v1.jobs.index'));

        $response->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.title', 'Mobile Visible Electrician')
            ->assertJsonPath('data.0.slug', 'mobile-visible-electrician')
            ->assertJsonPath('data.0.location', 'Dubai');
    }

    public function test_unpublished_jobs_are_excluded_from_index(): void
    {
        Job::factory()->create(['title' => 'Visible Role']);
        Job::factory()->draft()->create(['title' => 'Draft Hidden Role']);
        Job::factory()->inactive()->create(['title' => 'Inactive Hidden Role']);
        Job::factory()->closed()->create(['title' => 'Closed Hidden Role']);

        $response = $this->getJson(route('api.v1.jobs.index'));

        $response->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.title', 'Visible Role');
    }

    public function test_job_detail_respects_public_visibility(): void
    {
        $job = Job::factory()->create([
            'title' => 'Detail API Job',
            'slug' => 'detail-api-job',
            'description' => 'Public description body',
        ]);

        $this->getJson(route('api.v1.jobs.show', $job))
            ->assertOk()
            ->assertJsonPath('data.title', 'Detail API Job')
            ->assertJsonPath('data.description', 'Public description body')
            ->assertJsonPath('data.accepts_applications', true);
    }

    public function test_job_detail_returns_not_found_when_not_publicly_visible(): void
    {
        $job = Job::factory()->draft()->create(['slug' => 'hidden-draft-job']);

        $this->getJson(route('api.v1.jobs.show', $job))->assertNotFound();
    }

    public function test_invalid_pagination_inputs_are_rejected(): void
    {
        $this->getJson(route('api.v1.jobs.index', ['per_page' => 0]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['per_page']);

        $this->getJson(route('api.v1.jobs.index', ['page' => 0]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['page']);
    }

    public function test_keyword_search_filters_results(): void
    {
        Job::factory()->create(['title' => 'Warehouse Supervisor', 'location' => 'London']);
        Job::factory()->create(['title' => 'Site Engineer', 'location' => 'Manchester']);

        $this->getJson(route('api.v1.jobs.index', ['keyword' => 'Warehouse']))
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.title', 'Warehouse Supervisor');
    }

    public function test_private_internal_fields_are_not_exposed(): void
    {
        $job = Job::factory()->create(['title' => 'Sanitized Payload Job']);

        $payload = $this->getJson(route('api.v1.jobs.index'))
            ->assertOk()
            ->json('data.0');

        $this->assertIsArray($payload);
        $this->assertArrayNotHasKey('id', $payload);
        $this->assertArrayNotHasKey('employer_id', $payload);
        $this->assertArrayNotHasKey('publishing_status', $payload);
        $this->assertArrayHasKey('title', $payload);
        $this->assertSame('Sanitized Payload Job', $payload['title']);
        $this->assertSame($job->slug, $payload['slug']);
    }

    public function test_public_web_jobs_route_remains_functional(): void
    {
        Job::factory()->create(['title' => 'Web Index Job']);

        $this->get(route('jobs.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Jobs/Index')
                ->has('jobs.data', 1)
                ->where('jobs.data.0.title', 'Web Index Job'));
    }
}
