<?php

namespace Tests\Feature;

use App\Enums\EmployerNotificationEvent;
use App\Models\Application;
use App\Models\Job;
use App\Models\User;
use App\Support\QaHiringFixtures;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class QaHiringFixturesTest extends TestCase
{
    protected function tearDown(): void
    {
        $this->app->detectEnvironment(fn () => 'testing');
        parent::tearDown();
    }

    public function test_command_provisions_fixture_dataset_in_testing_environment(): void
    {
        $this->artisan('qa:hiring-fixtures')
            ->assertSuccessful()
            ->expectsOutputToContain('HIRING QA FIXTURES READY');

        $employerA = User::query()->where('email', QaHiringFixtures::EMPLOYER_A_EMAIL)->firstOrFail();
        $employerB = User::query()->where('email', QaHiringFixtures::EMPLOYER_B_EMAIL)->firstOrFail();
        $candidate = User::query()->where('email', QaHiringFixtures::CANDIDATE_EMAIL)->firstOrFail();

        $publishedA = Job::query()->where('slug', QaHiringFixtures::SLUG_PUBLISHED_A)->firstOrFail();
        $this->assertSame($employerA->employerProfile?->id, $publishedA->employer_id);

        $application = Application::query()
            ->where('user_id', $candidate->id)
            ->where('job_id', $publishedA->id)
            ->firstOrFail();

        $this->assertNotSame('', trim((string) $application->resume_path));
        $this->assertSame(1, $employerA->fresh()->notifications()
            ->where('data->event', EmployerNotificationEvent::ApplicationReceived->value)
            ->where('data->context->application_id', $application->id)
            ->count());
        $this->assertSame(0, $employerB->fresh()->notifications()
            ->where('data->event', EmployerNotificationEvent::ApplicationReceived->value)
            ->where('data->context->application_id', $application->id)
            ->count());
    }

    public function test_command_rejects_production_environment(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $this->artisan('qa:hiring-fixtures')
            ->assertFailed()
            ->expectsOutputToContain('production');
    }

    public function test_repeated_execution_does_not_duplicate_qa_users_or_jobs(): void
    {
        $this->artisan('qa:hiring-fixtures')->assertSuccessful();
        $this->artisan('qa:hiring-fixtures')->assertSuccessful();

        $this->assertSame(3, User::query()->whereIn('email', QaHiringFixtures::qaEmails())->count());
        $this->assertSame(3, Job::query()->whereIn('slug', QaHiringFixtures::qaJobSlugs())->count());

        $candidate = User::query()->where('email', QaHiringFixtures::CANDIDATE_EMAIL)->firstOrFail();
        $publishedA = Job::query()->where('slug', QaHiringFixtures::SLUG_PUBLISHED_A)->firstOrFail();

        $this->assertSame(1, Application::query()
            ->where('user_id', $candidate->id)
            ->where('job_id', $publishedA->id)
            ->count());
    }

    public function test_employer_b_job_is_isolated_from_employer_a_application(): void
    {
        Artisan::call('qa:hiring-fixtures');

        $employerB = User::query()->where('email', QaHiringFixtures::EMPLOYER_B_EMAIL)->firstOrFail();
        $publishedB = Job::query()->where('slug', QaHiringFixtures::SLUG_PUBLISHED_B)->firstOrFail();

        $this->assertSame($employerB->employerProfile?->id, $publishedB->employer_id);
        $this->assertSame(0, Application::query()->where('job_id', $publishedB->id)->count());
    }
}
