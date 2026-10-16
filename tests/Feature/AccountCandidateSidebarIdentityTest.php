<?php

namespace Tests\Feature;

use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AccountCandidateSidebarIdentityTest extends TestCase
{
    public function test_candidate_settings_shows_profile_payload_with_ournear_id_context(): void
    {
        $candidate = $this->makeCandidate(['username' => 'sidebar-id-test']);

        $this->actingAs($candidate)
            ->get(route('account.settings.account'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Settings/Account')
                ->has('profile')
                ->where('profile.username', 'sidebar-id-test'));
    }

    public function test_candidate_applications_page_does_not_require_separate_identity_leak(): void
    {
        $candidate = $this->makeCandidate();

        $this->actingAs($candidate)
            ->get(route('account.applications.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Applications')
                ->has('summary.total')
                ->has('summary.in_progress'));
    }
}
