<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\InterviewMode;
use App\Enums\InterviewOutcome;
use App\Enums\ScreeningDecision;
use App\Models\Application;
use App\Models\ApplicationInterview;
use App\Models\Job;
use App\Models\Location;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ApplicationsWorkspaceTest extends TestCase
{
    public function test_admin_can_list_search_and_filter_applications(): void
    {
        $admin = $this->makeAdmin();
        $job = Job::factory()->create(['title' => 'Site Engineer']);
        $match = Application::factory()->create([
            'job_id' => $job->id,
            'full_name' => 'Priya Applicant',
            'email' => 'priya@example.com',
            'mobile' => '07111111111',
            'current_location' => 'Manchester',
            'status' => ApplicationStatus::New,
            'user_id' => null,
        ]);
        Application::factory()->create([
            'full_name' => 'Other Person',
            'status' => ApplicationStatus::Reviewing,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.applications.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Applications/Index')
                ->has('applications.data', 2)
                ->where('applications.data.0.business_id', fn ($id) => is_string($id) && str_starts_with($id, 'ONT-A-')));

        $this->actingAs($admin)
            ->get(route('admin.applications.index', ['search' => $match->business_id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('applications.data', 1)
                ->where('applications.data.0.full_name', 'Priya Applicant')
                ->where('applications.data.0.business_id', $match->business_id)
                ->where('applications.data.0.job.business_id', $job->business_id));

        $this->actingAs($admin)
            ->get(route('admin.applications.index', ['search' => $job->business_id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('applications.data', 1));

        $this->actingAs($admin)
            ->get(route('admin.applications.index', [
                'status' => ApplicationStatus::New->value,
                'job_id' => $job->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('applications.data', 1)
                ->where('filters.status', ApplicationStatus::New->value)
                ->where('filters.job_id', (string) $job->id));
    }

    public function test_guest_application_still_opens_and_displays_without_candidate_id(): void
    {
        $admin = $this->makeAdmin();
        $application = Application::factory()->create([
            'full_name' => 'Guest Applicant',
            'user_id' => null,
            'current_location' => 'Leeds',
            'experience' => '1-3 years',
            'qualification' => 'NVQ',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.applications.index', ['application' => $application->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('selected.id', $application->id)
                ->where('selected.full_name', 'Guest Applicant')
                ->where('selected.business_id', $application->business_id)
                ->where('selected.candidate.id', null)
                ->where('selected.candidate.business_id', null)
                ->where('selected.current_location', 'Leeds')
                ->where('selected.screening.decision', ScreeningDecision::Pending->value)
                ->where('selected.status', ApplicationStatus::New->value));
    }

    public function test_existing_status_change_and_interview_still_work(): void
    {
        $admin = $this->makeAdmin();
        $application = Application::factory()->create(['status' => ApplicationStatus::New]);
        ApplicationInterview::query()->create([
            'application_id' => $application->id,
            'scheduled_at' => now()->addDay(),
            'mode' => InterviewMode::Phone,
            'interviewer_name' => 'Alex Recruiter',
            'outcome' => InterviewOutcome::Pending,
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.applications.index', ['application' => $application->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('selected.interview.interviewer_name', 'Alex Recruiter')
                ->where('selected.interview.mode', InterviewMode::Phone->value));

        $this->actingAs($admin)
            ->from(route('admin.applications.index', ['application' => $application->id]))
            ->patch(route('admin.applications.status', $application), [
                'status' => ApplicationStatus::Reviewing->value,
            ])
            ->assertRedirect();

        $this->assertSame(ApplicationStatus::Reviewing, $application->fresh()->status);
    }

    public function test_location_filter_matches_legacy_string_when_fk_is_null(): void
    {
        $admin = $this->makeAdmin();
        $location = Location::query()->firstOrCreate(
            ['name' => 'Sheffield'],
            ['is_active' => true, 'sort_order' => 1],
        );
        Application::factory()->create([
            'current_location' => 'Sheffield',
            'location_id' => null,
        ]);
        Application::factory()->create(['current_location' => 'London']);

        $this->actingAs($admin)
            ->get(route('admin.applications.index', ['location_id' => $location->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('applications.data', 1)
                ->where('applications.data.0.current_location', 'Sheffield'));
    }

    public function test_candidate_and_employer_cannot_access_application_workspaces(): void
    {
        $application = Application::factory()->create();

        $this->actingAs($this->makeCandidate())
            ->get(route('admin.applications.index'))
            ->assertForbidden();

        $this->actingAs($this->makeEmployer())
            ->put(route('admin.applications.screening', $application), [
                'decision' => ScreeningDecision::Suitable->value,
            ])
            ->assertForbidden();
    }

    public function test_admin_resume_download_still_works(): void
    {
        $admin = $this->makeAdmin();
        $path = 'workspace-resume.pdf';
        Storage::disk('resumes')->put($path, 'resume-bytes');
        $application = Application::factory()->create(['resume_path' => $path]);

        $this->actingAs($admin)
            ->get(route('admin.applications.resume', $application))
            ->assertOk();
    }
}
