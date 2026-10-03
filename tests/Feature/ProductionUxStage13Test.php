<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\EmployerSubmissionStatus;
use App\Enums\ScreeningDecision;
use App\Enums\StaffRoleSlug;
use App\Models\Application;
use App\Models\EmployerSubmission;
use App\Models\Industry;
use App\Models\Job;
use App\Services\ScreeningMatchService;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProductionUxStage13Test extends TestCase
{
    public function test_admin_screening_workspace_includes_candidate_vs_job_desk_and_keeps_disclaimer(): void
    {
        $admin = $this->makeAdmin();
        $job = Job::factory()->create([
            'location' => 'Manchester',
            'experience' => '1-3 years',
        ]);
        $application = Application::factory()->create([
            'job_id' => $job->id,
            'user_id' => null,
            'full_name' => 'Guest Snapshot Applicant',
            'current_location' => 'Manchester',
            'experience' => '1-3 years',
            'qualification' => 'NVQ',
            'status' => ApplicationStatus::New,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.screening.index', ['application' => $application->id, 'job_id' => $job->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Screening/Index')
                ->where('selected.full_name', 'Guest Snapshot Applicant')
                ->where('selected.match.disclaimer', ScreeningMatchService::DISCLAIMER)
                ->where('selected.match.percent', 100)
                ->where('selected.match.comparable_count', 2)
                ->where('selected.job_comparison.application_source', 'snapshot')
                ->where('selected.job_comparison.disclaimer', ScreeningMatchService::DISCLAIMER)
                ->where('selected.status', ApplicationStatus::New->value)
                ->where('selected.screening.decision', ScreeningDecision::Pending->value)
                ->has('selected.job_comparison.rows')
                ->has('jobSummary.experience')
                ->has('jobSummary.industry')
                ->where('applications.data.0.match.disclaimer', ScreeningMatchService::DISCLAIMER));

        $this->assertSame(ApplicationStatus::New, $application->fresh()->status);
        $this->assertDatabaseMissing('application_screenings', ['application_id' => $application->id]);
    }

    public function test_guest_application_desk_uses_snapshot_and_does_not_invent_profile_fields(): void
    {
        $admin = $this->makeAdmin();
        $industry = Industry::query()->firstOrCreate(
            ['name' => 'Stage Thirteen Guest Industry'],
            ['is_active' => true, 'sort_order' => 91],
        );
        $job = Job::factory()->create([
            'location' => 'Bristol',
            'experience' => '0-1 years',
            'industry' => $industry->name,
            'industry_id' => $industry->id,
        ]);
        $application = Application::factory()->create([
            'job_id' => $job->id,
            'user_id' => null,
            'current_location' => 'Bristol',
            'experience' => '0-1 years',
            'qualification' => 'GCSEs',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.screening.index', ['application' => $application->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Screening/Index')
                ->where('selected.job_comparison.application_source', 'snapshot')
                ->where('selected.designation', null)
                ->where('selected.skills', null)
                ->where('selected.notice_period', null)
                ->where(
                    'selected.job_comparison.rows',
                    fn ($rows) => collect($rows)->firstWhere('key', 'industry')['state'] === 'missing'
                        && collect($rows)->firstWhere('key', 'relocation')['state'] === 'missing'
                        && collect($rows)->firstWhere('key', 'location')['source'] === 'snapshot'
                        && collect($rows)->firstWhere('key', 'location')['candidate_value'] === 'Bristol'
                        && collect($rows)->firstWhere('key', 'qualification')['candidate_value'] === 'GCSEs'
                        && collect($rows)->firstWhere('key', 'qualification')['state'] === 'presented'
                        && collect($rows)->every(fn ($row) => ! str_contains((string) ($row['note'] ?? ''), 'V1.1'))
                        && collect($rows)->firstWhere('key', 'skills')['state'] !== 'compared'
                ));
    }

    public function test_existing_permissions_still_gate_admin_screening(): void
    {
        $application = Application::factory()->create(['full_name' => 'Permission Applicant']);

        $this->actingAs($this->makeStaff(StaffRoleSlug::Recruiter->value))
            ->get(route('admin.screening.index', ['application' => $application->id]))
            ->assertOk();

        $this->actingAs($this->makeStaff(StaffRoleSlug::Viewer->value))
            ->get(route('admin.screening.index'))
            ->assertOk();

        $this->actingAs($this->makeStaff(StaffRoleSlug::Finance->value))
            ->get(route('admin.screening.index'))
            ->assertForbidden();

        $this->actingAs($this->makeStaff(StaffRoleSlug::Finance->value))
            ->put(route('admin.applications.screening', $application), [
                'decision' => ScreeningDecision::Suitable->value,
                'notes' => 'Must stay forbidden.',
            ])
            ->assertForbidden();

        $this->actingAs($this->makeCandidate())
            ->get(route('admin.screening.index'))
            ->assertForbidden();
    }

    public function test_employer_cannot_access_admin_screening_or_internal_match_payloads(): void
    {
        $employer = $this->makeEmployer();
        $profile = $employer->employerProfile;
        $this->assertNotNull($profile);

        $job = Job::factory()->create(['employer_id' => $profile->id]);
        $application = Application::factory()->create([
            'job_id' => $job->id,
            'full_name' => 'Employer Visible Applicant',
            'status' => ApplicationStatus::New,
        ]);
        $submission = EmployerSubmission::query()->create([
            'employer_profile_id' => $profile->id,
            'job_id' => $job->id,
            'application_id' => $application->id,
            'status' => EmployerSubmissionStatus::Submitted,
            'submitted_by' => $employer->id,
            'submitted_at' => now(),
        ]);

        $this->actingAs($employer)
            ->get(route('admin.screening.index'))
            ->assertForbidden();

        $this->actingAs($employer)
            ->get(route('account.employer.jobs.applicants', $job))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Employer/Applicants/Index')
                ->where('applications.data.0.name', 'Employer Visible Applicant')
                ->missing('applications.data.0.match')
                ->missing('applications.data.0.match_percent')
                ->missing('applications.data.0.match_label')
                ->missing('applications.data.0.job_comparison')
                ->missing('applications.data.0.screening_notes')
                ->missing('applications.data.0.factors'));

        $this->actingAs($employer)
            ->get(route('account.employer.submissions.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Employer/Submissions/Index')
                ->where('submissions.data.0.submission_id', $submission->id)
                ->missing('submissions.data.0.match')
                ->missing('submissions.data.0.match_percent')
                ->missing('submissions.data.0.job_comparison')
                ->missing('submissions.data.0.screening_notes'));
    }
}
