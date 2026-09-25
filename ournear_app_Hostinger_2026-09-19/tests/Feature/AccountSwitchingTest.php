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
        $this->assertAuthenticatedAs($candidate, 'account');

        $this->post(route('logout'))->assertRedirect(route('home'));
        $this->assertGuest('account');

        $this->post(route('admin.login.store'), [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin, 'admin');
    }

    public function test_admin_can_open_account_login_without_replacing_admin(): void
    {
        $admin = $this->makeAdmin(['name' => 'Switch Admin']);

        $this->actingAs($admin, 'admin')
            ->get(route('login'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Login')
                ->where('auth.user', null)
                ->where('auth.admin', null));

        $this->assertAuthenticatedAs($admin, 'admin');
        $this->assertGuest('account');
    }

    public function test_admin_can_open_registration_without_replacing_admin(): void
    {
        $admin = $this->makeAdmin(['name' => 'Registration Admin']);

        $this->actingAs($admin, 'admin')
            ->get(route('register'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Register')
                ->where('auth.user', null));

        $this->assertAuthenticatedAs($admin, 'admin');
        $this->assertGuest('account');
    }

    public function test_account_login_keeps_admin_session_and_safe_intended_url(): void
    {
        $admin = $this->makeAdmin();
        $candidate = $this->makeCandidate([
            'username' => 'safe-intended-candidate',
            'password' => Hash::make('Password1!'),
        ]);

        $this->actingAs($admin, 'admin')
            ->withSession(['url.intended' => '/jobs/safe-role'])
            ->post(route('login.store'), [
                'login' => $candidate->username,
                'password' => 'Password1!',
            ])->assertRedirect('/jobs/safe-role');

        $this->assertAuthenticatedAs($admin, 'admin');
        $this->assertAuthenticatedAs($candidate, 'account');

        $this->post(route('logout'));

        $this->actingAs($admin, 'admin')
            ->withSession(['url.intended' => route('admin.jobs.index')])
            ->post(route('login.store'), [
                'login' => $candidate->username,
                'password' => 'Password1!',
            ])->assertRedirect(route('account.home'));

        $this->assertAuthenticatedAs($admin, 'admin');
        $this->assertAuthenticatedAs($candidate, 'account');
    }

    public function test_role_authorization_boundaries_remain_unchanged(): void
    {
        $admin = $this->makeAdmin();
        $candidate = $this->makeCandidate();
        $employer = $this->makeEmployer();

        $this->actingAs($admin, 'admin')
            ->get(route('account.home'))
            ->assertRedirect(route('login'));
        $this->assertAuthenticatedAs($admin, 'admin');
        $this->assertGuest('account');

        $this->forgetAuthentication();
        $this->actingAs($candidate, 'account')->get(route('admin.dashboard'))->assertForbidden();
        $this->forgetAuthentication();
        $this->actingAs($employer, 'account')->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_only_an_authenticated_account_admin_can_hit_the_legacy_switch_route(): void
    {
        $candidate = $this->makeCandidate();

        $this->actingAs($candidate, 'account')
            ->post(route('account.switch'), ['destination' => 'login'])
            ->assertForbidden();
        $this->assertAuthenticatedAs($candidate, 'account');

        $this->post(route('logout'));
        $this->post(route('account.switch'), ['destination' => 'login'])
            ->assertRedirect(route('login'));
    }
}
