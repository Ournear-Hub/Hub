<?php

namespace Tests\Feature;

use App\Models\EmploymentType;
use App\Models\ExperienceRange;
use App\Models\Industry;
use App\Models\Job;
use App\Models\JobCategory;
use App\Models\Location;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Phase E — public job cards and classification display/filter parity with master data.
 */
class PhaseEJobCardsClassificationTest extends TestCase
{
    public function test_public_card_and_filters_use_master_data_when_legacy_strings_are_blank(): void
    {
        $industry = Industry::findByName('Construction');
        $category = JobCategory::query()->where('name', 'Civil')->first();
        $location = Location::query()->firstOrCreate(['name' => 'Leeds'], ['is_active' => true, 'sort_order' => 1]);
        $employment = EmploymentType::findByName('Full-time');
        $experience = ExperienceRange::findByName('1-3 years');

        $this->assertNotNull($industry);
        $this->assertNotNull($category);
        $this->assertNotNull($employment);
        $this->assertNotNull($experience);

        Job::factory()->create(['title' => 'Noise Role', 'industry' => 'Hospitality']);

        $job = Job::factory()->create([
            'title' => 'Master Data Only Engineer',
            'industry_id' => $industry->id,
            'job_category_id' => $category->id,
            'location_id' => $location->id,
            'employment_type_id' => $employment->id,
            'experience_range_id' => $experience->id,
            'industry' => '',
            'category' => '',
            'location' => '',
            'employment_type' => '',
            'experience' => '',
        ]);

        $card = $job->fresh(['classifiedIndustry', 'classifiedCategory', 'classifiedLocation', 'classifiedEmploymentType', 'classifiedExperienceRange'])->toPublicCard();

        $this->assertSame('Construction', $card['industry']);
        $this->assertSame('Civil', $card['category']);
        $this->assertSame('Leeds', $card['location']);
        $this->assertSame('Full-time', $card['employment_type']);
        $this->assertSame('1-3 years', $card['experience']);

        $this->get(route('jobs.index', ['industry' => 'Construction', 'category' => 'Civil']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Jobs/Index')
                ->has('jobs.data', 1)
                ->where('jobs.data.0.slug', $job->slug)
                ->where('jobs.data.0.industry', 'Construction')
                ->where('jobs.data.0.category', 'Civil'));

        $this->get(route('jobs.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('options.industries', fn ($values) => collect($values)->contains('Construction'))
                ->where('options.categories', fn ($values) => collect($values)->contains('Civil')));
    }
}
