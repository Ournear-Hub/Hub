<?php

namespace Tests\Feature;

use App\Enums\JobPublishingStatus;
use App\Models\EmploymentType;
use App\Models\ExperienceRange;
use App\Models\Industry;
use App\Models\Job;
use App\Models\JobCategory;
use App\Models\JobTitle;
use App\Models\Location;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class JobsWorkspaceTest extends TestCase
{
    public function test_admin_can_list_search_and_filter_jobs(): void
    {
        $admin = $this->makeAdmin();
        $matching = Job::factory()->create([
            'title' => 'Site Engineer',
            'project' => 'North Hub',
            'industry' => 'Construction',
            'location' => 'Leeds',
        ]);
        Job::factory()->create([
            'title' => 'Chef',
            'industry' => 'Hospitality',
            'location' => 'London',
        ]);

        $industry = Industry::findByName('Construction');

        $this->actingAs($admin)
            ->get(route('admin.jobs.index', ['search' => $matching->business_id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Jobs/Index')
                ->has('jobs.data', 1)
                ->where('jobs.data.0.business_id', $matching->business_id)
                ->where('jobs.data.0.title', 'Site Engineer'));

        $this->actingAs($admin)
            ->get(route('admin.jobs.index', ['industry_id' => $industry?->id, 'search' => 'Leeds']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('jobs.data', 1)
                ->where('jobs.data.0.location', 'Leeds'));
    }

    public function test_existing_jobs_with_null_fks_display_legacy_values_without_using_v1_category_as_job_category(): void
    {
        $admin = $this->makeAdmin();
        $job = Job::factory()->create([
            'title' => 'Legacy Role',
            'industry' => 'Construction',
            'category' => 'Permanent',
            'location' => 'Birmingham',
            'employment_type' => 'Full-time',
            'experience' => '1-3 years',
        ]);

        $this->assertNull($job->industry_id);
        $this->assertNull($job->job_category_id);

        $this->actingAs($admin)
            ->get(route('admin.jobs.index', ['job' => $job->slug]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('jobs.data.0.industry', 'Construction')
                ->where('jobs.data.0.location', 'Birmingham')
                ->where('jobs.data.0.employment_type', 'Full-time')
                ->where('jobs.data.0.experience', '1-3 years')
                ->where('jobs.data.0.category', null)
                ->where('jobs.data.0.legacy_category', 'Permanent')
                ->where('selected.business_id', $job->business_id)
                ->where('selected.category', null)
                ->where('selected.legacy_category', 'Permanent'));
    }

    public function test_job_create_uses_master_data_and_rejects_cross_industry_titles(): void
    {
        $admin = $this->makeAdmin();
        $industry = Industry::findByName('Construction');
        $civil = JobCategory::query()->where('name', 'Civil')->first();
        $electricalTitle = JobTitle::query()->where('name', 'Electrical Engineer')->first();
        $civilTitle = JobTitle::query()->where('name', 'Site Engineer')->first();
        $location = Location::query()->firstOrCreate(
            ['name' => 'Sheffield'],
            ['is_active' => true, 'sort_order' => 1],
        );
        $employment = EmploymentType::findByName('Full-time');
        $experience = ExperienceRange::findByName('1-3 years');

        $this->assertNotNull($industry);
        $this->assertNotNull($civil);
        $this->assertNotNull($electricalTitle);
        $this->assertNotNull($civilTitle);
        $this->assertNotNull($employment);
        $this->assertNotNull($experience);

        $this->actingAs($admin)
            ->from(route('admin.jobs.create'))
            ->post(route('admin.jobs.store'), $this->jobPayload([
                'industry_id' => $industry->id,
                'job_category_id' => $civil->id,
                'job_title_id' => $electricalTitle->id,
                'location_id' => $location->id,
                'employment_type_id' => $employment->id,
                'experience_range_id' => $experience->id,
            ]))
            ->assertRedirect()
            ->assertSessionHasErrors('job_title_id');

        $this->actingAs($admin)
            ->post(route('admin.jobs.store'), $this->jobPayload([
                'title' => 'Site Engineer — North',
                'industry_id' => $industry->id,
                'job_category_id' => $civil->id,
                'job_title_id' => $civilTitle->id,
                'location_id' => $location->id,
                'employment_type_id' => $employment->id,
                'experience_range_id' => $experience->id,
            ]))
            ->assertRedirect();

        $job = Job::query()->where('title', 'Site Engineer — North')->first();
        $this->assertNotNull($job);
        $this->assertSame($industry->id, $job->industry_id);
        $this->assertSame($civil->id, $job->job_category_id);
        $this->assertSame($civilTitle->id, $job->job_title_id);
        $this->assertSame('Construction', $job->industry);
        $this->assertSame('Civil', $job->category);
        $this->assertNotSame('Permanent', $job->category);
        $this->assertSame('Full-time', $job->employment_type);
        $this->assertSame('Sheffield', $job->location);
        $this->assertMatchesRegularExpression('/^ONT-J-\d{6}$/', (string) $job->business_id);
    }

    public function test_job_edit_preserves_legacy_strings_and_publish_active_still_work(): void
    {
        $admin = $this->makeAdmin();
        $job = Job::factory()->create([
            'industry' => 'Logistics',
            'category' => 'Contract',
            'location' => 'Manchester',
            'employment_type' => 'Part-time',
            'experience' => '5+ years',
            'publishing_status' => JobPublishingStatus::Published,
            'active' => true,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.jobs.update', $job), [
                'title' => $job->title,
                'project' => $job->project,
                'description' => 'Legacy job updated',
                'responsibilities' => $job->responsibilities,
                'requirements' => $job->requirements,
                'what_we_offer' => $job->what_we_offer,
                'salary' => $job->salary,
                'vacancy' => $job->vacancy,
                'posted_date' => $job->posted_date?->toDateString(),
                'closing_date' => $job->closing_date?->toDateString(),
                'featured' => false,
                'active' => true,
                'publishing_status' => JobPublishingStatus::Published->value,
            ])
            ->assertRedirect();

        $job->refresh();
        $this->assertSame('Legacy job updated', $job->description);
        $this->assertSame('Logistics', $job->industry);
        $this->assertSame('Contract', $job->category);
        $this->assertSame('Manchester', $job->location);
        $this->assertSame('Part-time', $job->employment_type);
        $this->assertSame('5+ years', $job->experience);
        $this->assertNull($job->job_category_id);

        $this->actingAs($admin)->patch(route('admin.jobs.publish', $job))->assertRedirect();
        $this->assertSame(JobPublishingStatus::Draft, $job->fresh()->publishing_status);

        $this->actingAs($admin)->patch(route('admin.jobs.active', $job))->assertRedirect();
        $this->assertFalse($job->fresh()->active);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function jobPayload(array $overrides = []): array
    {
        return [
            'title' => 'Site Engineer',
            'project' => 'Test Project',
            'salary' => 'Competitive',
            'vacancy' => 1,
            'posted_date' => now()->toDateString(),
            'closing_date' => now()->addDays(14)->toDateString(),
            'featured' => false,
            'active' => true,
            'publishing_status' => JobPublishingStatus::Draft->value,
            'description' => 'A recruitment listing.',
            'responsibilities' => 'Deliver the work.',
            'requirements' => 'Relevant experience.',
            'what_we_offer' => 'Training.',
            ...$overrides,
        ];
    }
}
