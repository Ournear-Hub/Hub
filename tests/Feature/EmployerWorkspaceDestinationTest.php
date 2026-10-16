<?php

namespace Tests\Feature;

use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EmployerWorkspaceDestinationTest extends TestCase
{
    public function test_hiring_activation_redirects_to_preserved_job_create_destination(): void
    {
        $candidate = $this->makeCandidate([
            'email_verified_at' => now(),
            'mobile_verified_at' => now(),
        ]);

        $this->actingAs($candidate)
            ->get(route('account.hiring', ['destination' => '/account/jobs/create']))
            ->assertOk();

        $this->actingAs($candidate)
            ->post(route('account.hiring.activate'), $this->hiringActivationPayload([
                'company_name' => 'Destination Co',
                'company_email' => $candidate->email,
            ]))
            ->assertRedirect('/account/jobs/create');

        $this->actingAs($candidate->fresh())
            ->get(route('account.employer.jobs.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Account/Employer/Jobs/Form'));
    }

    public function test_candidate_without_workspace_redirects_from_job_create_to_hiring_with_destination(): void
    {
        $candidate = $this->makeCandidate(['email_verified_at' => now()]);

        $this->actingAs($candidate)
            ->get(route('account.employer.jobs.create'))
            ->assertRedirect(route('account.hiring', ['destination' => '/account/jobs/create']));
    }

    public function test_recruitment_support_redirects_to_activation_when_workspace_missing(): void
    {
        $candidate = $this->makeCandidate(['email_verified_at' => now()]);

        $this->actingAs($candidate)
            ->get(route('account.recruitment-support.create'))
            ->assertRedirect(route('account.hiring', ['destination' => '/account/recruitment-support']));
    }

    public function test_activation_ignores_unsafe_external_destination(): void
    {
        $candidate = $this->makeCandidate([
            'email_verified_at' => now(),
            'mobile_verified_at' => now(),
        ]);

        $this->actingAs($candidate)
            ->get(route('account.hiring', ['destination' => 'https://evil.example/phish']));

        $this->actingAs($candidate)
            ->post(route('account.hiring.activate'), $this->hiringActivationPayload([
                'company_name' => 'Safe Redirect Co',
                'company_email' => $candidate->email,
            ]))
            ->assertRedirect(route('account.hiring'));
    }
}
