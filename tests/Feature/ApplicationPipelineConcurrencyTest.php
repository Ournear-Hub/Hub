<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\RecruitmentActivityType;
use App\Models\Application;
use App\Models\Job;
use App\Models\RecruitmentActivity;
use App\Services\ApplicationPipelineService;
use Tests\TestCase;

class ApplicationPipelineConcurrencyTest extends TestCase
{
    public function test_change_status_records_transitions_from_authoritative_database_status(): void
    {
        $admin = $this->makeAdmin();
        $employer = $this->makeEmployer();
        $job = Job::factory()->create(['employer_id' => $employer->employerProfile->id]);
        $application = Application::factory()->create([
            'job_id' => $job->id,
            'status' => ApplicationStatus::New,
        ]);

        $pipeline = app(ApplicationPipelineService::class);
        $stale = Application::query()->findOrFail($application->id);

        $pipeline->shortlist(Application::query()->findOrFail($application->id), $employer);
        $pipeline->changeStatus($stale, ApplicationStatus::Rejected, $admin);

        $activity = RecruitmentActivity::query()
            ->where('application_id', $application->id)
            ->where('type', RecruitmentActivityType::StatusChanged)
            ->latest('id')
            ->first();

        $this->assertNotNull($activity);
        $this->assertSame(ApplicationStatus::Shortlisted->value, $activity->from_status);
        $this->assertSame(ApplicationStatus::Rejected->value, $activity->to_status);
        $this->assertSame(ApplicationStatus::Rejected, $application->fresh()->status);
    }
}
