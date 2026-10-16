<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\Job;
use App\Support\AtsStage;
use App\Support\EmployerApplicationPipeline;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Hiring-wide applications ATS pipeline (REG-PROD-001 extension).
 * NOT RUN — deferred by product owner.
 */
class EmployerHiringApplicationsPipelineTest extends TestCase
{
    public function test_hiring_applications_index_exposes_ats_pipeline_summary(): void
    {
        $employer = $this->makeEmployer();
        $job = Job::factory()->create(['employer_id' => $employer->employerProfile->id]);
        Application::factory()->create([
            'job_id' => $job->id,
            'status' => ApplicationStatus::New,
            'full_name' => 'Hiring Wide Applicant',
        ]);

        $this->actingAs($employer)
            ->get(route('account.hiring.applications'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Hiring/Applications/Index')
                ->has('pipeline_summary', 9)
                ->where('pipeline_summary.0.key', 'all')
                ->where('pipeline_summary.1.key', AtsStage::Applied)
                ->where('pipeline_summary.1.label', 'Applied')
                ->where('applications.data.0.ats_stage', 'applied')
                ->where('applications.data.0.name', 'Hiring Wide Applicant'));
    }

    public function test_hiring_applications_stage_filter_uses_authoritative_ats_resolution(): void
    {
        $employer = $this->makeEmployer();
        $job = Job::factory()->create(['employer_id' => $employer->employerProfile->id]);
        Application::factory()->create([
            'job_id' => $job->id,
            'status' => ApplicationStatus::New,
            'full_name' => 'Applied Only',
        ]);
        Application::factory()->create([
            'job_id' => $job->id,
            'status' => ApplicationStatus::Shortlisted,
            'full_name' => 'Shortlisted Only',
        ]);

        $this->actingAs($employer)
            ->get(route('account.hiring.applications', ['stage' => AtsStage::Shortlisted]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('applications.data', 1)
                ->where('applications.data.0.name', 'Shortlisted Only')
                ->where('filters.stage', AtsStage::Shortlisted));

        $profile = $employer->employerProfile;
        $summary = EmployerApplicationPipeline::summaryForEmployerApplicationsAts($profile);
        $all = collect($summary)->firstWhere('key', 'all');
        $this->assertSame(2, $all['count'] ?? 0);
    }

    public function test_hiring_applications_legacy_stage_query_keys_map_to_ats_buckets(): void
    {
        $employer = $this->makeEmployer();
        $job = Job::factory()->create(['employer_id' => $employer->employerProfile->id]);
        Application::factory()->create([
            'job_id' => $job->id,
            'status' => ApplicationStatus::Rejected,
            'full_name' => 'Closed Applicant',
        ]);

        $this->actingAs($employer)
            ->get(route('account.hiring.applications', ['stage' => 'rejected']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('applications.data', 1)
                ->where('applications.data.0.name', 'Closed Applicant')
                ->where('filters.stage', AtsStage::Closed));
    }
}
