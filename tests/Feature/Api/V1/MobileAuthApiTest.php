<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Notifications\VerifyAccountEmailNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileAuthApiTest extends TestCase
{
    public function test_candidate_registration_creates_account_and_mobile_token(): void
    {
        Notification::fake();
        $this->withoutMiddleware('throttle:account-register');

        $response = $this->postJson(route('api.v1.auth.register'), [
            'name' => 'Mobile Signup',
            'username' => 'mobile-signup-user',
            'email' => 'mobile-signup@example.test',
            'mobile' => '+971501234567',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'accept_terms' => true,
            'accept_privacy' => true,
            'device_name' => 'phpunit-mobile',
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'token',
                'token_type',
                'verification_email_sent',
                'user' => ['username', 'email', 'role', 'email_verified'],
            ])
            ->assertJsonPath('user.username', 'mobile-signup-user')
            ->assertJsonPath('user.email_verified', false)
            ->assertJsonPath('verification_email_sent', true);

        $user = User::query()->where('username', 'mobile-signup-user')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->isCandidate());

        Notification::assertSentTo($user, VerifyAccountEmailNotification::class);

        $this->postJson(route('api.v1.auth.login'), [
            'login' => 'mobile-signup-user',
            'password' => 'Password1!',
        ])->assertOk();
    }

    public function test_registration_rejects_duplicate_username_and_email(): void
    {
        $this->withoutMiddleware('throttle:account-register');

        $this->makeCandidate([
            'username' => 'taken-user',
            'email' => 'taken@example.test',
            'email_verified_at' => now(),
        ]);

        $this->postJson(route('api.v1.auth.register'), [
            'name' => 'Duplicate',
            'username' => 'taken-user',
            'email' => 'other@example.test',
            'mobile' => '+971501234568',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'accept_terms' => true,
            'accept_privacy' => true,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['username']);

        $this->postJson(route('api.v1.auth.register'), [
            'name' => 'Duplicate Email',
            'username' => 'fresh-user',
            'email' => 'taken@example.test',
            'mobile' => '+971501234569',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'accept_terms' => true,
            'accept_privacy' => true,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_forgot_password_returns_generic_success_message(): void
    {
        $this->withoutMiddleware('throttle:account-register');

        $this->makeCandidate([
            'email' => 'recover@example.test',
            'email_verified_at' => now(),
        ]);

        $this->postJson(route('api.v1.auth.forgot-password'), [
            'email' => 'recover@example.test',
        ])
            ->assertOk()
            ->assertJsonPath('message', 'If an account exists for that email, a password reset link has been sent.');

        $this->postJson(route('api.v1.auth.forgot-password'), [
            'email' => 'missing@example.test',
        ])
            ->assertOk()
            ->assertJsonPath('message', 'If an account exists for that email, a password reset link has been sent.');
    }

    public function test_valid_credentials_issue_mobile_token(): void
    {
        $user = $this->makeCandidate([
            'username' => 'mobile-user',
            'password' => Hash::make('Password1!'),
            'email_verified_at' => now(),
        ]);

        $response = $this->postJson(route('api.v1.auth.login'), [
            'login' => 'mobile-user',
            'password' => 'Password1!',
            'device_name' => 'test-device',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token', 'token_type', 'user' => ['name', 'username', 'role']])
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('user.username', 'mobile-user');

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'test-device',
        ]);
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        $this->makeCandidate([
            'username' => 'mobile-user',
            'password' => Hash::make('Password1!'),
            'email_verified_at' => now(),
        ]);

        $this->postJson(route('api.v1.auth.login'), [
            'login' => 'mobile-user',
            'password' => 'wrong-password',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['login']);
    }

    public function test_protected_account_endpoint_requires_authentication(): void
    {
        $this->getJson(route('api.v1.account.show'))->assertUnauthorized();
    }

    public function test_authenticated_request_returns_account_summary(): void
    {
        $user = $this->makeCandidate([
            'username' => 'token-user',
            'email_verified_at' => now(),
        ]);

        Sanctum::actingAs($user, ['account:mobile']);

        $this->getJson(route('api.v1.account.show'))
            ->assertOk()
            ->assertJsonPath('data.username', 'token-user')
            ->assertJsonPath('data.email', $user->email);
    }

    public function test_logout_revokes_current_token(): void
    {
        $user = $this->makeCandidate(['email_verified_at' => now()]);
        $token = $user->createToken('test', ['account:mobile'])->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson(route('api.v1.auth.logout'))
            ->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);

        $this->app['auth']->forgetGuards();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson(route('api.v1.account.show'))
            ->assertUnauthorized();
    }

    public function test_web_session_login_still_works(): void
    {
        $user = $this->makeCandidate([
            'username' => 'web-still-works',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        $this->post(route('login.store'), [
            'login' => 'web-still-works',
            'password' => 'password',
        ])->assertRedirect(route('account.home'));

        $this->assertAuthenticatedAs($user);
    }
}
