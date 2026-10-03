<?php

namespace Tests\Feature;

use App\Enums\AutomationActionType;
use App\Enums\AutomationScope;
use App\Enums\AutomationTrigger;
use App\Enums\DocumentType;
use App\Enums\PermissionName;
use App\Enums\StaffRoleSlug;
use App\Models\Application;
use App\Models\CandidateDocument;
use App\Models\CandidateResume;
use App\Models\Permission;
use App\Models\RecruitmentAutomation;
use App\Models\Role;
use App\Models\User;
use App\Services\AuthorizationBootstrapper;
use App\Support\AccessCatalog;
use Tests\TestCase;

class AdminRbacStage25Test extends TestCase
{
    public function test_staff_without_users_manage_cannot_mutate_users(): void
    {
        $recruiter = $this->makeStaff(StaffRoleSlug::Recruiter->value);
        $target = $this->makeStaff(StaffRoleSlug::Viewer->value);
        $role = Role::query()->where('slug', StaffRoleSlug::Recruiter->value)->firstOrFail();

        $this->actingAs($recruiter)
            ->post(route('admin.users.store'), [
                'name' => 'Intruder',
                'email' => 'stage25-intruder@example.com',
                'role_ids' => [$role->id],
            ])
            ->assertForbidden();

        $this->actingAs($recruiter)
            ->put(route('admin.users.update', $target), [
                'name' => 'Hijacked',
                'email' => 'hijacked-stage25@example.com',
            ])
            ->assertForbidden();

        $this->actingAs($recruiter)
            ->put(route('admin.users.roles', $target), [
                'role_ids' => [$role->id],
            ])
            ->assertForbidden();

        $this->assertSame($target->email, $target->fresh()->email);
        $this->assertNull(User::query()->where('email', 'stage25-intruder@example.com')->first());
    }

    public function test_staff_without_automation_manage_cannot_create_or_enable_automation(): void
    {
        $finance = $this->makeStaff(StaffRoleSlug::Finance->value);
        $admin = $this->makeAdmin();

        $this->actingAs($finance)
            ->post(route('admin.automations.store'), [
                'name' => 'Finance automation',
                'trigger' => AutomationTrigger::ApplicationSubmitted->value,
                'scope' => AutomationScope::Global->value,
                'actions' => [AutomationActionType::CreateTask->value],
            ])
            ->assertForbidden();

        $this->actingAs($admin)
            ->post(route('admin.automations.store'), [
                'name' => 'Allowed automation',
                'trigger' => AutomationTrigger::ApplicationSubmitted->value,
                'scope' => AutomationScope::Global->value,
                'actions' => [AutomationActionType::CreateTask->value],
            ])
            ->assertRedirect();

        $automation = RecruitmentAutomation::query()->where('name', 'Allowed automation')->firstOrFail();

        $this->actingAs($finance)
            ->post(route('admin.automations.toggle', $automation))
            ->assertForbidden();

        $this->assertSame(1, RecruitmentAutomation::query()->count());
    }

    public function test_staff_without_candidate_permission_cannot_download_private_documents(): void
    {
        $candidate = $this->makeCandidate();
        $this->actingAs($candidate)->post(route('account.resumes.store'), [
            'file' => $this->fakeResume('stage25.pdf'),
            'title' => 'Stage 25 CV',
        ])->assertRedirect();
        $this->actingAs($candidate)->post(route('account.documents.store'), [
            'file' => $this->fakeDocument('stage25-cert.pdf'),
            'document_type' => DocumentType::Certificate->value,
            'title' => 'Stage 25 certificate',
        ])->assertRedirect();

        $resume = CandidateResume::query()
            ->where('candidate_profile_id', $candidate->fresh()->candidateProfile?->id)
            ->firstOrFail();
        $document = CandidateDocument::query()
            ->where('candidate_profile_id', $candidate->fresh()->candidateProfile?->id)
            ->firstOrFail();

        $finance = $this->makeStaff(StaffRoleSlug::Finance->value);

        $this->actingAs($finance)
            ->get(route('admin.candidates.resumes.show', $resume))
            ->assertForbidden();
        $this->actingAs($finance)
            ->get(route('admin.candidates.documents.show', $document))
            ->assertForbidden();
    }

    public function test_staff_without_hiring_permission_cannot_shortlist(): void
    {
        $viewer = $this->makeStaff(StaffRoleSlug::Viewer->value);
        $application = Application::factory()->create();

        $this->actingAs($viewer)
            ->post(route('admin.applications.shortlist', $application))
            ->assertForbidden();

        $this->assertNotSame(\App\Enums\ApplicationStatus::Shortlisted, $application->fresh()->status);
    }

    public function test_invalid_permission_and_role_identifiers_are_rejected(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->post(route('admin.roles.store'), [
            'name' => 'Stage 25 Desk',
        ])->assertRedirect();

        $role = Role::query()->where('slug', 'stage_25_desk')->firstOrFail();
        $target = $this->makeStaff(StaffRoleSlug::Viewer->value);

        $this->actingAs($admin)
            ->put(route('admin.roles.permissions', $role), [
                'permission_ids' => [999999],
            ])
            ->assertSessionHasErrors('permission_ids.0');

        $this->actingAs($admin)
            ->put(route('admin.users.roles', $target), [
                'role_ids' => [999999],
            ])
            ->assertSessionHasErrors('role_ids.0');

        $this->assertSame(0, $role->permissions()->count());
        $this->assertTrue($target->fresh()->hasStaffRole(StaffRoleSlug::Viewer->value));
    }

    public function test_ordinary_admin_cannot_escalate_via_custom_role_assignment(): void
    {
        $super = $this->makeAdmin();
        $admin = $this->makeStaff(StaffRoleSlug::Admin->value);
        $target = $this->makeStaff(StaffRoleSlug::Recruiter->value);

        $this->actingAs($super)->post(route('admin.roles.store'), [
            'name' => 'Shadow Admin',
        ])->assertRedirect();

        $shadow = Role::query()->where('slug', 'shadow_admin')->firstOrFail();
        $rolesManage = Permission::query()->where('name', PermissionName::RolesManage->value)->firstOrFail();
        $usersManage = Permission::query()->where('name', PermissionName::UsersManage->value)->firstOrFail();

        $this->actingAs($super)
            ->put(route('admin.roles.permissions', $shadow), [
                'permission_ids' => [$rolesManage->id, $usersManage->id],
            ])
            ->assertSessionHasErrors('permission_ids');

        $shadow->permissions()->sync([$rolesManage->id, $usersManage->id]);

        $this->actingAs($admin)
            ->put(route('admin.users.roles', $target), [
                'role_ids' => [$shadow->id],
            ])
            ->assertSessionHasErrors('role_ids');

        $this->actingAs($admin)
            ->put(route('admin.users.roles', $admin), [
                'role_ids' => [$shadow->id],
            ])
            ->assertSessionHasErrors('role_ids');

        $this->assertTrue($target->fresh()->hasStaffRole(StaffRoleSlug::Recruiter->value));
        $this->assertFalse($admin->fresh()->hasPermission(PermissionName::RolesManage->value));
    }

    public function test_bootstrapper_preserves_catalog_role_extras_and_strips_roles_manage(): void
    {
        $recruiter = Role::query()->where('slug', StaffRoleSlug::Recruiter->value)->firstOrFail();
        $usersManage = Permission::query()->where('name', PermissionName::UsersManage->value)->firstOrFail();
        $rolesManage = Permission::query()->where('name', PermissionName::RolesManage->value)->firstOrFail();

        $recruiter->permissions()->syncWithoutDetaching([$usersManage->id, $rolesManage->id]);
        $this->assertTrue($recruiter->fresh()->permissions()->where('name', PermissionName::UsersManage->value)->exists());
        $this->assertTrue($recruiter->fresh()->permissions()->where('name', PermissionName::RolesManage->value)->exists());

        app(AuthorizationBootstrapper::class)->run();

        $recruiter->refresh();
        $this->assertTrue($recruiter->permissions()->where('name', PermissionName::UsersManage->value)->exists());
        $this->assertTrue($recruiter->permissions()->where('name', PermissionName::JobsView->value)->exists());
        $this->assertFalse($recruiter->permissions()->where('name', PermissionName::RolesManage->value)->exists());
        $this->assertEqualsCanonicalizing(
            AccessCatalog::permissionNames(),
            Role::query()->where('slug', StaffRoleSlug::SuperAdmin->value)->firstOrFail()->permissions()->pluck('name')->all(),
        );
    }

    public function test_catalog_role_cannot_drop_standard_permissions(): void
    {
        $admin = $this->makeAdmin();
        $recruiter = Role::query()->where('slug', StaffRoleSlug::Recruiter->value)->firstOrFail();
        $jobsView = Permission::query()->where('name', PermissionName::JobsView->value)->firstOrFail();

        $this->actingAs($admin)
            ->put(route('admin.roles.permissions', $recruiter), [
                'permission_ids' => [$jobsView->id],
            ])
            ->assertSessionHasErrors('permission_ids');

        $this->assertEqualsCanonicalizing(
            AccessCatalog::rolePermissions()[StaffRoleSlug::Recruiter->value],
            $recruiter->fresh()->permissions()->pluck('name')->all(),
        );
    }

    public function test_content_manager_cannot_open_operational_analytics(): void
    {
        $content = $this->makeStaff(StaffRoleSlug::ContentManager->value);
        $recruiter = $this->makeStaff(StaffRoleSlug::Recruiter->value);

        $this->actingAs($content)->get(route('admin.analytics.index'))->assertForbidden();
        $this->actingAs($content)->get(route('admin.dashboard'))->assertOk();
        $this->actingAs($recruiter)->get(route('admin.analytics.index'))->assertOk();
    }
}
