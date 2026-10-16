<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Job;
use App\Support\JobApplicationEligibility;
use Tests\TestCase;

class JobOwnCompanyApplicationTest extends TestCase
{
    public function test_candidate_cannot_apply_to_own_company_job(): void
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

        $candidate = $candidate->fresh();
        $job = $job->fresh();
        $this->assertSame($profile->id, $job->employer_id);
        $this->assertTrue(JobApplicationEligibility::candidateOwnsJobCompany($candidate, $job));

        $this->giveCandidateResume($candidate);

        $this->actingAs($candidate)
            ->from(route('jobs.show', $job))
            ->post(route('jobs.apply.store', $job))
            ->assertRedirect(route('jobs.show', $job))
            ->assertSessionHasErrors('job');

        $this->assertSame(0, Application::query()->where('job_id', $job->id)->count());
    }

    public function test_candidate_can_apply_to_unrelated_employer_job(): void
    {
        $owner = $this->makeEmployer([
            'email_verified_at' => now(),
            'mobile_verified_at' => now(),
        ]);
        $candidate = $this->makeCandidate(['email' => 'other@example.com']);

        $job = Job::factory()->create([
            'employer_id' => $owner->employerProfile->id,
            'title' => 'External Role',
        ]);

        $this->giveCandidateResume($candidate);

        $this->actingAs($candidate)
            ->post(route('jobs.apply.store', $job))
            ->assertRedirect(route('jobs.show', $job));

        $this->assertSame(1, Application::query()->where('job_id', $job->id)->count());
    }

    public function test_own_company_job_shows_own_company_apply_state(): void
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

        $candidate = $candidate->fresh();
        $job = $job->fresh();
        $this->assertTrue(JobApplicationEligibility::candidateOwnsJobCompany($candidate, $job));

        $this->actingAs($candidate)
            ->get(route('jobs.show', $job))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('job.apply_state', 'own_company'));
    }
}
