<?php

namespace Tests\Feature;

use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DualProfileHeaderControlsTest extends TestCase
{
    public function test_employer_sees_company_and_personal_header_nav_on_public_and_account_pages(): void
    {
        $employer = $this->makeEmployer([
            'email_verified_at' => now(),
            'mobile_verified_at' => now(),
        ]);

        $this->actingAs($employer)
            ->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('headerAccountNav')
                ->has('companyHeaderNav')
                ->where('auth.user.employer_identity.name', fn ($name) => is_string($name) && $name !== ''));

        $this->actingAs($employer)
            ->get(route('account.home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('headerAccountNav')
                ->has('companyHeaderNav'));
    }

    public function test_dual_role_candidate_sees_both_header_nav_payloads_on_personal_dashboard(): void
    {
        $candidate = $this->makeCandidate([
            'email_verified_at' => now(),
        ]);

        $this->actingAs($candidate)
            ->post(route('account.hiring.activate'), [
                'company_name' => 'Dual Header Co',
                'company_phone' => '+919876543288',
                'company_email' => $candidate->email,
                'industry_id' => $this->industryId(),
            ]);

        $candidate = $candidate->fresh();
        $this->assertNotNull($candidate->employerProfile);

        $this->actingAs($candidate)
            ->get(route('account.home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('headerAccountNav')
                ->has('companyHeaderNav')
                ->where('auth.user.employer_identity.name', 'Dual Header Co'));

        $this->actingAs($candidate)
            ->get(route('account.company'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('headerAccountNav')
                ->has('companyHeaderNav'));
    }
}
