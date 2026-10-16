<?php

namespace Tests\Feature;

use App\Models\Industry;
use App\Models\Job;
use App\Models\JobCategory;
use App\Models\JobTitle;
use App\Services\PopularJobSearchService;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HomepagePopularSearchDiscoveryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::forget('homepage.popular_designation_discovery');
    }

    public function test_homepage_exposes_master_data_popular_search_discovery(): void
    {
        $title = JobTitle::query()
            ->with('category.industry')
            ->where('is_active', true)
            ->whereHas('category', fn ($query) => $query->where('is_active', true))
            ->first();

        $this->assertNotNull($title, 'Seed at least one active job title for discovery.');

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Home')
                ->has('popularSearchDiscovery.industries')
                ->where('popularSearchDiscovery.designations.0.label', fn ($label) => is_string($label) && $label !== '')
                ->where('popularSearchDiscovery.designations.0.href', fn ($href) => is_string($href) && str_contains($href, 'job_title_id=')));
    }

    public function test_discovery_excludes_inactive_master_titles(): void
    {
        $activeTitle = JobTitle::query()
            ->with('category.industry')
            ->where('is_active', true)
            ->whereHas('category', fn ($query) => $query->where('is_active', true))
            ->first();

        $this->assertNotNull($activeTitle);

        $inactive = JobTitle::query()->create([
            'name' => 'QA Inactive Fixture Title '.uniqid(),
            'job_category_id' => $activeTitle->job_category_id,
            'is_active' => false,
            'sort_order' => 999,
        ]);

        $discovery = app(PopularJobSearchService::class)->homepageDiscovery();
        $labels = collect($discovery['designations'])->pluck('label')->all();

        $this->assertNotContains($inactive->name, $labels);
    }

    public function test_designation_href_filters_public_jobs_by_job_title_id(): void
    {
        $industry = Industry::query()->create([
            'name' => 'Discovery Filter Industry '.uniqid(),
            'is_active' => true,
            'sort_order' => 900,
        ]);
        $category = JobCategory::query()->create([
            'industry_id' => $industry->id,
            'name' => 'Discovery Filter Category',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $title = JobTitle::query()->create([
            'job_category_id' => $category->id,
            'name' => 'Discovery Filter Designation',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $employer = $this->makeEmployer();
        $match = Job::factory()->create([
            'employer_id' => $employer->employerProfile->id,
            'job_title_id' => $title->id,
            'industry_id' => $industry->id,
            'title' => 'Discovery Filter Role',
        ]);
        $other = Job::factory()->create([
            'employer_id' => $employer->employerProfile->id,
            'job_title_id' => null,
            'title' => 'Unrelated listing title',
        ]);

        $this->assertSame(1, Job::query()->where('job_title_id', $title->id)->count());
        $this->assertNull($other->fresh()->job_title_id);

        $this->get(route('jobs.index', ['job_title_id' => $title->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('jobs.data', 1)
                ->where('jobs.data.0.slug', $match->slug));
    }
}
