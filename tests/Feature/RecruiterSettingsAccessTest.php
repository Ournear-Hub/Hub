<?php

namespace Tests\Feature;

use Tests\TestCase;

class RecruiterSettingsAccessTest extends TestCase
{
    public function test_employer_role_is_redirected_from_personal_account_settings_to_company_profile(): void
    {
        $user = $this->makeEmployer(['email_verified_at' => now()]);

        $this->actingAs($user, 'account')
            ->get('/account/settings/account')
            ->assertRedirect(route('account.company'));

        $this->actingAs($user, 'account')
            ->get('/account/settings/password')
            ->assertRedirect(route('account.company'));
    }

    public function test_candidate_can_still_open_personal_account_settings(): void
    {
        $user = $this->makeCandidate(['email_verified_at' => now()]);

        $this->actingAs($user, 'account')
            ->get('/account/settings/account')
            ->assertOk();
    }
}
