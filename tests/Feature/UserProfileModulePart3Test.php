<?php

namespace Tests\Feature;

use App\Models\EmployerProfile;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UserProfileModulePart3Test extends TestCase
{
    public function test_candidate_profile_marks_mobile_optional_until_hiring_is_activated(): void
    {
        $candidate = $this->makeCandidate([
            'email_verified_at' => now(),
            'mobile_verified_at' => null,
        ]);

        $this->actingAs($candidate)
            ->get(route('account.profile.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Profile')
                ->where('profile.verification_context.mobile_required', false)
                ->where('profile.verifications.email', true)
                ->where('profile.verifications.mobile', false));

        EmployerProfile::query()->create([
            'user_id' => $candidate->id,
            'company_name' => 'Side Hire Co',
        ]);

        $this->actingAs($candidate->fresh())
            ->get(route('account.profile.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('profile.verification_context.mobile_required', true)
                ->where('profile.hiring_links.0.label', 'Hiring dashboard')
                ->where('profile.hiring_links.0.href', '/account/hiring'));
    }

    public function test_employer_profile_requires_mobile_verification_context(): void
    {
        $employer = $this->makeEmployer([
            'email_verified_at' => now(),
            'mobile_verified_at' => null,
        ]);

        $this->actingAs($employer)
            ->get(route('account.profile.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Profile')
                ->where('role', 'employer')
                ->where('profile.verification_context.mobile_required', true)
                ->has('profile.modules.company'));
    }

    public function test_employer_company_profile_omits_public_company_page_control_fields(): void
    {
        $employer = $this->makeEmployer(['email_verified_at' => now()]);

        $this->actingAs($employer)
            ->get(route('account.profile.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('role', 'employer')
                ->where('lookups.public_share_fields', []));
    }
}
