<?php

namespace Tests\Feature;

use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AccountVerificationSettingsStage15Test extends TestCase
{
    public function test_employer_verification_includes_hiring_gate_and_excludes_whatsapp_otp_ui_payload(): void
    {
        $employer = $this->makeEmployer(['email_verified_at' => now(), 'mobile_verified_at' => null]);
        $employer->employerProfile?->update(['company_name' => 'Gate Co']);

        $this->actingAs($employer)
            ->get(route('account.settings.verification'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Settings/Verification')
                ->where('profile.email_verified', true)
                ->where('profile.mobile_verified', false)
                ->has('hiring_gate')
                ->where('hiring_gate.hiring_active', false)
                ->where('hiring_gate.whatsapp_required', false)
                ->missing('whatsapp_otp_retry_after'));
    }

    public function test_candidate_verification_has_no_hiring_gate(): void
    {
        $candidate = $this->makeCandidate(['email_verified_at' => null]);

        $this->actingAs($candidate)
            ->get(route('account.settings.verification'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Settings/Verification')
                ->where('profile.email_verified', false)
                ->where('hiring_gate', null));
    }

    public function test_settings_index_redirects_to_account_tab(): void
    {
        $candidate = $this->makeCandidate();

        $this->actingAs($candidate)
            ->get(route('account.settings.index'))
            ->assertRedirect(route('account.settings.account'));
    }

    public function test_security_tab_renders_overview_without_hiring_gate(): void
    {
        $employer = $this->makeEmployer(['email_verified_at' => now(), 'mobile_verified_at' => null]);

        $this->actingAs($employer)
            ->get(route('account.settings.security'))
            ->assertRedirect(route('account.company'));
    }
}
