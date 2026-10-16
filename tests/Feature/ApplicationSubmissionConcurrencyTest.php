<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Job;
use App\Models\User;
use App\Support\QaHiringFixtures;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

class ApplicationSubmissionConcurrencyTest extends TestCase
{
    public function test_concurrent_apply_posts_create_at_most_one_application(): void
    {
        if (! $this->app->environment('local')) {
            $this->markTestSkipped('Concurrent HTTP probe runs against local dev server only.');
        }

        $candidate = User::query()->where('email', QaHiringFixtures::CANDIDATE_EMAIL)->first();
        if ($candidate === null) {
            $this->markTestSkipped('Run php artisan qa:hiring-fixtures first.');
        }

        $provision = Process::path(base_path())
            ->run(['php', 'scripts/qa-provision-journey-apply-job.php']);
        if (! $provision->successful()) {
            $this->markTestSkipped('Could not provision journey apply job: '.$provision->errorOutput());
        }

        $slug = null;
        foreach (explode("\n", $provision->output()) as $line) {
            if (str_starts_with($line, 'slug=')) {
                $slug = trim(substr($line, 5));
            }
        }
        $this->assertNotEmpty($slug);

        $before = Application::query()
            ->where('user_id', $candidate->id)
            ->whereHas('job', fn ($q) => $q->where('slug', $slug))
            ->count();

        $probe = Process::path(base_path())
            ->env([
                'OURNEAR_CONCURRENT_APPLY_BASE_URL' => 'http://127.0.0.1:8000',
            ])
            ->run(['php', 'scripts/qa-concurrent-apply-local.php', $slug]);

        $this->assertTrue(
            $probe->successful(),
            "Concurrent apply probe failed:\n".$probe->errorOutput()."\n".$probe->output(),
        );

        $after = Application::query()
            ->where('user_id', $candidate->id)
            ->whereHas('job', fn ($q) => $q->where('slug', $slug))
            ->count();

        $this->assertLessThanOrEqual($before + 1, $after);
        $this->assertSame($before + 1, $after, 'Exactly one new application should be created.');

        $application = Application::query()
            ->where('user_id', $candidate->id)
            ->whereHas('job', fn ($q) => $q->where('slug', $slug))
            ->sole();
        $application->loadMissing('job.employer.user');
        $employer = $application->job->employer?->user;
        if ($employer !== null) {
            $fingerprint = 'employer_application_received:application_id='.$application->id.'|job_id='.$application->job_id;
            $this->assertSame(
                1,
                $employer->notifications()->where('data->fingerprint', $fingerprint)->count(),
                'Employer should receive at most one in-app notification per application.',
            );
        }
    }

    public function test_candidate_can_apply_to_two_different_jobs(): void
    {
        $candidate = $this->makeCandidate(['email' => 'concurrency-two-jobs@example.com']);
        $this->giveCandidateResume($candidate);
        $jobA = Job::factory()->create();
        $jobB = Job::factory()->create();

        $this->applyAsCandidate($candidate, $jobA);
        $this->applyAsCandidate($candidate, $jobB);

        $this->assertSame(1, Application::query()->where('job_id', $jobA->id)->count());
        $this->assertSame(1, Application::query()->where('job_id', $jobB->id)->count());
    }

    public function test_duplicate_guard_and_unique_email_remain_enforced(): void
    {
        $candidate = $this->makeCandidate(['email' => 'concurrency-guard@example.com']);
        $job = Job::factory()->create();
        $this->giveCandidateResume($candidate);
        $this->applyAsCandidate($candidate, $job);

        $this->actingAs($candidate)
            ->from(route('jobs.show', $job))
            ->post(route('jobs.apply.store', $job))
            ->assertRedirect(route('jobs.show', $job))
            ->assertSessionHasErrors('job');

        $this->assertSame(1, Application::query()->where('job_id', $job->id)->count());
    }
}
