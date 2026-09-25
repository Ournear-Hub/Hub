<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DualGuardAuthenticationTest extends TestCase
{
    public function test_admin_login_uses_the_admin_guard(): void
    {
        $admin = $this->makeAdmin([
            'email' => 'dual-admin@example.com',
            'password' => Hash::make('password'),
        ]);

        $this->post(route('admin.login.store'), [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin, 'admin');
        $this->assertGuest('account');
        $this->get(route('admin.dashboard'))->assertOk();
    }

    public function test_account_login_uses_the_account_guard(): void
    {
        $candidate = $this->makeCandidate([
            'username' => 'dual-candidate',
            'password' => Hash::make('Password1!'),
        ]);

        $this->post(route('login.store'), [
            'login' => $candidate->username,
            'password' => 'Password1!',
        ])->assertRedirect(route('account.home'));

        $this->assertAuthenticatedAs($candidate, 'account');
        $this->assertGuest('admin');
        $this->get(route('account.home'))->assertOk();
    }

    public function test_admin_and_candidate_sessions_coexist(): void
    {
        [$admin, $candidate] = $this->loginAdminAndAccount('dual-coexist-candidate');

        $this->assertSame($admin->id, Auth::guard('admin')->id());
        $this->assertSame($candidate->id, Auth::guard('account')->id());
        $this->get(route('admin.dashboard'))->assertOk();
        $this->get(route('account.home'))->assertOk();
    }

    public function test_account_login_does_not_replace_admin(): void
    {
        $admin = $this->makeAdmin([
            'email' => 'dual-keep-admin@example.com',
            'password' => Hash::make('password'),
        ]);
        $candidate = $this->makeCandidate([
            'username' => 'dual-keep-admin-candidate',
            'password' => Hash::make('Password1!'),
        ]);

        $this->post(route('admin.login.store'), [
            'email' => $admin->email,
            'password' => 'password',
        ]);
        $this->post(route('login.store'), [
            'login' => $candidate->username,
            'password' => 'Password1!',
        ]);

        $this->assertAuthenticatedAs($admin, 'admin');
        $this->assertAuthenticatedAs($candidate, 'account');
    }

    public function test_admin_login_does_not_replace_account(): void
    {
        $candidate = $this->makeCandidate([
            'username' => 'dual-keep-candidate',
            'password' => Hash::make('Password1!'),
        ]);
        $admin = $this->makeAdmin([
            'email' => 'dual-keep-candidate-admin@example.com',
            'password' => Hash::make('password'),
        ]);

        $this->post(route('login.store'), [
            'login' => $candidate->username,
            'password' => 'Password1!',
        ]);
        $this->post(route('admin.login.store'), [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($candidate, 'account');
        $this->assertAuthenticatedAs($admin, 'admin');
    }

    public function test_admin_logout_leaves_the_account_session(): void
    {
        [$admin, $candidate] = $this->loginAdminAndAccount('dual-admin-logout');

        $this->post(route('admin.logout'))->assertRedirect(route('admin.login'));

        $this->assertGuest('admin');
        $this->assertAuthenticatedAs($candidate, 'account');
        $this->get(route('admin.dashboard'))->assertForbidden();
        $this->get(route('admin.login'))->assertOk();
        $this->get(route('account.home'))->assertOk();
        $this->assertNotSame($admin->id, Auth::guard('account')->id());
    }

    public function test_account_logout_leaves_the_admin_session(): void
    {
        [$admin, $candidate] = $this->loginAdminAndAccount('dual-account-logout');

        $this->post(route('logout'))->assertRedirect(route('home'));

        $this->assertGuest('account');
        $this->assertAuthenticatedAs($admin, 'admin');
        $this->get(route('account.home'))->assertRedirect(route('login'));
        $this->get(route('admin.dashboard'))->assertOk();
        $this->assertNotSame($candidate->id, Auth::guard('admin')->id());
    }

    public function test_candidate_account_session_cannot_open_admin(): void
    {
        $candidate = $this->makeCandidate();

        $this->actingAs($candidate, 'account')
            ->get(route('admin.dashboard'))
            ->assertForbidden();
        $this->assertGuest('admin');
    }

    public function test_employer_account_session_cannot_open_admin(): void
    {
        $employer = $this->makeEmployer();

        $this->actingAs($employer, 'account')
            ->get(route('admin.dashboard'))
            ->assertForbidden();
        $this->assertGuest('admin');
    }

    public function test_admin_session_does_not_impersonate_an_account(): void
    {
        $admin = $this->makeAdmin();
        $employer = $this->makeEmployer([
            'username' => 'dual-employer',
            'password' => Hash::make('Password1!'),
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('account.home'))
            ->assertRedirect(route('login'));
        $this->assertGuest('account');

        $this->actingAs($admin, 'admin')->post(route('login.store'), [
            'login' => $employer->username,
            'password' => 'Password1!',
        ])->assertRedirect(route('account.home'));

        $this->assertAuthenticatedAs($admin, 'admin');
        $this->assertAuthenticatedAs($employer, 'account');
        $this->get(route('account.home'))->assertOk();
        $this->get(route('admin.dashboard'))->assertOk();
    }

    public function test_admin_login_returns_to_the_admin_intended_url(): void
    {
        $admin = $this->makeAdmin([
            'email' => 'dual-intended-admin@example.com',
            'password' => Hash::make('password'),
        ]);

        $this->get(route('admin.jobs.index'))->assertRedirect(route('admin.login'));

        $this->post(route('admin.login.store'), [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.jobs.index'));
    }

    public function test_account_login_returns_to_the_account_intended_url(): void
    {
        $candidate = $this->makeCandidate([
            'username' => 'dual-intended-candidate',
            'password' => Hash::make('Password1!'),
        ]);

        $this->get(route('account.profile.edit'))->assertRedirect(route('login'));

        $this->post(route('login.store'), [
            'login' => $candidate->username,
            'password' => 'Password1!',
        ])->assertRedirect(route('account.profile.edit'));
    }

    public function test_login_does_not_consume_the_other_channels_intended_url(): void
    {
        $admin = $this->makeAdmin([
            'email' => 'dual-cross-admin@example.com',
            'password' => Hash::make('password'),
        ]);
        $candidate = $this->makeCandidate([
            'username' => 'dual-cross-candidate',
            'password' => Hash::make('Password1!'),
        ]);

        $this->withSession(['url.intended' => route('admin.jobs.index')])
            ->post(route('login.store'), [
                'login' => $candidate->username,
                'password' => 'Password1!',
            ])->assertRedirect(route('account.home'));

        $this->post(route('logout'));
        $this->forgetAuthentication();

        $this->withSession(['url.intended' => route('account.profile.edit')])
            ->post(route('admin.login.store'), [
                'email' => $admin->email,
                'password' => 'password',
            ])->assertRedirect(route('admin.dashboard'));
    }

    public function test_guest_pages_stay_independent_between_channels(): void
    {
        $admin = $this->makeAdmin();
        $candidate = $this->makeCandidate();

        $this->actingAs($admin, 'admin')
            ->get(route('login'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Account/Login'));

        $this->forgetAuthentication();

        $this->actingAs($candidate, 'account')
            ->get(route('admin.login'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Auth/Login'));
    }

    /**
     * @return array{0: \App\Models\User, 1: \App\Models\User}
     */
    private function loginAdminAndAccount(string $username): array
    {
        $admin = $this->makeAdmin([
            'email' => $username.'-admin@example.com',
            'password' => Hash::make('password'),
        ]);
        $candidate = $this->makeCandidate([
            'username' => $username,
            'password' => Hash::make('Password1!'),
        ]);

        $this->post(route('admin.login.store'), [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));
        $this->post(route('login.store'), [
            'login' => $candidate->username,
            'password' => 'Password1!',
        ])->assertRedirect(route('account.home'));

        return [$admin, $candidate];
    }
}
