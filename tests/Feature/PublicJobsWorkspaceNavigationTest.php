<?php

namespace Tests\Feature;

use App\Models\Job;
use App\Models\SavedJob;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicJobsWorkspaceNavigationTest extends TestCase
{
    public function test_jobs_index_with_selected_job_keeps_full_public_listing(): void
    {
        $selected = Job::factory()->create(['title' => 'Selected Workspace Role']);
        $other = Job::factory()->create(['title' => 'Other Workspace Role']);

        $this->get(route('jobs.index', ['job' => $selected->slug]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Jobs/Index')
                ->has('jobs.data', 2)
                ->where('selected_job.slug', $selected->slug)
                ->where('jobs.data', function ($jobs) use ($selected, $other) {
                    $slugs = collect($jobs)->pluck('slug')->all();

                    return in_array($selected->slug, $slugs, true)
                        && in_array($other->slug, $slugs, true);
                }));
    }

    public function test_candidate_cannot_save_own_company_job(): void
    {
        $candidate = $this->makeCandidate([
            'email_verified_at' => now(),
            'mobile_verified_at' => now(),
        ]);

        $this->actingAs($candidate)
            ->post(route('account.hiring.activate'), $this->hiringActivationPayload([
                'company_name' => 'Self Co',
                'company_email' => $candidate->email,
            ]))
            ->assertRedirect(route('account.hiring'));

        $profile = $candidate->fresh()->employerProfile;
        $this->assertNotNull($profile);

        $job = Job::factory()->create([
            'employer_id' => $profile->id,
            'title' => 'Own Company Role',
        ]);

        $this->actingAs($candidate->fresh())
            ->from(route('jobs.index', ['job' => $job->slug]))
            ->post(route('account.saved-jobs.store', $job))
            ->assertSessionHasErrors('job');

        $this->assertSame(0, SavedJob::query()->where('job_id', $job->id)->count());
    }

    public function test_own_company_job_in_jobs_workspace_has_own_company_apply_state(): void
    {
        $candidate = $this->makeCandidate([
            'email_verified_at' => now(),
            'mobile_verified_at' => now(),
        ]);

        $this->actingAs($candidate)
            ->post(route('account.hiring.activate'), $this->hiringActivationPayload([
                'company_name' => 'Self Co',
                'company_email' => $candidate->email,
            ]))
            ->assertRedirect(route('account.hiring'));

        $profile = $candidate->fresh()->employerProfile;
        $this->assertNotNull($profile);

        $job = Job::factory()->create([
            'employer_id' => $profile->id,
        ]);

        $this->actingAs($candidate->fresh())
            ->get(route('jobs.index', ['job' => $job->slug]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('selected_job.apply_state', 'own_company')
                ->where('selected_job.slug', $job->slug));
    }
}
