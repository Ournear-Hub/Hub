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
 * Employer job applicants ATS pipeline UI (REG-PROD-001).
 * REG-PROD-001 employer job applicants ATS pipeline (PHPUnit verified 2026-10-12).
 */
class EmployerJobApplicantPipelineTest extends TestCase
{
    public function test_job_applicants_page_exposes_ats_pipeline_summary_and_stage_labels(): void
    {
        $employer = $this->makeEmployer();
        $job = Job::factory()->create(['employer_id' => $employer->employerProfile->id]);
        Application::factory()->create([
            'job_id' => $job->id,
            'status' => ApplicationStatus::New,
            'full_name' => 'Pipeline Applicant',
        ]);

        $this->actingAs($employer)
            ->get(route('account.employer.jobs.applicants', $job))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Employer/Applicants/Index')
                ->has('pipeline_summary', 9)
                ->where('pipeline_summary.0.key', 'all')
                ->where('pipeline_summary.1.key', AtsStage::Applied)
                ->where('pipeline_summary.1.label', 'Applied')
                ->where('applications.data.0.ats_stage', 'applied')
                ->where('applications.data.0.name', 'Pipeline Applicant'));
    }

    public function test_stage_filter_uses_authoritative_ats_resolution(): void
    {
        $employer = $this->makeEmployer();
        $job = Job::factory()->create(['employer_id' => $employer->employerProfile->id]);
        Application::factory()->create([
            'job_id' => $job->id,
            'status' => ApplicationStatus::New,
            'full_name' => 'Applied Stage',
        ]);
        Application::factory()->create([
            'job_id' => $job->id,
            'status' => ApplicationStatus::Shortlisted,
            'full_name' => 'Shortlisted Stage',
        ]);

        $this->actingAs($employer)
            ->get(route('account.employer.jobs.applicants', ['job' => $job, 'stage' => AtsStage::Shortlisted]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('applications.data', 1)
                ->where('applications.data.0.name', 'Shortlisted Stage')
                ->where('filters.stage', AtsStage::Shortlisted));

        $summary = EmployerApplicationPipeline::summaryForJobAts($job);
        $all = collect($summary)->firstWhere('key', 'all');
        $this->assertSame(2, $all['count'] ?? 0);
    }

    public function test_legacy_stage_query_keys_map_to_ats_buckets(): void
    {
        $employer = $this->makeEmployer();
        $job = Job::factory()->create(['employer_id' => $employer->employerProfile->id]);
        Application::factory()->create([
            'job_id' => $job->id,
            'status' => ApplicationStatus::Rejected,
            'full_name' => 'Closed Applicant',
        ]);

        $this->actingAs($employer)
            ->get(route('account.employer.jobs.applicants', ['job' => $job, 'stage' => 'rejected']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.stage', AtsStage::Closed)
                ->has('applications.data', 1));
    }

    public function test_other_employer_cannot_open_job_applicants_pipeline(): void
    {
        $owner = $this->makeEmployer();
        $intruder = $this->makeEmployer();
        $job = Job::factory()->create(['employer_id' => $owner->employerProfile->id]);

        $this->actingAs($intruder)
            ->get(route('account.employer.jobs.applicants', $job))
            ->assertForbidden();
    }
}
