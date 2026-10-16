<?php

namespace Tests\Feature;

use App\Models\HomepageStatistic;
use App\Models\MediaAsset;
use App\Models\ShowcaseCompany;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ShowcaseCompaniesAndHomepageStatisticsTest extends TestCase
{
    public function test_admin_can_save_manual_homepage_statistics_and_home_renders_them(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->put(route('admin.website-content.showcase.statistics'), [
                'registered_companies' => 1200,
                'talent_pool_companies' => 340,
                'staffing_companies' => 88,
                'registered_users' => 56000,
            ])
            ->assertRedirect(route('admin.website-content.index', ['page' => 'showcase-statistics']));

        $row = HomepageStatistic::query()->where('lock_key', 'default')->firstOrFail();
        $this->assertSame(1200, $row->registered_companies);

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('homeGlanceStats', 4)
                ->where('homeGlanceStats.0.formatted', '1,200')
                ->where('homeGlanceStats.3.formatted', '56,000'));
    }

    public function test_statistics_reject_negative_values(): void
    {
        $this->actingAs($this->makeAdmin())
            ->put(route('admin.website-content.showcase.statistics'), [
                'registered_companies' => -1,
                'talent_pool_companies' => 0,
                'staffing_companies' => 0,
                'registered_users' => 0,
            ])
            ->assertSessionHasErrors('registered_companies');
    }

    public function test_showcase_company_appears_on_assigned_public_surfaces_only(): void
    {
        Storage::disk('public')->put('media/showcase-co.png', UploadedFile::fake()->image('co.png', 80, 40)->getContent());
        $media = MediaAsset::query()->create([
            'disk' => 'public',
            'path' => 'media/showcase-co.png',
            'original_name' => 'co.png',
            'mime_type' => 'image/png',
            'extension' => 'png',
            'size_bytes' => 10,
        ]);

        $company = ShowcaseCompany::query()->create([
            'name' => 'Showcase Co',
            'media_asset_id' => $media->id,
            'is_active' => true,
            'sort_order' => 0,
            'show_on_home' => true,
            'show_on_talent_pool' => false,
            'show_on_staffing' => true,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('homeShowcaseCompanies.0.name', 'Showcase Co'));

        $this->get(route('talent-pool'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('talentPoolShowcaseCompanies', []));

        $this->get(route('staffing'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('staffingShowcaseCompanies.0.name', 'Showcase Co'));

        $company->forceFill(['is_active' => false])->save();

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('homeShowcaseCompanies', []));
    }

    public function test_staff_without_website_permission_cannot_manage_showcase(): void
    {
        $this->actingAs($this->makeStaff('recruiter'))
            ->put(route('admin.website-content.showcase.statistics'), [
                'registered_companies' => 1,
                'talent_pool_companies' => 1,
                'staffing_companies' => 1,
                'registered_users' => 1,
            ])
            ->assertForbidden();
    }

    public function test_showcase_card_reuses_one_employer_profile_across_placements(): void
    {
        $admin = $this->makeAdmin();
        $employer = $this->makeEmployer();
        $profile = $employer->employerProfile;
        $this->assertNotNull($profile);

        $this->actingAs($admin)
            ->post(route('admin.website-content.showcase.companies.store'), [
                'name' => $profile->displayCompany(),
                'employer_profile_id' => $profile->id,
                'show_on_home' => true,
                'show_on_talent_pool' => true,
                'show_on_staffing' => false,
                'is_active' => true,
            ])
            ->assertRedirect();

        $card = ShowcaseCompany::query()->where('employer_profile_id', $profile->id)->first();
        $this->assertNotNull($card);
        $this->assertTrue($card->show_on_home);
        $this->assertTrue($card->show_on_talent_pool);
        $this->assertFalse($card->show_on_staffing);

        $this->actingAs($admin)
            ->post(route('admin.website-content.showcase.companies.store'), [
                'name' => $profile->displayCompany(),
                'employer_profile_id' => $profile->id,
                'show_on_staffing' => true,
            ])
            ->assertSessionHasErrors('employer_profile_id');

        $this->assertSame(1, ShowcaseCompany::query()->where('employer_profile_id', $profile->id)->count());

        $this->actingAs($admin)
            ->put(route('admin.website-content.showcase.companies.update', $card), [
                'show_on_staffing' => true,
            ])
            ->assertRedirect();

        $this->assertTrue($card->fresh()->show_on_staffing);
    }
}
