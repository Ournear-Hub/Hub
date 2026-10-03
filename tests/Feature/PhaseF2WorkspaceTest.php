<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\Job;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PhaseF2WorkspaceTest extends TestCase
{
    public function test_dashboard_exposes_platform_and_recruitment_metrics(): void
    {
        $admin = $this->makeAdmin();
        $this->makeCandidate(['email_verified_at' => now()]);
        Job::factory()->create(['active' => true]);
        Application::factory()->create(['status' => ApplicationStatus::New]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard')
                ->where('stats.platform.total_users.value', fn ($value) => is_int($value) && $value >= 2)
                ->where('stats.platform.total_users.href', '/admin/users')
                ->where('stats.platform.new_users.href', '/admin/users?segment=new')
                ->where('stats.platform.verified_users.href', '/admin/users?segment=verified')
                ->where('stats.recruitment.active_jobs.value', fn ($value) => is_int($value) && $value >= 1)
                ->where('stats.recruitment.applications.href', '/admin/applications')
                ->where('stats.recruitment.candidates.href', '/admin/candidates')
                ->where('stats.recruitment.screening_pending.href', '/admin/screening?screening_decision=pending')
                ->has('stats.recruitment_pipeline', 7)
                ->where('stats.recruitment_pipeline.0.label', 'Applications')
                ->where('stats.recruitment_pipeline.6.label', 'Joined')
                ->has('stats.pending_actions'));
    }

    public function test_jobs_workspace_filters_by_employer_and_exposes_related_links(): void
    {
        $admin = $this->makeAdmin();
        $employer = $this->makeEmployer();
        $profile = $employer->employerProfile;
        $this->assertNotNull($profile);
        $profile->update(['company_name' => 'BuildCo Ltd']);

        $matching = Job::factory()->create([
            'title' => 'Site Engineer',
            'employer_id' => $profile->id,
        ]);
        Job::factory()->create(['title' => 'Chef']);

        $this->actingAs($admin)
            ->get(route('admin.jobs.index', ['employer_id' => $profile->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Jobs/Index')
                ->has('jobs.data', 1)
                ->where('jobs.data.0.business_id', $matching->business_id)
                ->where('jobs.data.0.employer.company_name', 'BuildCo Ltd')
                ->where('filters.employer_id', (string) $profile->id));

        $this->actingAs($admin)
            ->get(route('admin.jobs.index', ['job' => $matching->slug]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('selected.business_id', $matching->business_id)
                ->where('selected.links.applications', '/admin/applications?job_id='.$matching->id)
                ->where('selected.links.candidates', '/admin/candidates?job_id='.$matching->id)
                ->where('selected.links.screening', '/admin/screening?job_id='.$matching->id)
                ->where('selected.links.shortlisted', '/admin/shortlisted?job_id='.$matching->id)
                ->where('selected.links.submissions', '/admin/submissions?job_id='.$matching->id)
                ->where('selected.links.placements', '/admin/placements?job_id='.$matching->id)
                ->where('selected.business_id', fn ($id) => is_string($id) && str_starts_with($id, 'ONT-J-')));
    }

    public function test_candidates_workspace_filters_by_job_and_keeps_user_relationship(): void
    {
        $admin = $this->makeAdmin();
        $candidate = $this->makeCandidate(['name' => 'Asha Candidate', 'email' => 'asha-f2@example.com']);
        $other = $this->makeCandidate(['name' => 'Other Person']);
        $profile = $candidate->candidateProfile;
        $this->assertNotNull($profile);
        $job = Job::factory()->create(['title' => 'Site Engineer']);
        Application::factory()->create([
            'job_id' => $job->id,
            'user_id' => $candidate->id,
            'full_name' => 'Asha Candidate',
            'email' => 'asha-f2@example.com',
        ]);
        Application::factory()->create([
            'user_id' => $other->id,
            'full_name' => 'Other Person',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.candidates.index', ['job_id' => $job->id, 'candidate' => $profile->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Candidates/Index')
                ->has('candidates.data', 1)
                ->where('candidates.data.0.business_id', $profile->business_id)
                ->where('candidates.data.0.user_id', $candidate->id)
                ->where('selected.user_id', $candidate->id)
                ->where('selected.user_business_id', $candidate->business_id)
                ->where('selected.account_status', AccountStatus::Active->value)
                ->where('recruitment.jobs.0.id', $job->id)
                ->where('selected.links.applications', '/admin/applications?candidate='.$profile->id)
                ->where('selected.links.screening', '/admin/screening?candidate='.$profile->id)
                ->where('selected.business_id', fn ($id) => is_string($id) && str_starts_with($id, 'ONT-C-')));
    }

    public function test_applications_filter_by_employer_and_resume_view_stays_separate_from_download(): void
    {
        $admin = $this->makeAdmin();
        $employer = $this->makeEmployer();
        $profile = $employer->employerProfile;
        $this->assertNotNull($profile);
        $job = Job::factory()->create(['employer_id' => $profile->id]);
        $path = 'f2-resume.pdf';
        Storage::disk('resumes')->put($path, 'resume-bytes');
        $match = Application::factory()->create([
            'job_id' => $job->id,
            'full_name' => 'Priya Applicant',
            'resume_path' => $path,
            'status' => ApplicationStatus::New,
        ]);
        Application::factory()->create(['full_name' => 'Other Person']);

        $this->actingAs($admin)
            ->get(route('admin.applications.index', ['employer_id' => $profile->id, 'application' => $match->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Applications/Index')
                ->has('applications.data', 1)
                ->where('applications.data.0.business_id', $match->business_id)
                ->where('applications.data.0.employer.id', $profile->id)
                ->where('selected.links.job', '/admin/jobs?job='.$job->slug)
                ->where('selected.resume_view_url', route('admin.applications.resume', [
                    'application' => $match,
                    'disposition' => 'inline',
                ]))
                ->where('selected.business_id', fn ($id) => is_string($id) && str_starts_with($id, 'ONT-A-')));

        $download = $this->actingAs($admin)->get(route('admin.applications.resume', $match));
        $download->assertOk();
        $this->assertStringContainsString('attachment', (string) $download->headers->get('content-disposition'));

        $view = $this->actingAs($admin)->get(route('admin.applications.resume', [
            'application' => $match,
            'disposition' => 'inline',
        ]));
        $view->assertOk();
        $this->assertStringContainsString('inline', (string) $view->headers->get('content-disposition'));
    }
}
