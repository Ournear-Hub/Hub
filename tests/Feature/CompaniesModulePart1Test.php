<?php

namespace Tests\Feature;

use App\Models\EmployerProfile;
use App\Models\Job;
use App\Models\Location;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CompaniesModulePart1Test extends TestCase
{
    public function test_companies_index_supports_keyword_industry_and_location_filters(): void
    {
        $employer = $this->eligibleEmployer();
        $profile = $employer->employerProfile;
        $this->assertNotNull($profile);
        $location = Location::query()->where('is_active', true)->first()
            ?? Location::query()->create(['name' => 'Companies QA City', 'is_active' => true, 'sort_order' => 99]);

        $profile->update([
            'company_name' => 'Part One Construction Ltd',
            'industry' => 'Construction',
            'about_company' => 'Development test company for Companies module.',
        ]);
        $profile->locations()->sync([$location->id]);

        $this->get(route('companies.index', [
            'keyword' => 'Part One',
            'industry' => 'Construction',
            'location' => $location->name,
        ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Companies/Index')
                ->where('filters.keyword', 'Part One')
                ->where('filters.industry', 'Construction')
                ->where('filters.location', $location->name)
                ->has('companies', 1)
                ->where('companies.0.id', $profile->id)
                ->where('companies.0.url', route('companies.show', $profile)));
    }

    public function test_public_company_detail_includes_id_and_zero_jobs_empty_state(): void
    {
        $employer = $this->eligibleEmployer();
        $profile = $employer->employerProfile;
        $this->assertNotNull($profile);
        $profile->update(['company_name' => 'Zero Jobs Dev Co']);

        $this->get(route('companies.show', $profile))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Companies/Show')
                ->where('company.id', $profile->id)
                ->where('company.company_name', 'Zero Jobs Dev Co')
                ->where('company.open_jobs', []));
    }

    public function test_companies_index_lists_open_job_count_for_public_company(): void
    {
        $employer = $this->eligibleEmployer();
        $profile = $employer->employerProfile;
        $this->assertNotNull($profile);
        $profile->update(['company_name' => 'Jobs Count Dev Co']);

        Job::factory()->create(['employer_id' => $profile->id, 'title' => 'Visible Role']);

        $this->get(route('companies.index', ['keyword' => 'Jobs Count']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('companies.0.open_jobs', 1));
    }

    public function test_unpublished_company_is_hidden_from_companies_index(): void
    {
        $employer = $this->eligibleEmployer();
        $profile = $employer->employerProfile;
        $this->assertNotNull($profile);
        $profile->update(['company_name' => 'Hidden From Index Co']);

        $this->actingAs($employer)
            ->put(route('account.profile.company-privacy.update'), ['enabled' => false])
            ->assertRedirect();

        $this->get(route('companies.index', ['keyword' => 'Hidden From Index']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('companies', 0));
    }

    private function eligibleEmployer()
    {
        return $this->makeEmployer([
            'email_verified_at' => now(),
            'mobile_verified_at' => now(),
        ]);
    }
}
