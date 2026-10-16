<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\ApplicationStatusOption;
use App\Models\Industry;
use App\Models\Job;
use App\Models\JobCategory;
use App\Models\JobTitle;
use App\Services\MasterData\MasterDataBootstrapper;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MasterDataTest extends TestCase
{
    public function test_admin_can_view_master_data_workspace(): void
    {
        $this->actingAs($this->makeAdmin())
            ->get(route('admin.master-data.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/MasterData/Index')
                ->where('tab', 'industries')
                ->has('records.data')
                ->has('tabs'));
    }

    public function test_candidate_and_employer_cannot_access_master_data(): void
    {
        $this->actingAs($this->makeCandidate())
            ->get(route('admin.master-data.index'))
            ->assertForbidden();

        $this->actingAs($this->makeEmployer())
            ->get(route('admin.master-data.index'))
            ->assertForbidden();
    }

    public function test_admin_can_create_and_update_an_industry(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->post(route('admin.master-data.store', ['type' => 'industries']), [
                'name' => 'Energy',
                'description' => 'Power and utilities roles',
                'sort_order' => 20,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $industry = Industry::findByName('Energy');
        $this->assertNotNull($industry);
        $this->assertTrue($industry->is_active);
        $this->assertSame('Power and utilities roles', $industry->description);

        $this->actingAs($admin)
            ->put(route('admin.master-data.update', ['type' => 'industries', 'id' => $industry->id]), [
                'name' => 'Energy & Utilities',
                'description' => 'Updated',
                'sort_order' => 21,
            ])
            ->assertRedirect();

        $this->assertSame('Energy & Utilities', $industry->fresh()->name);
        $this->assertDatabaseHas('master_data_changes', [
            'entity_type' => 'industries',
            'entity_id' => $industry->id,
            'action' => 'updated',
        ]);
    }

    public function test_duplicate_active_industry_name_is_rejected(): void
    {
        $this->actingAs($this->makeAdmin())
            ->from(route('admin.master-data.index'))
            ->post(route('admin.master-data.store', ['type' => 'industries']), [
                'name' => 'Construction',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('name');
    }

    public function test_category_requires_an_active_industry_and_job_title_requires_that_category(): void
    {
        $admin = $this->makeAdmin();
        $industry = Industry::findByName('Construction');
        $this->assertNotNull($industry);

        $this->actingAs($admin)
            ->post(route('admin.master-data.store', ['type' => 'categories']), [
                'name' => 'Plumbing',
            ])
            ->assertSessionHasErrors('industry_id');

        $this->actingAs($admin)
            ->post(route('admin.master-data.store', ['type' => 'categories']), [
                'name' => 'Plumbing',
                'industry_id' => $industry->id,
            ])
            ->assertRedirect();

        $category = JobCategory::query()->where('name', 'Plumbing')->first();
        $this->assertNotNull($category);
        $this->assertSame($industry->id, $category->industry_id);

        $this->actingAs($admin)
            ->post(route('admin.master-data.store', ['type' => 'job-titles']), [
                'name' => 'Plumber',
                'industry_id' => $industry->id,
            ])
            ->assertSessionHasErrors('job_category_id');

        $this->actingAs($admin)
            ->post(route('admin.master-data.store', ['type' => 'job-titles']), [
                'name' => 'Plumber',
                'industry_id' => $industry->id,
                'job_category_id' => $category->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('job_titles', [
            'name' => 'Plumber',
            'job_category_id' => $category->id,
        ]);
    }

    public function test_inactive_industry_is_not_selectable_for_new_categories(): void
    {
        $admin = $this->makeAdmin();
        $industry = Industry::query()->create([
            'name' => 'Archived Sector',
            'is_active' => false,
            'sort_order' => 99,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.master-data.store', ['type' => 'categories']), [
                'name' => 'Hidden Trade',
                'industry_id' => $industry->id,
            ])
            ->assertSessionHasErrors('industry_id');
    }

    public function test_deactivating_an_industry_keeps_historical_categories(): void
    {
        $admin = $this->makeAdmin();
        $industry = Industry::findByName('Construction');
        $category = JobCategory::query()->where('name', 'Civil')->first();
        $title = JobTitle::query()->where('name', 'Site Engineer')->first();

        $this->assertNotNull($industry);
        $this->assertNotNull($category);
        $this->assertNotNull($title);

        $this->actingAs($admin)
            ->from(route('admin.master-data.index'))
            ->patch(route('admin.master-data.status', ['type' => 'industries', 'id' => $industry->id]), [
                'is_active' => false,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertFalse($industry->fresh()->is_active);
        $this->assertTrue($category->fresh()->is_active);
        $this->assertSame($industry->id, $category->fresh()->industry_id);
        $this->assertTrue($title->fresh()->is_active);
        $this->assertFalse($category->fresh()->isSelectable());
    }

    public function test_system_application_status_cannot_be_deactivated_or_renamed(): void
    {
        $admin = $this->makeAdmin();
        $status = ApplicationStatusOption::query()->where('code', ApplicationStatus::New->value)->first();
        $this->assertNotNull($status);

        $this->actingAs($admin)
            ->patch(route('admin.master-data.status', ['type' => 'application-statuses', 'id' => $status->id]), [
                'is_active' => false,
            ])
            ->assertSessionHasErrors('is_active');

        $this->actingAs($admin)
            ->put(route('admin.master-data.update', ['type' => 'application-statuses', 'id' => $status->id]), [
                'name' => 'Brand New',
                'sort_order' => 1,
            ])
            ->assertSessionHasErrors('name');

        $this->assertSame(ApplicationStatus::New->value, $status->fresh()->code);
        $this->assertSame(ApplicationStatus::New->value, $status->fresh()->name);
        $this->assertTrue($status->fresh()->is_active);
    }

    public function test_existing_job_strings_are_preserved_and_unambiguous_values_are_mapped(): void
    {
        $job = Job::factory()->create([
            'industry' => 'Construction',
            'category' => 'Permanent',
            'employment_type' => 'Full-time',
            'experience' => '1-3 years',
            'location' => 'Manchester',
        ]);

        $this->assertNull($job->industry_id);
        $this->assertNull($job->job_category_id);

        app(MasterDataBootstrapper::class)->run();

        $job->refresh();

        $this->assertSame('Construction', $job->industry);
        $this->assertSame('Permanent', $job->category);
        $this->assertSame('Full-time', $job->employment_type);
        $this->assertSame('1-3 years', $job->experience);
        $this->assertSame('Manchester', $job->location);
        $this->assertNotNull($job->industry_id);
        $this->assertSame('Construction', $job->classifiedIndustry?->name);
        $this->assertNull($job->job_category_id);
        $this->assertNull($job->job_title_id);
        $this->assertNotNull($job->employment_type_id);
        $this->assertNotNull($job->experience_range_id);
        $this->assertNotNull($job->location_id);
        $this->assertMatchesRegularExpression('/^ONT-J-\d{6}$/', (string) $job->business_id);
    }

    public function test_existing_application_status_values_remain_valid_with_nullable_foreign_keys(): void
    {
        $application = Application::factory()->create([
            'status' => ApplicationStatus::Interview,
            'qualification' => 'Degree',
            'experience' => '5+ years',
        ]);

        $this->assertSame(ApplicationStatus::Interview, $application->status);
        $this->assertNull($application->qualification_id);

        app(MasterDataBootstrapper::class)->mapExistingForeignKeys();

        $application->refresh();

        $this->assertSame(ApplicationStatus::Interview, $application->status);
        $this->assertSame('Degree', $application->qualification);
        $this->assertSame('5+ years', $application->experience);
        $this->assertNotNull($application->qualification_id);
        $this->assertNotNull($application->experience_range_id);
        $this->assertMatchesRegularExpression('/^ONT-A-\d{6}$/', (string) $application->business_id);
    }

    public function test_bootstrapper_seeds_mining_and_infrastructure_job_categories(): void
    {
        app(MasterDataBootstrapper::class)->run();

        $mining = Industry::findByName('Mining');
        $infrastructure = Industry::findByName('Infrastructure');
        $this->assertNotNull($mining);
        $this->assertNotNull($infrastructure);

        $miningCategories = JobCategory::query()
            ->where('industry_id', $mining->id)
            ->pluck('name')
            ->all();
        $this->assertContains('Open Cast & Surface Mining', $miningCategories);
        $this->assertContains('Underground Mining', $miningCategories);

        $infrastructureCategories = JobCategory::query()
            ->where('industry_id', $infrastructure->id)
            ->pluck('name')
            ->all();
        $this->assertContains('Civil & Structural Works', $infrastructureCategories);
        $this->assertContains('Roads & Highways', $infrastructureCategories);
    }
}
