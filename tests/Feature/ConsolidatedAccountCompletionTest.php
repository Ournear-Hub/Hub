<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ConsolidatedAccountCompletionTest extends TestCase
{
    public function test_register_page_uses_unified_account_signup_tab(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Account/Register'));
    }

    public function test_candidate_contact_dashboard_page_loads(): void
    {
        $candidate = $this->makeCandidate(['email_verified_at' => null]);

        $this->actingAs($candidate)
            ->get(route('account.contact'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Contact')
                ->where('showEmployerEnquiry', false)
                ->has('content.contact'));
    }

    public function test_login_sets_email_verification_reminder_for_unverified_candidates(): void
    {
        $user = $this->makeCandidate([
            'username' => 'reminder-user',
            'email' => 'reminder@example.test',
            'email_verified_at' => null,
        ]);

        $this->post(route('login.store'), [
            'login' => 'reminder-user',
            'password' => 'password',
        ])->assertRedirect(route('account.home'));

        $this->actingAs($user->fresh())
            ->get(route('account.settings.verification'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('emailVerificationReminder.email', 'reminder@example.test'));
    }

    public function test_dashboard_next_action_uses_updated_profile_completion_copy(): void
    {
        $candidate = $this->makeCandidate([
            'email_verified_at' => now(),
            'bio' => null,
            'mobile' => null,
            'location' => null,
            'photo_path' => null,
        ]);

        $this->actingAs($candidate)
            ->get(route('account.home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('dashboard.next_action.title', 'Complete Your Personal & Professional Information')
                ->where('dashboard.next_action.cta', 'Complete Your Profile'));
    }

    public function test_guest_verification_link_logs_in_and_verifies_account(): void
    {
        Notification::fake();
        $user = $this->makeCandidate(['email' => 'guest-verify@example.test', 'email_verified_at' => null]);
        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);

        $this->assertGuest('account');

        $this->get($url)
            ->assertRedirect()
            ->assertSessionHas('success', 'Your email has been verified successfully.');

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_account_settings_account_saves_whatsapp_once(): void
    {
        $candidate = $this->makeCandidate(['email_verified_at' => now()]);

        $this->actingAs($candidate)
            ->from(route('account.settings.account'))
            ->put(route('account.settings.account.update'), [
                'username' => $candidate->username,
                'mobile' => $candidate->mobile ?? '+919999999999',
                'whatsapp_number' => '+919876543210',
                'preferred_language' => 'en',
            ])
            ->assertRedirect();

        $this->assertSame('+919876543210', $candidate->fresh()->whatsapp_number);
    }
}
