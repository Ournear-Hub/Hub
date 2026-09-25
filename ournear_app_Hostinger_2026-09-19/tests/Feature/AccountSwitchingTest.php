<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AccountSwitchingTest extends TestCase
{
    public function test_guest_account_and_admin_authentication_flows_still_work(): void
    {
        $candidate = $this->makeCandidate([
            'username' => 'switch-candidate',
            'password' => Hash::make('Password1!'),
        ]);
        $admin = $this->makeAdmin([
            'email' => 'switch-admin@example.com',
            'password' => Hash::make('password'),
        ]);

        $this->get(route('login'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Account/Login'));
        $this->get(route('register'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Account/Register'));

        $this->post(route('login.store'), [
            'login' => $candidate->username,
            'password' => 'Password1!',
        ])->assertRedirect(route('account.home'));
        $this->assertAuthenticatedAs($candidate);

        $this->post(route('logout'))->assertRedirect(route('home'));
        $this->post(route('admin.login.store'), [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_admin_must_explicitly_switch_before_account_login(): void
    {
        $admin = $this->makeAdmin(['name' => 'Switch Admin']);
        $candidate = $this->makeCandidate([
            'username' => 'after-admin-switch',
            'password' => Hash::make('Password1!'),
        ]);

        $this->actingAs($admin)
            ->get(route('login'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Switch')
                ->where('destination', 'login')
                ->where('currentUserName', 'Switch Admin'));
        $this->assertAuthenticatedAs($admin);

        $this->post(route('account.switch'), ['destination' => 'login'])
            ->assertRedirect(route('login'));
        $this->assertGuest();

        $this->get(route('login'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Account/Login'));

        $this->post(route('login.store'), [
            'login' => $candidate->username,
            'password' => 'Password1!',
        ])->assertRedirect(route('account.home'));
        $this->assertAuthenticatedAs($candidate);
    }

    public function test_admin_must_explicitly_switch_before_registration(): void
    {
        $admin = $this->makeAdmin(['name' => 'Registration Admin']);

        $this->actingAs($admin)
            ->get(route('register'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Switch')
                ->where('destination', 'register')
                ->where('currentUserName', 'Registration Admin'));
        $this->assertAuthenticatedAs($admin);

        $this->post(route('account.switch'), ['destination' => 'register'])
            ->assertRedirect(route('register'));
        $this->assertGuest();

        $this->get(route('register'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Account/Register'));
    }

    public function test_switching_preserves_only_safe_account_intended_urls(): void
    {
        $admin = $this->makeAdmin();
        $candidate = $this->makeCandidate([
            'username' => 'safe-intended-candidate',
            'password' => Hash::make('Password1!'),
        ]);

        $this->actingAs($admin)
            ->withSession(['url.intended' => '/jobs/safe-role'])
            ->post(route('account.switch'), ['destination' => 'login'])
            ->assertRedirect(route('login'))
            ->assertSessionHas('url.intended', '/jobs/safe-role');

        $this->post(route('login.store'), [
            'login' => $candidate->username,
            'password' => 'Password1!',
        ])->assertRedirect('/jobs/safe-role');

        $this->post(route('logout'));

        $this->actingAs($admin)
            ->withSession(['url.intended' => route('admin.jobs.index')])
            ->post(route('account.switch'), ['destination' => 'login'])
            ->assertRedirect(route('login'))
            ->assertSessionMissing('url.intended');
    }

    public function test_switching_regenerates_session_and_csrf_token(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->withSession(['_token' => 'old-csrf-token']);
        $oldSessionId = $this->app['session']->getId();

        $this->post(route('account.switch'), ['destination' => 'login'])
            ->assertRedirect(route('login'))
            ->assertSessionHas('_token', fn (string $token) => $token !== 'old-csrf-token');

        $this->assertNotSame($oldSessionId, $this->app['session']->getId());
        $this->assertGuest();
    }

    public function test_role_authorization_boundaries_remain_unchanged(): void
    {
        $admin = $this->makeAdmin();
        $candidate = $this->makeCandidate();
        $employer = $this->makeEmployer();

        $this->actingAs($admin)->get(route('account.home'))->assertForbidden();
        $this->actingAs($candidate)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($employer)->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_only_an_admin_can_confirm_an_account_switch(): void
    {
        $candidate = $this->makeCandidate();

        $this->actingAs($candidate)
            ->post(route('account.switch'), ['destination' => 'login'])
            ->assertForbidden();
        $this->assertAuthenticatedAs($candidate);

        $this->post(route('logout'));
        $this->post(route('account.switch'), ['destination' => 'login'])
            ->assertRedirect(route('login'));
    }
}
