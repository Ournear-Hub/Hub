<?php

namespace Tests\Feature;

use App\Enums\PreferredLanguage;
use App\Enums\UserActivityType;
use App\Enums\UserRole;
use App\Models\Job;
use App\Models\User;
use App\Models\UserActivity;
use Illuminate\Auth\Notifications\ResetPassword;
use App\Notifications\AccountEmailChangeVerificationNotification;
use App\Notifications\VerifyAccountEmailNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AccountUserFoundationTest extends TestCase
{
    public function test_admin_cannot_register_through_the_account_form(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Admin Attempt',
            'username' => 'admin-attempt',
            'email' => 'admin.attempt@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'role' => UserRole::Admin->value,
        ])->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'admin.attempt@example.com']);
    }

    public function test_candidate_can_request_a_password_reset(): void
    {
        Notification::fake();
        $candidate = $this->makeCandidate(['email' => 'reset.me@example.com']);

        $this->post(route('password.email'), [
            'email' => 'reset.me@example.com',
        ])->assertRedirect();

        Notification::assertSentTo($candidate, ResetPassword::class);
    }

    public function test_candidate_can_reset_password_and_login_with_the_new_password(): void
    {
        Notification::fake();
        $candidate = $this->makeCandidate([
            'email' => 'reset.complete@example.com',
            'email_verified_at' => now(),
            'password' => 'OldPassword1!',
        ]);
        $token = null;

        $this->post(route('password.email'), [
            'email' => $candidate->email,
        ])->assertRedirect();

        Notification::assertSentTo(
            $candidate,
            ResetPassword::class,
            function (ResetPassword $notification) use (&$token): bool {
                $token = $notification->token;

                return true;
            }
        );

        $this->post(route('password.store'), [
            'token' => $token,
            'email' => $candidate->email,
            'password' => 'NewPassword1!',
            'password_confirmation' => 'NewPassword1!',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('NewPassword1!', $candidate->fresh()->password));

        $this->post(route('login.store'), [
            'login' => $candidate->email,
            'password' => 'NewPassword1!',
        ])->assertRedirect(route('account.home'));
        $this->assertAuthenticatedAs($candidate);
    }

    public function test_password_reset_does_not_disclose_unknown_accounts_and_rejects_invalid_tokens(): void
    {
        Notification::fake();

        $this->post(route('password.email'), [
            'email' => 'missing-account@example.com',
        ])->assertSessionHas('status', 'A password reset link has been sent if that account exists.');

        Notification::assertNothingSent();

        $candidate = $this->makeCandidate([
            'email' => 'invalid.token@example.com',
            'password' => 'OldPassword1!',
        ]);

        $this->post(route('password.store'), [
            'token' => 'invalid-token',
            'email' => $candidate->email,
            'password' => 'NewPassword1!',
            'password_confirmation' => 'NewPassword1!',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('OldPassword1!', $candidate->fresh()->password));
    }

    public function test_guest_job_application_redirects_to_login(): void
    {
        $job = Job::factory()->create();

        $this->post(route('jobs.apply.store', $job))->assertRedirect(route('login'));

        $this->assertDatabaseMissing('applications', [
            'email' => 'v12a.guest@example.com',
        ]);
    }

    public function test_candidate_can_update_own_profile_username_bio_and_language(): void
    {
        $candidate = $this->makeCandidate([
            'email' => 'profile.owner@example.com',
        ]);

        $this->actingAs($candidate)
            ->put(route('account.profile.update'), [
                'name' => 'Asha Owner',
                'username' => 'asha-owner',
                'mobile' => '9876543210',
                'location' => 'Hyderabad',
                'bio' => 'Civil engineer looking for project roles.',
                'preferred_language' => PreferredLanguage::Telugu->value,
                'experience' => '5 years',
                'qualification' => 'B.Tech',
            ])
            ->assertRedirect();

        $candidate->refresh();
        $this->assertSame('Asha Owner', $candidate->name);
        $this->assertSame('asha-owner', $candidate->username);
        $this->assertSame('+919876543210', $candidate->mobile);
        $this->assertSame('Hyderabad', $candidate->location);
        $this->assertSame('Civil engineer looking for project roles.', $candidate->bio);
        $this->assertSame(PreferredLanguage::Telugu, $candidate->preferred_language);
        $this->assertSame('+919876543210', $candidate->candidateProfile?->mobile);
        $this->assertSame('Hyderabad', $candidate->candidateProfile?->location);
        $this->assertSame('5 years', $candidate->candidateProfile?->experience);
        $this->assertTrue(
            UserActivity::query()
                ->where('user_id', $candidate->id)
                ->where('type', UserActivityType::UpdatedProfile->value)
                ->exists()
        );
    }

    public function test_username_must_be_unique_and_valid(): void
    {
        $owner = $this->makeCandidate(['username' => 'taken-name']);
        $candidate = $this->makeCandidate(['username' => 'free-name']);

        $this->actingAs($candidate)
            ->from(route('account.profile.edit'))
            ->put(route('account.profile.update'), $this->profilePayload($candidate, [
                'username' => 'taken-name',
            ]))
            ->assertSessionHasErrors('username');

        $this->assertSame('free-name', $candidate->fresh()->username);

        $this->actingAs($candidate)
            ->from(route('account.profile.edit'))
            ->put(route('account.profile.update'), $this->profilePayload($candidate, [
                'username' => 'not valid!',
            ]))
            ->assertSessionHasErrors('username');

        $this->actingAs($candidate)
            ->from(route('account.profile.edit'))
            ->put(route('account.profile.update'), $this->profilePayload($candidate, [
                'username' => '',
            ]))
            ->assertSessionHasErrors('username');

        $this->assertSame('free-name', $candidate->fresh()->username);
        $this->assertSame('taken-name', $owner->fresh()->username);
    }

    public function test_candidate_cannot_modify_another_users_profile(): void
    {
        $owner = $this->makeCandidate(['name' => 'Owner User', 'username' => 'owner-user']);
        $intruder = $this->makeCandidate(['name' => 'Intruder', 'username' => 'intruder-user']);

        $this->actingAs($intruder)
            ->put(route('account.profile.update'), $this->profilePayload($intruder, [
                'name' => 'Intruder Updated',
                'username' => 'intruder-updated',
                'id' => $owner->id,
                'user_id' => $owner->id,
            ]))
            ->assertRedirect();

        $this->assertSame('Owner User', $owner->fresh()->name);
        $this->assertSame('owner-user', $owner->fresh()->username);
        $this->assertSame('Intruder Updated', $intruder->fresh()->name);
        $this->assertSame('intruder-updated', $intruder->fresh()->username);
    }

    public function test_candidate_can_upload_and_remove_own_photo(): void
    {
        $candidate = $this->makeCandidate();

        $this->actingAs($candidate)
            ->post(route('account.profile.photo.update'), [
                'photo' => UploadedFile::fake()->image('avatar.jpg', 80, 80),
            ])
            ->assertRedirect();

        $candidate->refresh();
        $this->assertNotNull($candidate->photo_path);
        Storage::disk('public')->assertExists($candidate->photo_path);

        $this->actingAs($candidate)
            ->delete(route('account.profile.photo.destroy'))
            ->assertRedirect();

        $this->assertNull($candidate->fresh()->photo_path);
    }

    public function test_unverified_candidate_can_verify_email_with_a_signed_link(): void
    {
        Notification::fake();
        $candidate = $this->makeCandidate([
            'email' => 'verify.me@example.com',
            'email_verified_at' => null,
        ]);
        $other = $this->makeCandidate(['email' => 'other.verify@example.com', 'email_verified_at' => null]);

        $this->assertFalse($candidate->fresh()->hasVerifiedEmail());

        $this->actingAs($candidate)
            ->from(route('account.settings.verification'))
            ->post(route('account.verification.send'))
            ->assertRedirect();

        Notification::assertSentTo($candidate, VerifyAccountEmailNotification::class);

        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $candidate->id,
            'hash' => sha1($candidate->email),
        ]);

        $this->actingAs($other)
            ->get($url)
            ->assertForbidden();

        $this->actingAs($candidate)
            ->get(route('verification.verify', ['id' => $candidate->id, 'hash' => sha1($candidate->email)]))
            ->assertForbidden();

        $this->actingAs($candidate)
            ->get($url)
            ->assertRedirect(route('account.settings.verification'))
            ->assertSessionHas('success', 'Your email has been verified successfully.');

        $this->assertNotNull($candidate->fresh()->email_verified_at);
        $this->assertTrue(
            UserActivity::query()
                ->where('user_id', $candidate->id)
                ->where('type', UserActivityType::EmailVerified->value)
                ->exists()
        );

        $this->assertTrue($candidate->fresh()->hasVerifiedEmail());

        $sentCount = count(Notification::sent($candidate, VerifyAccountEmailNotification::class));

        $this->actingAs($candidate)
            ->post(route('account.verification.send'))
            ->assertRedirect()
            ->assertSessionHas('status', 'Your email is already verified.');

        $this->assertSame($sentCount, count(Notification::sent($candidate, VerifyAccountEmailNotification::class)));
    }

    public function test_verification_resend_is_throttled(): void
    {
        Notification::fake();
        $candidate = $this->makeCandidate(['email_verified_at' => null]);

        for ($i = 0; $i < 6; $i++) {
            $this->actingAs($candidate)
                ->post(route('account.verification.send'))
                ->assertRedirect();
        }

        $this->actingAs($candidate)
            ->post(route('account.verification.send'))
            ->assertStatus(429);
    }

    public function test_candidate_can_change_password_with_current_password(): void
    {
        $candidate = $this->makeCandidate([
            'email' => 'password.change@example.com',
            'password' => 'password',
        ]);

        $this->actingAs($candidate)
            ->from(route('account.settings.password'))
            ->put(route('account.settings.password.update'), [
                'current_password' => 'wrong-password',
                'password' => 'NewPassword1!',
                'password_confirmation' => 'NewPassword1!',
            ])
            ->assertSessionHasErrors('current_password');

        $this->actingAs($candidate)
            ->from(route('account.settings.password'))
            ->put(route('account.settings.password.update'), [
                'current_password' => 'password',
                'password' => 'NewPassword1!',
                'password_confirmation' => 'mismatch',
            ])
            ->assertSessionHasErrors('password');

        $this->actingAs($candidate)
            ->put(route('account.settings.password.update'), [
                'current_password' => 'password',
                'password' => 'NewPassword1!',
                'password_confirmation' => 'NewPassword1!',
            ])
            ->assertRedirect();

        $candidate->refresh();
        $this->assertTrue(Hash::check('NewPassword1!', $candidate->password));
        $this->assertFalse(Hash::check('password', $candidate->password));
        $this->assertNotNull($candidate->password_changed_at);

        $this->post(route('logout'));
        $this->assertGuest();

        $this->from(route('login'))
            ->post(route('login.store'), [
                'email' => 'password.change@example.com',
                'password' => 'password',
            ])
            ->assertSessionHasErrors('email');

        $this->post(route('login.store'), [
            'email' => 'password.change@example.com',
            'password' => 'NewPassword1!',
        ])->assertRedirect(route('account.home'));
    }

    public function test_profile_completion_reflects_actual_fields_and_verification(): void
    {
        $candidate = $this->makeCandidate([
            'name' => 'Score User',
            'email' => 'score.user@example.com',
            'username' => 'score-user',
            'email_verified_at' => null,
            'mobile' => null,
            'location' => null,
            'bio' => null,
            'photo_path' => null,
            'mobile_verified_at' => null,
        ]);

        $this->assertSame(40, $candidate->fresh()->calculateProfileCompletion());

        $candidate->forceFill(['bio' => 'A short bio.'])->save();
        $this->assertSame(50, $candidate->fresh()->profile_completion);

        $candidate->forceFill(['email_verified_at' => now()])->save();
        $this->assertSame(60, $candidate->fresh()->profile_completion);
        $this->assertLessThanOrEqual(100, $candidate->fresh()->profile_completion);
        $this->assertContains(
            'email_verified',
            collect($candidate->profileCompletionItems())->where('complete', true)->pluck('key')->all()
        );
    }

    public function test_account_settings_pages_render_and_employer_can_update_language(): void
    {
        $employer = $this->makeEmployer(['username' => 'employer-one']);
        $candidate = $this->makeCandidate(['username' => 'candidate-one']);

        $this->actingAs($employer)
            ->get(route('account.home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Home')
                ->where('profile.username', 'employer-one')
                ->has('profile.completion_items'));

        $this->actingAs($employer)
            ->get(route('account.settings.index'))
            ->assertRedirect(route('account.company'));

        $this->actingAs($employer)
            ->get(route('account.settings.account'))
            ->assertRedirect(route('account.company'));

        $this->actingAs($employer)
            ->get(route('account.settings.security'))
            ->assertRedirect(route('account.company'));

        $this->actingAs($employer)
            ->get(route('account.settings.verification'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Account/Settings/Verification'));

        $this->actingAs($employer)
            ->get(route('account.settings.password'))
            ->assertRedirect(route('account.company'));

        $this->actingAs($employer)
            ->get(route('account.settings.communication'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Account/Settings/Account')->where('role', 'employer'));

        $this->actingAs($candidate)
            ->get(route('account.settings.account'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Account/Settings/Account'));

        $this->actingAs($candidate)
            ->put(route('account.settings.account.update'), [
                'username' => 'candidate-one',
                'mobile' => '9000000000',
                'preferred_language' => PreferredLanguage::Hindi->value,
            ])
            ->assertRedirect();

        $this->assertSame(PreferredLanguage::Hindi, $candidate->fresh()->preferred_language);
        $this->assertSame('+919000000000', $candidate->fresh()->mobile);
    }

    public function test_candidate_and_employer_remain_isolated_from_admin(): void
    {
        $candidate = $this->makeCandidate();
        $employer = $this->makeEmployer();

        $this->actingAs($candidate)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($employer)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($this->makeAdmin())->get(route('account.home'))->assertRedirect(route('login'));
        $this->actingAs($this->makeAdmin())->put(route('account.profile.update'), [
            'name' => 'Nope',
            'username' => 'nope-admin',
            'preferred_language' => 'en',
        ])->assertRedirect(route('login'));
    }

    public function test_verified_user_email_change_keeps_current_email_until_pending_link_is_confirmed(): void
    {
        Notification::fake();
        $candidate = $this->makeCandidate([
            'email' => 'keep.until.confirm@example.com',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($candidate)
            ->from(route('account.settings.verification'))
            ->post(route('account.settings.email.change'), [
                'email' => 'new.after.confirm@example.com',
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $candidate->refresh();
        $this->assertSame('keep.until.confirm@example.com', $candidate->email);
        $this->assertTrue($candidate->hasVerifiedEmail());
        $this->assertSame('new.after.confirm@example.com', $candidate->pending_email);

        Notification::assertSentOnDemand(
            AccountEmailChangeVerificationNotification::class,
            fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'new.after.confirm@example.com',
        );

        $url = URL::temporarySignedRoute('account.settings.email.verify-change', now()->addHours(24), [
            'id' => $candidate->id,
            'hash' => sha1('new.after.confirm@example.com'),
        ]);

        $other = $this->makeCandidate(['email' => 'other.email@example.com', 'email_verified_at' => now()]);
        $this->actingAs($other)->get($url)->assertForbidden();

        $this->actingAs($candidate)->get($url)->assertRedirect(route('account.settings.verification'));

        $candidate->refresh();
        $this->assertSame('new.after.confirm@example.com', $candidate->email);
        $this->assertNull($candidate->pending_email);
        $this->assertTrue($candidate->hasVerifiedEmail());
    }

    public function test_unverified_user_email_change_updates_address_and_sends_standard_verification(): void
    {
        Notification::fake();
        $candidate = $this->makeCandidate([
            'email' => 'old.unverified@example.com',
            'email_verified_at' => null,
        ]);

        $this->actingAs($candidate)
            ->post(route('account.settings.email.change'), [
                'email' => 'fresh.unverified@example.com',
            ])
            ->assertRedirect();

        $this->assertSame('fresh.unverified@example.com', $candidate->fresh()->email);
        $this->assertFalse($candidate->fresh()->hasVerifiedEmail());
        Notification::assertSentTo($candidate->fresh(), VerifyAccountEmailNotification::class);
    }

    public function test_client_cannot_set_email_or_verification_timestamps_via_account_settings(): void
    {
        $candidate = $this->makeCandidate([
            'email' => 'protected@example.com',
            'email_verified_at' => null,
            'mobile' => '9810000099',
        ]);

        $this->actingAs($candidate)
            ->put(route('account.settings.account.update'), [
                'username' => $candidate->username,
                'mobile' => '9810000099',
                'preferred_language' => 'en',
                'email' => 'hacked@example.com',
                'email_verified_at' => now()->toDateTimeString(),
                'pending_email' => 'pending@example.com',
            ])
            ->assertSessionHasErrors(['email', 'email_verified_at', 'pending_email']);

        $candidate->refresh();
        $this->assertSame('protected@example.com', $candidate->email);
        $this->assertNull($candidate->email_verified_at);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function profilePayload(User $user, array $overrides = []): array
    {
        return array_merge([
            'name' => $user->name,
            'username' => $user->username,
            'mobile' => $user->mobile,
            'location' => $user->location,
            'bio' => $user->bio,
            'preferred_language' => $user->preferred_language instanceof PreferredLanguage
                ? $user->preferred_language->value
                : 'en',
            'experience' => $user->candidateProfile?->experience,
            'qualification' => $user->candidateProfile?->qualification,
        ], $overrides);
    }
}
