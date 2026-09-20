<?php

namespace Tests\Feature;

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
            'email' => 'asha@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => UserRole::Candidate->value,
        ]);

        $response->assertRedirect(route('account.home'));
        $this->assertAuthenticated();

        $user = User::query()->where('email', 'asha@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->isCandidate());
        $this->assertDatabaseHas('candidate_profiles', ['user_id' => $user->id]);

        $this->post(route('logout'));
        $this->assertGuest();

        $login = $this->post(route('login.store'), [
            'email' => 'asha@example.com',
            'password' => 'password',
        ]);

        $login->assertRedirect(route('account.home'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_employer_can_register_and_log_in(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Ravi Employer',
            'email' => 'ravi@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => UserRole::Employer->value,
        ]);

        $response->assertRedirect(route('account.home'));

        $user = User::query()->where('email', 'ravi@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->isEmployer());
        $this->assertDatabaseHas('employer_profiles', ['user_id' => $user->id]);

        $this->post(route('logout'));

        $login = $this->post(route('login.store'), [
            'email' => 'ravi@example.com',
            'password' => 'password',
        ]);

        $login->assertRedirect(route('account.home'));
        $this->assertAuthenticatedAs($user);
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
    }

    public function test_admin_login_page_renders(): void
    {
        $this->get(route('admin.login'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Auth/Login'));
    }
}
