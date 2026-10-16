<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Job;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * REG-BRW-004 — employer job applicant screening comparison (server-authorized).
 */
class EmployerApplicantCompareTest extends TestCase
{
    public function test_employer_can_load_compare_panel_for_own_job_applicants(): void
    {
        $employer = $this->makeEmployer();
        $job = Job::factory()->create([
            'employer_id' => $employer->employerProfile->id,
            'title' => 'Compare Pipeline Role',
        ]);

        $first = Application::factory()->create([
            'job_id' => $job->id,
            'full_name' => 'Employer Compare Applicant One',
            'user_id' => null,
        ]);
        $second = Application::factory()->create([
            'job_id' => $job->id,
            'full_name' => 'Employer Compare Applicant Two',
            'user_id' => null,
        ]);

        $this->actingAs($employer)
            ->get(route('account.employer.jobs.applicants', [
                'job' => $job,
                'compare' => $first->id.','.$second->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Employer/Applicants/Index')
                ->where('compare.open', true)
                ->has('compare.applications', 2)
                ->where('compare.applications.0.name', 'Employer Compare Applicant One')
                ->where('compare.applications.1.name', 'Employer Compare Applicant Two')
                ->where('compare.applications.0.resume_url', fn ($url) => $url === null || ! str_contains((string) $url, '..'))
                ->missing('compare.applications.0.resume_path'));
    }

    public function test_invalid_or_foreign_application_ids_are_rejected(): void
    {
        $owner = $this->makeEmployer();
        $other = $this->makeEmployer();
        $ownedJob = Job::factory()->create(['employer_id' => $owner->employerProfile->id]);
        $foreignJob = Job::factory()->create(['employer_id' => $other->employerProfile->id]);
        $ownedApplication = Application::factory()->create(['job_id' => $ownedJob->id]);
        $foreignApplication = Application::factory()->create(['job_id' => $foreignJob->id]);

        $this->actingAs($owner)
            ->from(route('account.employer.jobs.applicants', $ownedJob))
            ->get(route('account.employer.jobs.applicants', [
                'job' => $ownedJob,
                'compare' => $ownedApplication->id.','.$foreignApplication->id,
            ]))
            ->assertSessionHasErrors('compare');

        $this->actingAs($owner)
            ->get(route('account.employer.jobs.applicants', [
                'job' => $ownedJob,
                'compare' => (string) $ownedApplication->id,
            ]))
            ->assertSessionHasErrors('compare');

        $this->actingAs($other)
            ->get(route('account.employer.jobs.applicants', [
                'job' => $ownedJob,
                'compare' => $ownedApplication->id.','.Application::factory()->create(['job_id' => $ownedJob->id])->id,
            ]))
            ->assertForbidden();
    }

    public function test_compare_uses_resolved_candidate_display_name(): void
    {
        $employer = $this->makeEmployer();
        $job = Job::factory()->create(['employer_id' => $employer->employerProfile->id]);

        $guest = Application::factory()->create([
            'job_id' => $job->id,
            'full_name' => 'Snapshot Applicant Name',
            'user_id' => null,
        ]);

        $user = $this->makeCandidate(['name' => 'Profile Backed Applicant']);
        $profileBacked = Application::factory()->create([
            'job_id' => $job->id,
            'user_id' => $user->id,
            'full_name' => '',
        ]);

        $linked = $this->makeCandidate(['name' => 'Account Display Name']);
        $snapshotPreferred = Application::factory()->create([
            'job_id' => $job->id,
            'user_id' => $linked->id,
            'full_name' => 'Application Snapshot Name',
        ]);

        $this->actingAs($employer)
            ->get(route('account.employer.jobs.applicants', [
                'job' => $job,
                'compare' => $guest->id.','.$profileBacked->id.','.$snapshotPreferred->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('compare.applications.0.name', 'Snapshot Applicant Name')
                ->where('compare.applications.1.name', 'Profile Backed Applicant')
                ->where('compare.applications.2.name', 'Application Snapshot Name'));
    }

    public function test_compare_preserves_job_applicant_list_filters(): void
    {
        $employer = $this->makeEmployer();
        $job = Job::factory()->create(['employer_id' => $employer->employerProfile->id]);
        $alpha = Application::factory()->create(['job_id' => $job->id, 'full_name' => 'Alpha Compare']);
        $beta = Application::factory()->create(['job_id' => $job->id, 'full_name' => 'Beta Compare']);

        $this->actingAs($employer)
            ->get(route('account.employer.jobs.applicants', [
                'job' => $job,
                'q' => 'Alpha',
                'compare' => $alpha->id.','.$beta->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.q', 'Alpha')
                ->where('compare.open', true)
                ->has('compare.applications', 2));
    }
}
