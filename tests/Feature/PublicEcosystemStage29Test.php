<?php

namespace Tests\Feature;

use App\Models\Job;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicEcosystemStage29Test extends TestCase
{
    public function test_public_job_omits_company_url_when_company_profile_is_not_publicly_shared(): void
    {
        $employer = $this->makeEmployer([
            'email_verified_at' => now(),
            'mobile_verified_at' => now(),
        ]);
        $profile = $employer->employerProfile;
        $this->assertNotNull($profile);
        $profile->update(['company_name' => 'Private Listing Co']);

        $this->actingAs($employer)
            ->put(route('account.profile.company-privacy.update'), ['enabled' => false])
            ->assertRedirect();

        $job = Job::factory()->create([
            'employer_id' => $profile->id,
            'title' => 'Public Role Hidden Company',
        ]);

        $this->get(route('jobs.show', $job))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('job.company_name', 'Private Listing Co')
                ->where('job.company_url', null));
    }

    public function test_public_job_links_to_company_when_profile_is_publicly_shared(): void
    {
        $employer = $this->makeEmployer([
            'email_verified_at' => now(),
            'mobile_verified_at' => now(),
        ]);
        $profile = $employer->employerProfile;
        $this->assertNotNull($profile);
        $profile->update(['company_name' => 'Linked Listing Co']);

        $job = Job::factory()->create([
            'employer_id' => $profile->id,
            'title' => 'Public Role Linked Company',
        ]);

        $expectedUrl = route('companies.show', $profile, false);

        $this->get(route('jobs.show', $job))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('job.company_url', $expectedUrl));
    }

    public function test_public_job_omits_company_url_when_employer_account_is_inactive(): void
    {
        $employer = $this->makeEmployer([
            'email_verified_at' => now(),
            'mobile_verified_at' => now(),
        ]);
        $profile = $employer->employerProfile;
        $this->assertNotNull($profile);
        $profile->update(['company_name' => 'Inactive Owner Co']);
        $employer->forceFill(['is_active' => false])->save();

        $job = Job::factory()->create([
            'employer_id' => $profile->id,
            'title' => 'Role With Inactive Employer',
        ]);

        $this->get(route('jobs.show', $job))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('job.company_name', 'Inactive Owner Co')
                ->where('job.company_url', null));
    }
}
