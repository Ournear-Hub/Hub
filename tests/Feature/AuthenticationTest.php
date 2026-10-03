<?php

namespace Tests\Feature;

use App\Enums\StaffRoleSlug;
use App\Enums\UserRole;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    public function test_admin_can_log_in(): void
    {
        $admin = $this->makeAdmin([
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response = $this->post(route('admin.login.store'), [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_staff_login_uses_admin_portal_and_respects_rbac(): void
    {
        $staff = $this->makeStaff(StaffRoleSlug::Recruiter->value, [
            'email' => 'recruiter.login@example.com',
            'password' => 'Password1!',
        ]);

        $this->post(route('admin.login.store'), [
            'email' => $staff->email,
            'password' => 'Password1!',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($staff);
        $this->get(route('admin.dashboard'))->assertOk();
        $this->get(route('admin.jobs.index'))->assertOk();
        $this->get(route('admin.finance.index'))->assertForbidden();
    }

    public function test_candidate_cannot_log_in_through_admin_form(): void
    {
        $candidate = $this->makeCandidate([
            'email' => 'candidate@example.com',
            'password' => 'password',
        ]);

        $response = $this->from(route('admin.login'))->post(route('admin.login.store'), [
            'email' => $candidate->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('admin.login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_candidate_can_register_and_log_in(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Asha Candidate',
            'username' => 'asha-candidate',
            'email' => 'asha@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'role' => UserRole::Candidate->value,
            'accept_terms' => true,
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('registered_email', 'asha@example.com');
        $this->assertGuest();

        $user = User::query()->where('email', 'asha@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->isCandidate());
        $this->assertSame('asha-candidate', $user->username);
        $this->assertDatabaseHas('candidate_profiles', ['user_id' => $user->id]);

        $this->from(route('login'))->post(route('login.store'), [
            'login' => 'asha@example.com',
            'password' => 'Password1!',
        ])->assertSessionHasErrors('login');

        $login = $this->post(route('login.store'), [
            'login' => 'asha-candidate',
            'password' => 'Password1!',
        ]);

        $login->assertRedirect(route('account.home'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_candidate_registration_discards_stale_admin_intended_url(): void
    {
        $this->withSession(['url.intended' => route('admin.dashboard')])
            ->post(route('register.store'), [
                'name' => 'Safe Registration',
                'username' => 'safe-registration',
                'email' => 'safe-registration@example.com',
                'password' => 'Password1!',
                'password_confirmation' => 'Password1!',
                'role' => UserRole::Candidate->value,
                'accept_terms' => true,
            ])
            ->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertTrue(User::query()->where('email', 'safe-registration@example.com')->firstOrFail()->isCandidate());
    }

    public function test_employer_can_register_and_log_in(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Ravi Employer',
            'username' => 'ravi-employer',
            'email' => 'ravi@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'role' => UserRole::Employer->value,
            'accept_terms' => true,
        ]);

        $response->assertRedirect(route('login'));
        $this->assertGuest();

        $user = User::query()->where('email', 'ravi@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->isEmployer());
        $this->assertSame('ravi-employer', $user->username);
        $this->assertDatabaseHas('employer_profiles', ['user_id' => $user->id]);

        $login = $this->post(route('login.store'), [
            'login' => 'ravi-employer',
            'password' => 'Password1!',
        ]);

        $login->assertRedirect(route('account.home'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_candidate_login_discards_stale_admin_intended_url(): void
    {
        $candidate = $this->makeCandidate([
            'username' => 'candidate-intended',
            'password' => 'Password1!',
        ]);

        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
        $this->get(route('login'))->assertOk();

        $this->post(route('login.store'), [
            'login' => $candidate->username,
            'password' => 'Password1!',
        ])
            ->assertRedirect(route('account.home'));

        $this->assertAuthenticatedAs($candidate);
    }

    public function test_employer_login_discards_stale_admin_intended_url(): void
    {
        $employer = $this->makeEmployer([
            'username' => 'employer-intended',
            'password' => 'Password1!',
        ]);

        $this->withSession(['url.intended' => route('admin.jobs.index')])
            ->post(route('login.store'), [
                'login' => $employer->username,
                'password' => 'Password1!',
            ])
            ->assertRedirect(route('account.home'));

        $this->assertAuthenticatedAs($employer);
    }

    public function test_admin_login_discards_stale_account_intended_url(): void
    {
        $admin = $this->makeAdmin([
            'email' => 'admin-intended@example.com',
            'password' => 'password',
        ]);

        $this->get(route('account.home'))->assertRedirect(route('login'));
        $this->get(route('admin.login'))->assertOk();

        $this->post(route('admin.login.store'), [
            'email' => $admin->email,
            'password' => 'password',
        ])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_account_login_keeps_user_side_job_intended_url(): void
    {
        $candidate = $this->makeCandidate([
            'username' => 'candidate-job-intended',
            'password' => 'Password1!',
        ]);

        $this->withSession(['url.intended' => '/jobs'])
            ->post(route('login.store'), [
                'login' => $candidate->username,
                'password' => 'Password1!',
            ])
            ->assertRedirect('/jobs');
    }

    public function test_logout_clears_user_session_and_account_requires_login_again(): void
    {
        $candidate = $this->makeCandidate();

        $this->actingAs($candidate)
            ->post(route('logout'))
            ->assertRedirect(route('home'));

        $this->assertGuest();
        $this->get(route('account.home'))->assertRedirect(route('login'));
    }

    public function test_admin_cannot_log_in_through_account_form(): void
    {
        $admin = $this->makeAdmin([
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response = $this->from(route('login'))->post(route('login.store'), [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_guest_is_redirected_from_admin_routes_to_admin_login(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
        $this->get(route('admin.jobs.index'))->assertRedirect(route('admin.login'));
    }

    public function test_candidate_cannot_access_admin_routes(): void
    {
        $candidate = $this->makeCandidate();

        $this->actingAs($candidate)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($candidate)->get(route('admin.jobs.index'))->assertForbidden();
        $this->actingAs($candidate)->get(route('admin.applications.index'))->assertForbidden();
        $this->actingAs($candidate)->get(route('admin.screening.index'))->assertForbidden();
        $this->actingAs($candidate)->get(route('admin.shortlisted.index'))->assertForbidden();
        $this->actingAs($candidate)->get(route('admin.employers.index'))->assertForbidden();
        $this->actingAs($candidate)->get(route('admin.submissions.index'))->assertForbidden();
        $this->actingAs($candidate)->get(route('admin.placements.index'))->assertForbidden();
        $this->actingAs($candidate)->get(route('admin.employees.index'))->assertForbidden();
        $this->actingAs($candidate)->get(route('admin.replacements.index'))->assertForbidden();
        $this->actingAs($candidate)->get(route('admin.finance.index'))->assertForbidden();
        $this->actingAs($candidate)->get(route('admin.notifications.index'))->assertForbidden();
        $this->actingAs($candidate)->get(route('admin.messages.index'))->assertForbidden();
        $this->actingAs($candidate)->get(route('admin.settings.edit'))->assertForbidden();
    }

    public function test_employer_cannot_access_admin_routes(): void
    {
        $employer = $this->makeEmployer();

        $this->actingAs($employer)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($employer)->get(route('admin.employer-enquiries.index'))->assertForbidden();
        $this->actingAs($employer)->get(route('admin.employers.index'))->assertForbidden();
        $this->actingAs($employer)->get(route('admin.submissions.index'))->assertForbidden();
        $this->actingAs($employer)->get(route('admin.placements.index'))->assertForbidden();
        $this->actingAs($employer)->get(route('admin.employees.index'))->assertForbidden();
        $this->actingAs($employer)->get(route('admin.replacements.index'))->assertForbidden();
        $this->actingAs($employer)->get(route('admin.finance.index'))->assertForbidden();
        $this->actingAs($employer)->get(route('admin.notifications.index'))->assertForbidden();
        $this->actingAs($employer)->get(route('admin.messages.index'))->assertForbidden();
    }

    public function test_admin_login_page_renders(): void
    {
        $this->get(route('admin.login'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Auth/Login'));
    }
}
