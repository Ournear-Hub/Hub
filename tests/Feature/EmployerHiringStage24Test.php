<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Job;
use App\Models\User;
use App\Support\EmployerVisibleCandidate;
use App\Support\QaHiringFixtures;
use Illuminate\Support\Facades\Artisan;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EmployerHiringStage24Test extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('qa:hiring-fixtures');
    }

    private function employerA(): User
    {
        return User::query()->where('email', QaHiringFixtures::EMPLOYER_A_EMAIL)->firstOrFail();
    }

    private function employerB(): User
    {
        return User::query()->where('email', QaHiringFixtures::EMPLOYER_B_EMAIL)->firstOrFail();
    }

    private function candidate(): User
    {
        return User::query()->where('email', QaHiringFixtures::CANDIDATE_EMAIL)->firstOrFail();
    }

    private function publishedJobA(): Job
    {
        return Job::query()->where('slug', QaHiringFixtures::SLUG_PUBLISHED_A)->firstOrFail();
    }

    private function applicationOnJobA(): Application
    {
        $candidate = $this->candidate();

        return Application::query()
            ->where('job_id', $this->publishedJobA()->id)
            ->where('user_id', $candidate->id)
            ->firstOrFail();
    }

    public function test_employer_a_talent_pool_lists_fixture_candidate(): void
    {
        $this->actingAs($this->employerA())
            ->get(route('account.hiring.candidates'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Hiring/TalentPool/Index')
                ->has('applications.data', 1)
                ->where('applications.data.0.name', 'QA Candidate')
                ->where('company_name', QaHiringFixtures::COMPANY_A));
    }

    public function test_talent_pool_stage_filters_use_application_status_mapping(): void
    {
        $employer = $this->employerA();

        $this->actingAs($employer)
            ->get(route('account.hiring.candidates', ['stage' => 'shortlisted']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('applications.data', 0));

        $this->actingAs($employer)
            ->get(route('account.hiring.candidates'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('applications.data', 1));
    }

    public function test_talent_pool_contact_uses_application_snapshot_not_live_account_fields(): void
    {
        $employer = $this->employerA();
        $application = $this->applicationOnJobA();
        $snapshotEmail = $application->email;
        $snapshotMobile = $application->mobile;

        $candidate = $this->candidate();
        $candidate->forceFill([
            'email' => 'live-account-only@example.test',
            'mobile' => '+919999999999',
        ])->save();

        $this->actingAs($employer)
            ->get(route('account.hiring.candidates'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('applications.data.0.application_contact.email', $snapshotEmail)
                ->where('applications.data.0.application_contact.mobile', $snapshotMobile)
                ->missing('applications.data.0.email'));

        $payload = EmployerVisibleCandidate::fromApplication($application->fresh(['user.candidateProfile', 'job']));
        $this->assertSame($snapshotEmail, $payload['application_contact']['email']);
        $this->assertNotSame('live-account-only@example.test', $payload['application_contact']['email']);
    }

    public function test_employer_b_cannot_access_employer_a_application_or_talent_pool_candidate(): void
    {
        $job = $this->publishedJobA();
        $application = $this->applicationOnJobA();

        $this->actingAs($this->employerB())
            ->get(route('account.employer.jobs.application', [$job, $application]))
            ->assertForbidden();

        $this->actingAs($this->employerB())
            ->get(route('account.hiring.candidates'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('applications.data', 0));
    }

    public function test_candidate_cannot_access_employer_talent_pool_route(): void
    {
        $this->actingAs($this->candidate())
            ->get(route('account.hiring.candidates'))
            ->assertRedirect(route('account.hiring', ['destination' => '/account/hiring/candidates']));
    }

    public function test_employer_application_detail_has_no_candidate_owner_controls_in_props(): void
    {
        $job = $this->publishedJobA();
        $application = $this->applicationOnJobA();

        $this->actingAs($this->employerA())
            ->get(route('account.employer.jobs.application', [$job, $application]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Employer/Applications/Show')
                ->has('application.application_contact')
                ->missing('application.user')
                ->missing('application.account_settings'));
    }
}
