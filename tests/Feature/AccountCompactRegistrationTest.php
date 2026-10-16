<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AccountCompactRegistrationTest extends TestCase
{
    public function test_login_page_renders_login_component(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Account/Login'));
    }

    public function test_register_page_renders_register_component(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Account/Register'));
    }

    public function test_compact_registration_creates_candidate_redirects_to_login_without_session(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Compact User',
            'username' => 'compactuser01',
            'email' => 'compact.user@example.com',
            'mobile' => '+971501234568',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'accept_terms' => true,
        ])
            ->assertRedirect(route('login'))
            ->assertSessionHas('registered_email', 'compact.user@example.com');

        $user = User::query()->where('email', 'compact.user@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->isCandidate());
        $this->assertNotEmpty($user->username);
        $this->assertNull($user->employerProfile);
        $this->assertGuest();
    }

    public function test_website_registration_rejects_employer_role_field(): void
    {
        $this->from(route('register'))
            ->post(route('register.store'), [
                'name' => 'Employer Attempt',
                'email' => 'employer.attempt@example.com',
                'password' => 'Password1!',
                'password_confirmation' => 'Password1!',
                'role' => 'employer',
                'accept_terms' => true,
            ])
            ->assertSessionHasErrors('role');

        $this->assertNull(User::query()->where('email', 'employer.attempt@example.com')->first());
    }

    public function test_registration_requires_terms_acceptance(): void
    {
        $this->from(route('register'))
            ->post(route('register.store'), [
                'name' => 'Terms User',
                'email' => 'terms.compact@example.com',
                'password' => 'Password1!',
                'password_confirmation' => 'Password1!',
                'accept_terms' => false,
            ])
            ->assertSessionHasErrors('accept_terms');

        $this->assertNull(User::query()->where('email', 'terms.compact@example.com')->first());
    }

    public function test_registration_rejects_weak_password(): void
    {
        $this->from(route('register'))
            ->post(route('register.store'), [
                'name' => 'Weak Password User',
                'email' => 'weak.compact@example.com',
                'password' => 'short',
                'password_confirmation' => 'short',
                'accept_terms' => true,
            ])
            ->assertSessionHasErrors('password');

        $this->assertNull(User::query()->where('email', 'weak.compact@example.com')->first());
    }

    public function test_existing_user_can_sign_in_with_email_via_login_field(): void
    {
        $user = $this->makeCandidate([
            'email' => 'signin@example.com',
            'password' => Hash::make('Password1!'),
        ]);

        $this->post(route('login.store'), [
            'login' => 'signin@example.com',
            'password' => 'Password1!',
        ])->assertRedirect(route('account.home'));

        $this->assertAuthenticatedAs($user, 'account');
    }
}
