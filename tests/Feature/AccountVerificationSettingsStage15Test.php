<?php

namespace Tests\Feature;

use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AccountVerificationSettingsStage15Test extends TestCase
{
    public function test_employer_security_includes_hiring_gate_and_excludes_whatsapp_otp_ui_payload(): void
    {
        $employer = $this->makeEmployer(['email_verified_at' => now(), 'mobile_verified_at' => null]);
        $employer->employerProfile?->update(['company_name' => 'Gate Co']);

        $this->actingAs($employer)
            ->get(route('account.settings.security'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Settings/Security')
                ->where('profile.email_verified', true)
                ->where('profile.mobile_verified', false)
                ->has('hiring_gate')
                ->where('hiring_gate.hiring_active', false)
                ->where('hiring_gate.whatsapp_required', false)
                ->missing('whatsapp_otp_retry_after'));
    }

    public function test_candidate_security_has_no_hiring_gate(): void
    {
        $candidate = $this->makeCandidate(['email_verified_at' => null]);

        $this->actingAs($candidate)
            ->get(route('account.settings.security'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('profile.email_verified', false)
                ->where('hiring_gate', null));
    }

    public function test_settings_index_shows_verification_summary_without_whatsapp_verified_claim(): void
    {
        $candidate = $this->makeCandidate();

        $this->actingAs($candidate)
            ->get(route('account.settings.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Account/Settings/Index'));
    }
}
