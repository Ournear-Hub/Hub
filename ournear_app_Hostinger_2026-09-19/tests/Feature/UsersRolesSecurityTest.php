<?php

namespace Tests\Feature;

use App\Enums\LoginEvent;
use App\Enums\PermissionName;
use App\Enums\StaffRoleSlug;
use App\Enums\UserRole;
use App\Models\Job;
use App\Models\LoginLog;
use App\Models\Role;
use App\Models\SecurityActivity;
use App\Models\User;
use App\Services\AuthorizationBootstrapper;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UsersRolesSecurityTest extends TestCase
{
    public function test_existing_admin_users_without_roles_receive_super_admin(): void
    {
        $user = User::query()->create([
            'name' => 'Legacy Admin',
            'email' => 'legacy-admin@example.com',
            'password' => Hash::make('password'),
            'role' => UserRole::Admin,
        ]);
        $user->forceFill(['is_active' => true])->save();

        $this->assertFalse($user->roles()->exists());

        app(AuthorizationBootstrapper::class)->assignExistingAdmins();
        $user->forgetPermissionCache();

        $this->assertTrue($user->fresh()->isSuperAdmin());
        $this->assertTrue($user->fresh()->hasPermission(PermissionName::RolesManage->value));
        $this->assertTrue($user->fresh()->canManageRecruitment());
    }

    public function test_guest_cannot_access_security_routes(): void
    {
        $this->get(route('admin.users.index'))->assertRedirect(route('admin.login'));
        $this->get(route('admin.roles.index'))->assertRedirect(route('admin.login'));
        $this->get(route('admin.login-logs.index'))->assertRedirect(route('admin.login'));
        $this->get(route('admin.activity-logs.index'))->assertRedirect(route('admin.login'));
        $this->post(route('admin.users.store'), [])->assertRedirect(route('admin.login'));
    }

    public function test_super_admin_can_open_users_roles_and_security_workspaces(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->get(route('admin.users.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Users/Index'));

        $this->actingAs($admin)->get(route('admin.roles.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Roles/Index'));

        $this->actingAs($admin)->get(route('admin.login-logs.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/LoginLogs/Index'));

        $this->actingAs($admin)->get(route('admin.activity-logs.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/ActivityLogs/Index'));
    }

    public function test_authorization_matrix_allows_and_denies_domain_access(): void
    {
        $recruiter = $this->makeStaff(StaffRoleSlug::Recruiter->value);
        $finance = $this->makeStaff(StaffRoleSlug::Finance->value);
        $admin = $this->makeStaff(StaffRoleSlug::Admin->value);
        $job = Job::factory()->create();

        $this->actingAs($recruiter)->get(route('admin.jobs.index'))->assertOk();
        $this->actingAs($recruiter)->get(route('admin.finance.index'))->assertForbidden();
        $this->actingAs($recruiter)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($recruiter)->get(route('admin.roles.index'))->assertForbidden();
        $this->actingAs($recruiter)->get(route('admin.settings.edit'))->assertForbidden();

        $this->actingAs($finance)->get(route('admin.finance.index'))->assertOk();
        $this->actingAs($finance)->get(route('admin.jobs.index'))->assertForbidden();
        $this->actingAs($finance)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($finance)->put(route('admin.jobs.update', $job), ['title' => 'Nope'])->assertForbidden();
        $this->actingAs($finance)->get(route('admin.website-content.index'))->assertForbidden();
        $this->actingAs($finance)->get(route('admin.settings.edit'))->assertForbidden();
        $this->actingAs($finance)->get(route('admin.messages.index'))->assertForbidden();
        $this->actingAs($finance)->get(route('admin.notifications.index'))->assertOk();

        $this->actingAs($admin)->get(route('admin.users.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.roles.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('admin.finance.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.jobs.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.settings.edit'))->assertOk();
    }

    public function test_recruiter_can_manage_jobs_but_cannot_create_invoices_or_users(): void
    {
        $recruiter = $this->makeStaff(StaffRoleSlug::Recruiter->value);
        $finance = $this->makeStaff(StaffRoleSlug::Finance->value);

        $this->assertTrue($recruiter->can('create', Job::class));
        $this->assertFalse($recruiter->can('create', \App\Models\Invoice::class));
        $this->assertFalse($recruiter->can('viewAny', User::class));
        $this->assertFalse($recruiter->can('viewAny', Role::class));

        $this->assertTrue($finance->can('create', \App\Models\Invoice::class));
        $this->assertFalse($finance->can('create', Job::class));
        $this->assertTrue($finance->can('viewAny', \App\Models\Employee::class));
        $this->assertFalse($finance->can('viewAny', Job::class));

        $this->actingAs($recruiter)->post(route('admin.users.store'), [
            'name' => 'Intruder',
            'email' => 'intruder@example.com',
            'role_ids' => [Role::query()->value('id')],
        ])->assertForbidden();
    }

    public function test_ordinary_admin_cannot_assign_super_admin_or_edit_roles(): void
    {
        $admin = $this->makeStaff(StaffRoleSlug::Admin->value);
        $target = $this->makeStaff(StaffRoleSlug::Recruiter->value);
        $super = Role::query()->where('slug', StaffRoleSlug::SuperAdmin->value)->first();
        $role = Role::query()->where('slug', StaffRoleSlug::Recruiter->value)->first();

        $this->actingAs($admin)
            ->put(route('admin.users.roles', $target), [
                'role_ids' => [$super->id],
            ])
            ->assertSessionHasErrors('role_ids');

        $this->actingAs($admin)
            ->put(route('admin.roles.permissions', $role), [
                'permission_ids' => [],
            ])
            ->assertForbidden();

        $target->refresh()->forgetPermissionCache();
        $this->assertTrue($target->hasStaffRole(StaffRoleSlug::Recruiter->value));
        $this->assertFalse($target->isSuperAdmin());
    }

    public function test_last_super_admin_cannot_be_removed_or_deactivated(): void
    {
        $super = $this->makeAdmin();
        $adminRole = Role::query()->where('slug', StaffRoleSlug::Admin->value)->first();

        $this->actingAs($super)
            ->put(route('admin.users.roles', $super), [
                'role_ids' => [$adminRole->id],
            ])
            ->assertSessionHasErrors('role_ids');

        $this->actingAs($super)
            ->patch(route('admin.users.status', $super), [
                'is_active' => false,
            ])
            ->assertForbidden();

        $this->assertTrue($super->fresh()->isSuperAdmin());
        $this->assertTrue($super->fresh()->isActive());
    }

    public function test_inactive_admin_cannot_log_in_and_existing_session_is_rejected(): void
    {
        $admin = $this->makeAdmin([
            'email' => 'inactive-admin@example.com',
            'password' => 'password',
        ]);
        $admin->forceFill(['is_active' => false])->save();

        $this->from(route('admin.login'))
            ->post(route('admin.login.store'), [
                'email' => $admin->email,
                'password' => 'password',
            ])
            ->assertRedirect(route('admin.login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertTrue(LoginLog::query()->where('email', $admin->email)->where('event', LoginEvent::LoginFailure->value)->exists());

        $this->actingAs($admin->fresh())
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'));
        $this->assertGuest();
    }

    public function test_successful_admin_login_and_logout_are_logged(): void
    {
        $admin = $this->makeAdmin([
            'email' => 'logger@example.com',
            'password' => 'password',
        ]);

        $this->post(route('admin.login.store'), [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin);
        $this->assertTrue(LoginLog::query()->where('user_id', $admin->id)->where('event', LoginEvent::LoginSuccess->value)->exists());
        $this->assertFalse(LoginLog::query()->where('event', LoginEvent::LoginSuccess->value)->whereNotNull('metadata->password')->exists());

        $this->post(route('admin.logout'))->assertRedirect(route('admin.login'));
        $this->assertGuest();
        $this->assertTrue(LoginLog::query()->where('user_id', $admin->id)->where('event', LoginEvent::Logout->value)->exists());
    }

    public function test_staff_user_creation_assigns_roles_and_does_not_expose_a_password(): void
    {
        Notification::fake();
        $actor = $this->makeAdmin();
        $recruiter = Role::query()->where('slug', StaffRoleSlug::Recruiter->value)->first();

        $this->actingAs($actor)
            ->post(route('admin.users.store'), [
                'name' => 'New Recruiter',
                'email' => 'new-recruiter@example.com',
                'role_ids' => [$recruiter->id],
            ])
            ->assertRedirect();

        $created = User::query()->where('email', 'new-recruiter@example.com')->first();
        $this->assertNotNull($created);
        $this->assertTrue($created->isAdmin());
        $this->assertTrue($created->hasStaffRole(StaffRoleSlug::Recruiter->value));
        $this->assertFalse($created->isSuperAdmin());
        $this->assertArrayNotHasKey('password', $created->toAdminArray());
        $this->assertTrue(SecurityActivity::query()->where('action', 'user_created')->where('subject_id', $created->id)->exists());
        $this->assertTrue(LoginLog::query()->where('email', $created->email)->where('event', LoginEvent::PasswordResetRequest->value)->exists());
        $this->assertStringNotContainsString('password', json_encode(LoginLog::query()->latest('id')->first()->metadata ?? []));
    }

    public function test_candidate_and_employer_remain_outside_admin_rbac(): void
    {
        $candidate = $this->makeCandidate();
        $employer = $this->makeEmployer();

        $this->assertFalse($candidate->canManageRecruitment());
        $this->assertFalse($employer->canManageRecruitment());
        $this->assertFalse($candidate->hasPermission(PermissionName::JobsView->value));
        $this->actingAs($candidate)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($employer)->get(route('admin.finance.index'))->assertForbidden();
    }
}
