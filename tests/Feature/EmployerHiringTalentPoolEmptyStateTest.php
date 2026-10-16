<?php

namespace Tests\Feature;

use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EmployerHiringTalentPoolEmptyStateTest extends TestCase
{
    public function test_job_talent_pool_empty_state_does_not_offer_create_first_job(): void
    {
        $employer = $this->makeEmployer();

        $this->actingAs($employer)
            ->get(route('account.hiring.candidates'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Hiring/TalentPool/Index')
                ->where('applications.total', 0));

        $html = $this->actingAs($employer)
            ->get(route('account.hiring.candidates'))
            ->getContent();

        $this->assertIsString($html);
        $this->assertStringNotContainsString('Create your first job', $html);
        $this->assertStringContainsString('Assigned Candidates', $html);
    }
}
