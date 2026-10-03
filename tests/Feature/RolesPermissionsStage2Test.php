<?php

namespace Tests\Feature;

use App\Enums\PermissionName;
use App\Enums\SecurityActivityType;
use App\Enums\StaffRoleSlug;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SecurityActivity;
use App\Support\AccessCatalog;
use App\Support\UserQuery;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RolesPermissionsStage2Test extends TestCase
{
    public function test_super_admin_can_create_a_role_and_assign_existing_permissions(): void
    {
        $admin = $this->makeAdmin();
        $catalogCount = Role::query()->whereIn('slug', $this->catalogSlugs())->count();
        $recruiterPermissions = $this->permissionNamesFor(StaffRoleSlug::Recruiter);

        $this->actingAs($admin)
            ->post(route('admin.roles.store'), [
                'name' => 'Talent Sourcer',
                'description' => 'Sourcing team created by Super Admin.',
            ])
            ->assertRedirect();

        $role = Role::query()->where('slug', 'talent_sourcer')->first();
        $this->assertNotNull($role);
        $this->assertSame('Talent Sourcer', $role->name);
        $this->assertFalse($role->is_locked);
        $this->assertSame(0, $role->permissions()->count());
        $this->assertTrue(SecurityActivity::query()->where('action', SecurityActivityType::RoleCreated->value)->where('subject_id', $role->id)->exists());

        $permissionIds = Permission::query()
            ->whereIn('name', [
                PermissionName::CandidatesView->value,
                PermissionName::CandidatesManage->value,
                PermissionName::JobsView->value,
            ])
            ->pluck('id')
            ->all();

        $this->actingAs($admin)
            ->put(route('admin.roles.permissions', $role), [
                'permission_ids' => $permissionIds,
            ])
            ->assertRedirect(route('admin.roles.index', ['role' => $role->id]));

        $role->refresh();
        $this->assertEqualsCanonicalizing([
            PermissionName::CandidatesView->value,
            PermissionName::CandidatesManage->value,
            PermissionName::JobsView->value,
        ], $role->permissions()->pluck('name')->all());

        $this->assertSame($catalogCount, Role::query()->whereIn('slug', $this->catalogSlugs())->count());
        $this->assertEqualsCanonicalizing($recruiterPermissions, $this->permissionNamesFor(StaffRoleSlug::Recruiter));
        $this->assertEqualsCanonicalizing(AccessCatalog::permissionNames(), $this->permissionNamesFor(StaffRoleSlug::SuperAdmin));
    }

    public function test_staff_with_a_created_role_receives_only_assigned_permissions(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->post(route('admin.roles.store'), [
            'name' => 'Support Desk',
            'description' => 'Helpdesk',
        ])->assertRedirect();

        $role = Role::query()->where('slug', 'support_desk')->firstOrFail();
        $permissionIds = Permission::query()
            ->whereIn('name', [
                PermissionName::UsersView->value,
                PermissionName::NotificationsView->value,
                PermissionName::MessagesManage->value,
            ])
            ->pluck('id')
            ->all();

        $this->actingAs($admin)
            ->put(route('admin.roles.permissions', $role), [
                'permission_ids' => $permissionIds,
            ])
            ->assertRedirect();

        $staff = $this->makeStaff('support_desk');

        $this->assertTrue($staff->hasPermission(PermissionName::UsersView->value));
        $this->assertTrue($staff->hasPermission(PermissionName::MessagesManage->value));
        $this->assertFalse($staff->hasPermission(PermissionName::UsersManage->value));
        $this->assertFalse($staff->hasPermission(PermissionName::FinanceManage->value));
        $this->assertFalse($staff->hasPermission(PermissionName::RolesManage->value));

        $this->actingAs($staff)->get(route('admin.users.index'))->assertOk();
        $this->actingAs($staff)->get(route('admin.messages.index'))->assertOk();
        $this->actingAs($staff)->get(route('admin.finance.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('admin.roles.index'))->assertForbidden();
        $this->actingAs($staff)->post(route('admin.users.store'), [
            'name' => 'Intruder',
            'email' => 'intruder-role@example.com',
            'role_ids' => [$role->id],
        ])->assertForbidden();
    }

    public function test_created_role_appears_on_roles_and_staff_assignment_lists(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->post(route('admin.roles.store'), [
            'name' => 'CRM Desk',
        ])->assertRedirect();

        $role = Role::query()->where('slug', 'crm_desk')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.roles.index', ['role' => $role->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Roles/Index')
                ->has('roles')
                ->where('selected.id', $role->id)
                ->where('selected.slug', 'crm_desk')
                ->where('selected.is_locked', false));

        $this->actingAs($admin)
            ->get(route('admin.staff.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Staff/Index')->has('options.roles'));

        $staffRoles = collect(app(UserQuery::class)->options()['roles']);
        $this->assertTrue($staffRoles->contains(
            fn (array $item) => $item['slug'] === 'crm_desk' && $item['assignable'] === true,
        ));
    }

    public function test_duplicate_and_reserved_role_names_are_rejected(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->post(route('admin.roles.store'), ['name' => 'Super Admin'])
            ->assertSessionHasErrors('name');

        $this->actingAs($admin)
            ->post(route('admin.roles.store'), ['name' => 'Recruiter'])
            ->assertSessionHasErrors('name');

        $this->actingAs($admin)
            ->from(route('admin.roles.index'))
            ->post(route('admin.roles.store'), ['name' => '  '])
            ->assertSessionHasErrors('name');

        $this->assertSame(count(StaffRoleSlug::cases()), Role::query()->count());
    }

    public function test_only_roles_manage_can_create_roles(): void
    {
        $admin = $this->makeStaff(StaffRoleSlug::Admin->value);
        $recruiter = $this->makeStaff(StaffRoleSlug::Recruiter->value);
        $candidate = $this->makeCandidate();
        $employer = $this->makeEmployer();

        $this->assertFalse($admin->can('create', Role::class));
        $this->assertFalse($recruiter->can('create', Role::class));
        $this->assertFalse($candidate->can('create', Role::class));

        $this->actingAs($admin)->post(route('admin.roles.store'), ['name' => 'Shadow Role'])->assertForbidden();
        $this->actingAs($recruiter)->post(route('admin.roles.store'), ['name' => 'Shadow Role'])->assertForbidden();
        $this->actingAs($candidate)->get(route('admin.roles.index'))->assertForbidden();
        $this->actingAs($employer)->post(route('admin.roles.store'), ['name' => 'Shadow Role'])->assertForbidden();

        $this->assertNull(Role::query()->where('slug', 'shadow_role')->first());
    }

    public function test_locked_super_admin_role_still_cannot_be_edited(): void
    {
        $actor = $this->makeAdmin();
        $super = Role::query()->where('slug', StaffRoleSlug::SuperAdmin->value)->firstOrFail();

        $this->actingAs($actor)
            ->put(route('admin.roles.permissions', $super), [
                'permission_ids' => [],
            ])
            ->assertForbidden();

        $this->assertEqualsCanonicalizing(
            AccessCatalog::permissionNames(),
            $super->fresh()->permissions()->pluck('name')->all(),
        );
    }

    /**
     * @return list<string>
     */
    private function catalogSlugs(): array
    {
        return array_map(fn (StaffRoleSlug $slug) => $slug->value, StaffRoleSlug::cases());
    }

    /**
     * @return list<string>
     */
    private function permissionNamesFor(StaffRoleSlug $slug): array
    {
        return Role::query()
            ->where('slug', $slug->value)
            ->firstOrFail()
            ->permissions()
            ->pluck('name')
            ->all();
    }
}
