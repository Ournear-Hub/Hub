<?php

namespace Tests\Feature;

use App\Enums\PreferredLanguage;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * P0 unified account + profile persistence (2026-10-12 functional completion).
 * NOT RUN — deferred by product owner.
 */
class UnifiedAccountFunctionalCompletionTest extends TestCase
{
    public function test_owner_can_view_full_profile_when_public_sharing_is_disabled(): void
    {
        $candidate = $this->makeCandidate([
            'username' => 'owner-preview',
            'bio' => 'Owner bio',
            'email_verified_at' => now(),
        ]);
        $candidate->candidateProfile?->update(['current_designation' => 'Analyst']);

        $this->actingAs($candidate)
            ->put(route('account.profile.privacy.update'), ['enabled' => false])
            ->assertRedirect();

        $this->actingAs($candidate)
            ->get(route('profiles.show', ['user' => 'owner-preview']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Profiles/Show')
                ->where('viewer.is_owner', true)
                ->where('profile.share_enabled', false)
                ->where('profile.bio', 'Owner bio')
                ->where('profile.designation', 'Analyst'));

        $stranger = $this->makeCandidate(['username' => 'stranger-preview']);
        $this->actingAs($stranger)
            ->get(route('profiles.show', ['user' => 'owner-preview']))
            ->assertNotFound();
    }

    public function test_hiring_enabled_candidate_personal_save_does_not_clear_company_profile(): void
    {
        $candidate = $this->makeCandidate([
            'username' => 'dual-save',
            'email_verified_at' => now(),
            'mobile_verified_at' => now(),
        ]);

        $this->actingAs($candidate)
            ->post(route('account.hiring.activate'), $this->hiringActivationPayload(['company_name' => 'Dual Save Co']))
            ->assertRedirect();

        $candidate->refresh();
        $employerProfile = $candidate->employerProfile;
        $this->assertNotNull($employerProfile);
        $employerProfile->update(['about_company' => 'Keep this about text']);

        $this->actingAs($candidate)
            ->put(route('account.profile.update'), [
                'name' => 'Dual Save Name',
                'username' => 'dual-save',
                'preferred_language' => PreferredLanguage::English->value,
                'bio' => 'Updated personal bio',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $candidate->refresh();
        $this->assertSame('Dual Save Name', $candidate->name);
        $this->assertSame('Updated personal bio', $candidate->bio);
        $this->assertSame('Dual Save Co', $candidate->employerProfile?->company_name);
        $this->assertSame('Keep this about text', $candidate->employerProfile?->about_company);
    }

    public function test_one_identity_accesses_candidate_home_and_hiring_workspace(): void
    {
        $candidate = $this->makeCandidate([
            'username' => 'dual-nav',
            'password' => Hash::make('Password1!'),
            'email_verified_at' => now(),
            'mobile_verified_at' => now(),
        ]);

        $this->post(route('login.store'), [
            'login' => 'dual-nav',
            'password' => 'Password1!',
        ])->assertRedirect(route('account.home'));

        $this->actingAs($candidate)
            ->get(route('account.home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Account/Home'));

        $this->actingAs($candidate)
            ->post(route('account.hiring.activate'), $this->hiringActivationPayload(['company_name' => 'Dual Nav Co']))
            ->assertRedirect();

        $this->actingAs($candidate)
            ->get(route('account.hiring'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Account/Hiring/Dashboard'));

        $this->actingAs($candidate)
            ->get(route('account.profile.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Profile')
                ->where('role', 'candidate')
                ->where('profile.public_profile_url', route('profiles.show', ['user' => 'dual-nav'], false)));
    }
}
