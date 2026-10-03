<?php

namespace Tests\Feature;

use App\Enums\EmployerNotificationEvent;
use App\Models\Application;
use App\Models\Job;
use App\Models\User;
use App\Support\QaHiringFixtures;
use Illuminate\Support\Facades\Artisan;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EmployerHiringStage25Test extends TestCase
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

    private function fixtureApplication(): Application
    {
        return Application::query()
            ->where('job_id', $this->publishedJobA()->id)
            ->where('user_id', $this->candidate()->id)
            ->firstOrFail();
    }

    public function test_employer_a_hiring_applications_list_includes_fixture_application(): void
    {
        $application = $this->fixtureApplication();

        $this->actingAs($this->employerA())
            ->get(route('account.hiring.applications'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Hiring/Applications/Index')
                ->where('company_name', QaHiringFixtures::COMPANY_A)
                ->has('applications.data', 1)
                ->where('applications.data.0.application_id', $application->id)
                ->where('applications.data.0.job.title', QaHiringFixtures::TITLE_PUBLISHED_A)
                ->where('applications.data.0.status', 'New'));
    }

    public function test_employer_b_hiring_applications_list_is_empty(): void
    {
        $this->actingAs($this->employerB())
            ->get(route('account.hiring.applications'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('applications.data', 0)
                ->where('company_name', QaHiringFixtures::COMPANY_B));
    }

    public function test_employer_application_detail_uses_contact_snapshot_and_read_only_actions_copy(): void
    {
        $job = $this->publishedJobA();
        $application = $this->fixtureApplication();
        $snapshotEmail = $application->email;

        $this->candidate()->forceFill(['email' => 'different-live@example.test'])->save();

        $this->actingAs($this->employerA())
            ->get(route('account.employer.jobs.application', [$job, $application]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Employer/Applications/Show')
                ->where('application.application_contact.email', $snapshotEmail)
                ->where('application.has_resume', true)
                ->missing('application.user'));
    }

    public function test_application_resume_snapshot_authorization(): void
    {
        $application = $this->fixtureApplication();

        $this->actingAs($this->employerA())
            ->get(route('account.employer.applications.resume', $application))
            ->assertOk();

        $this->actingAs($this->employerB())
            ->get(route('account.employer.applications.resume', $application))
            ->assertForbidden();

        $this->actingAs($this->candidate())
            ->get(route('account.employer.applications.resume', $application))
            ->assertForbidden();
    }

    public function test_employer_cannot_update_application_status_via_account_routes(): void
    {
        $job = $this->publishedJobA();
        $application = $this->fixtureApplication();

        $this->actingAs($this->employerA())
            ->put(route('account.employer.jobs.application', [$job, $application]), [
                'status' => 'Shortlisted',
            ])
            ->assertStatus(405);

        $this->actingAs($this->employerA())
            ->patch('/account/jobs/'.$job->slug.'/applications/'.$application->id.'/status', [
                'status' => 'Shortlisted',
            ])
            ->assertNotFound();
    }

    public function test_notification_read_redirects_employer_a_to_own_application(): void
    {
        $employer = $this->employerA();
        $application = $this->fixtureApplication();
        $notification = $employer->notifications()
            ->where('data->event', EmployerNotificationEvent::ApplicationReceived->value)
            ->where('data->context->application_id', $application->id)
            ->firstOrFail();

        $expected = route('account.employer.jobs.application', [$this->publishedJobA(), $application], false);
        $this->assertSame($expected, $notification->data['url']);

        $this->actingAs($employer)
            ->post(route('account.notifications.read', [
                'notification' => $notification->id,
                'redirect' => 1,
            ]))
            ->assertRedirect($expected);
    }

    public function test_employer_b_cannot_use_employer_a_notification_or_application(): void
    {
        $application = $this->fixtureApplication();
        $job = $this->publishedJobA();
        $notification = $this->employerA()->notifications()->firstOrFail();

        $this->actingAs($this->employerB())
            ->post(route('account.notifications.read', $notification))
            ->assertForbidden();

        $this->actingAs($this->employerB())
            ->get(route('account.employer.jobs.application', [$job, $application]))
            ->assertForbidden();
    }

    public function test_candidate_can_view_own_application_but_not_employer_application_route(): void
    {
        $application = $this->fixtureApplication();
        $job = $this->publishedJobA();

        $this->actingAs($this->candidate())
            ->get(route('account.applications.show', $application))
            ->assertOk();

        $this->actingAs($this->candidate())
            ->get(route('account.employer.jobs.application', [$job, $application]))
            ->assertForbidden();
    }
}
