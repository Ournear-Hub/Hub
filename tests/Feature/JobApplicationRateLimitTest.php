<?php

namespace Tests\Feature;

use App\Models\Job;
use Tests\TestCase;

class JobApplicationRateLimitTest extends TestCase
{
    public function test_many_distinct_candidates_can_apply_in_one_test_run_without_shared_ip_bucket(): void
    {
        $employer = $this->makeEmployer(['email_verified_at' => now(), 'mobile_verified_at' => now()]);
        $job = Job::factory()->create(['employer_id' => $employer->employerProfile->id]);

        for ($i = 0; $i < 12; $i++) {
            $candidate = $this->makeCandidate([
                'email_verified_at' => now(),
                'mobile_verified_at' => now(),
            ]);
            $this->applyAsCandidate($candidate, $job);
        }

        $this->assertSame(12, $job->applications()->count());
    }
}
