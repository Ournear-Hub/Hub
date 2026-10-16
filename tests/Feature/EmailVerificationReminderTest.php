<?php

namespace Tests\Feature;

use App\Notifications\VerifyAccountEmailNotification;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EmailVerificationReminderTest extends TestCase
{
    public function test_unverified_candidate_sees_reminder_after_login(): void
    {
        $user = $this->makeCandidate([
            'username' => 'needs-verify',
            'email_verified_at' => null,
        ]);

        $this->post(route('login.store'), [
            'login' => 'needs-verify',
            'password' => 'password',
        ])->assertRedirect(route('account.home'));

        $this->get(route('account.settings.verification'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('emailVerificationReminder.email', $user->email));
    }

    public function test_dismiss_reminder_does_not_verify_email(): void
    {
        $user = $this->makeCandidate(['email_verified_at' => null]);

        $this->post(route('login.store'), [
            'login' => $user->username,
            'password' => 'password',
        ]);

        $this->post(route('account.email-verification-reminder.dismiss'))
            ->assertRedirect();

        $this->assertNull($user->fresh()->email_verified_at);

        $this->get(route('account.home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('emailVerificationReminder', null));
    }

    public function test_reminder_returns_after_subsequent_login_while_email_unverified(): void
    {
        $user = $this->makeCandidate(['email_verified_at' => null]);

        $this->post(route('login.store'), [
            'login' => $user->username,
            'password' => 'password',
        ]);

        $this->post(route('account.email-verification-reminder.dismiss'));

        $this->post(route('logout'));

        $this->post(route('login.store'), [
            'login' => $user->username,
            'password' => 'password',
        ]);

        $this->get(route('account.settings.verification'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('emailVerificationReminder.email', $user->email));
    }

    public function test_resend_invokes_verification_endpoint_with_status_feedback(): void
    {
        Notification::fake();

        $user = $this->makeCandidate(['email_verified_at' => null]);
        $this->actingAs($user)
            ->from(route('account.settings.verification'))
            ->post(route('account.verification.send'))
            ->assertRedirect(route('account.settings.verification'))
            ->assertSessionHas('status', 'A verification link has been sent to your email address.');

        Notification::assertSentTo($user, VerifyAccountEmailNotification::class);
    }

    public function test_resend_verification_remains_rate_limited(): void
    {
        Notification::fake();
        $user = $this->makeCandidate(['email_verified_at' => null]);
        $this->actingAs($user);

        for ($i = 0; $i < 6; $i++) {
            $this->post(route('account.verification.send'))->assertRedirect();
        }

        $this->post(route('account.verification.send'))->assertStatus(429);
    }

    public function test_verified_candidate_does_not_see_reminder_after_login(): void
    {
        $user = $this->makeCandidate([
            'username' => 'verified-user',
            'email_verified_at' => now(),
        ]);

        $this->post(route('login.store'), [
            'login' => 'verified-user',
            'password' => 'password',
        ]);

        $this->get(route('account.home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('emailVerificationReminder', null));
    }

    public function test_unverified_employer_does_not_receive_candidate_reminder(): void
    {
        $employer = $this->makeEmployer(['email_verified_at' => null]);

        $this->post(route('login.store'), [
            'login' => $employer->username,
            'password' => 'password',
        ]);

        $this->get(route('account.home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('emailVerificationReminder', null));
    }
}
