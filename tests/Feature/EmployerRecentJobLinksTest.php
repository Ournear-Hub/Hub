<?php

namespace Tests\Feature;

use App\Models\Job;
use App\Services\AccountDashboardService;
use Tests\TestCase;

class EmployerRecentJobLinksTest extends TestCase
{
    public function test_recent_job_view_href_points_to_applicants_in_company_context(): void
    {
        $employer = $this->makeEmployer();
        $profile = $employer->employerProfile;
        $this->assertNotNull($profile);

        $job = Job::factory()->for($profile, 'employer')->create([
            'title' => 'QA Recent Job Link',
            'active' => true,
        ]);

        $dashboard = app(AccountDashboardService::class)->employerHiringCommandCenter($employer);
        $recent = collect($dashboard['recent_jobs'] ?? []);
        $row = $recent->firstWhere('slug', $job->slug);

        $this->assertNotNull($row);
        $this->assertSame('/account/jobs/'.$job->slug.'/applicants', $row['href']);
    }

    public function test_employer_jobs_applicants_route_is_reachable(): void
    {
        $employer = $this->makeEmployer();
        $job = Job::factory()->for($employer->employerProfile, 'employer')->create();

        $this->actingAs($employer)
            ->get('/account/jobs/'.$job->slug.'/applicants')
            ->assertOk();
    }
}
