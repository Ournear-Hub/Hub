<?php

namespace Tests\Feature;

use App\Models\EmployerProfile;
use App\Models\Job;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicJobsCompanyFilterTest extends TestCase
{
    public function test_valid_employer_id_returns_only_that_employers_public_jobs(): void
    {
        $acme = $this->makeEmployer();
        $acme->employerProfile->update(['company_name' => 'Filter Co Alpha']);
        $other = $this->makeEmployer();
        $other->employerProfile->update(['company_name' => 'Filter Co Beta']);

        $match = Job::factory()->create(['employer_id' => $acme->employerProfile->id]);
        Job::factory()->create(['employer_id' => $other->employerProfile->id]);
        Job::factory()->create(['employer_id' => null, 'project' => 'Filter Co Alpha']);

        $this->get(route('jobs.index', ['employer' => $acme->employerProfile->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('jobs.data', 1)
                ->where('jobs.data.0.slug', $match->slug));
    }

    public function test_draft_and_inactive_jobs_stay_hidden_when_filtering_by_company(): void
    {
        $employer = $this->makeEmployer();
        $employer->employerProfile->update(['company_name' => 'Hidden Draft Co']);

        Job::factory()->create(['employer_id' => $employer->employerProfile->id]);
        Job::factory()->draft()->create(['employer_id' => $employer->employerProfile->id]);
        Job::factory()->create([
            'employer_id' => $employer->employerProfile->id,
            'active' => false,
        ]);

        $this->get(route('jobs.index', ['company' => 'Hidden Draft Co']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('jobs.data', 1));
    }

    public function test_invalid_employer_id_returns_empty_results(): void
    {
        Job::factory()->create();

        $this->get(route('jobs.index', ['employer' => 999999]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('jobs.data', 0));
    }

    public function test_clearing_company_filter_restores_broader_results(): void
    {
        $employer = $this->makeEmployer();
        $employer->employerProfile->update(['company_name' => 'Clear Filter Co']);
        Job::factory()->create(['employer_id' => $employer->employerProfile->id]);
        Job::factory()->create();

        $this->get(route('jobs.index', ['company' => 'Clear Filter Co']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('jobs.data', 1));

        $this->get(route('jobs.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('jobs.data', 2));
    }

    public function test_company_profile_includes_filtered_public_jobs_url(): void
    {
        $employer = $this->makeEmployer();
        $profile = $employer->employerProfile;
        $this->assertInstanceOf(EmployerProfile::class, $profile);
        $profile->update([
            'company_name' => 'Profile Jobs Co',
            'public_share' => array_merge($profile->publicShare(), ['enabled' => true]),
        ]);

        $this->get(route('companies.show', $profile))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('company.public_jobs_url', route('jobs.index', ['employer' => $profile->id])));
    }
}
