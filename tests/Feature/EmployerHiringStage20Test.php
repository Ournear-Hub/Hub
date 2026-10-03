<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\Job;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EmployerHiringStage20Test extends TestCase
{
    private function eligibleEmployer()
    {
        return $this->makeEmployer([
            'email_verified_at' => now(),
            'mobile_verified_at' => now(),
        ]);
    }

    public function test_employer_hiring_dashboard_shows_command_center_before_full_activation(): void
    {
        $employer = $this->makeEmployer([
            'email_verified_at' => now(),
            'mobile_verified_at' => null,
        ]);
        $employer->employerProfile->update(['company_name' => 'Stage20 Co']);

        $this->actingAs($employer)
            ->get(route('account.hiring'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Hiring/Dashboard')
                ->where('gate.hiring_active', false)
                ->has('workspace')
                ->where('workspace.company_name', 'Stage20 Co'));
    }

    public function test_employer_nav_is_grouped_and_includes_talent_pool_routes(): void
    {
        $employer = $this->eligibleEmployer();

        $this->actingAs($employer)
            ->get(route('account.employer.jobs.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('employerNav', fn (Assert $nav) => $nav
                    ->where('0.group', 'HIRING')
                    ->where('0.href', '/account/hiring')
                    ->etc()));
    }

    public function test_employer_talent_pool_lists_owned_applications_only(): void
    {
        $employer = $this->eligibleEmployer();
        $other = $this->eligibleEmployer();
        $job = Job::factory()->create(['employer_id' => $employer->employerProfile->id]);
        $otherJob = Job::factory()->create(['employer_id' => $other->employerProfile->id]);
        $candidate = $this->makeCandidate();

        Application::factory()->create([
            'job_id' => $job->id,
            'user_id' => $candidate->id,
            'status' => ApplicationStatus::Shortlisted,
        ]);
        Application::factory()->create([
            'job_id' => $otherJob->id,
            'user_id' => $candidate->id,
            'status' => ApplicationStatus::Shortlisted,
        ]);

        $this->actingAs($employer)
            ->get(route('account.hiring.candidates', ['stage' => 'shortlisted']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Hiring/TalentPool/Index')
                ->has('applications.data', 1)
                ->where('filters.stage', 'shortlisted'));

        $this->actingAs($other)
            ->get(route('account.hiring.candidates'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('applications.data', 1));
    }

    public function test_employer_hiring_applications_index_is_scoped(): void
    {
        $employer = $this->eligibleEmployer();
        $job = Job::factory()->create(['employer_id' => $employer->employerProfile->id]);
        Application::factory()->count(2)->create(['job_id' => $job->id]);

        $this->actingAs($employer)
            ->get(route('account.hiring.applications'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Hiring/Applications/Index')
                ->has('applications.data', 2));
    }

    public function test_candidate_without_hiring_profile_cannot_open_talent_pool(): void
    {
        $candidate = $this->makeCandidate([
            'email_verified_at' => now(),
            'mobile_verified_at' => now(),
        ]);

        $this->actingAs($candidate)
            ->get(route('account.hiring.candidates'))
            ->assertForbidden();
    }
}
