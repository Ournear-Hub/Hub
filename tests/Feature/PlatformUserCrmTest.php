<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\BusinessIdPrefix;
use App\Enums\CommunicationChannel;
use App\Enums\StaffRoleSlug;
use App\Enums\UserActivityType;
use App\Models\Brand;
use App\Models\Role;
use App\Models\User;
use App\Models\UserActivity;
use App\Models\UserCommunicationPreference;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PlatformUserCrmTest extends TestCase
{
    public function test_user_crm_lists_platform_identities_without_duplicating_candidates(): void
    {
        $admin = $this->makeAdmin();
        $candidate = $this->makeCandidate([
            'name' => 'Asha Candidate',
            'email' => 'asha.crm@example.com',
        ]);
        $employer = $this->makeEmployer([
            'name' => 'Ravi Employer',
            'email' => 'ravi.crm@example.com',
        ]);

        $this->assertSame(1, User::query()->where('email', 'asha.crm@example.com')->count());
        $this->assertTrue($candidate->candidateProfile()->exists());
        $this->assertMatchesRegularExpression('/^ONT-U-\d{6}$/', (string) $candidate->business_id);
        $this->assertNotNull($candidate->username);
        $this->assertSame(Brand::query()->where('is_primary', true)->value('id'), $candidate->brand_id);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Users/Index')
                ->has('users.data')
                ->has('options.segments'));

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['segment' => 'candidates']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.segment', 'candidates')
                ->where('users.data.0.email', $candidate->email)
                ->where('users.data.0.account_type', 'candidate'));

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['segment' => 'employers']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('users.data.0.email', $employer->email));
    }

    public function test_user_360_and_activity_workspaces_open(): void
    {
        $admin = $this->makeAdmin();
        $candidate = $this->makeCandidate(['name' => 'Priya User']);

        $this->actingAs($admin)
            ->get(route('admin.users.show', $candidate))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Users/Show')
                ->where('record.user.id', $candidate->id)
                ->where('record.user.name', 'Priya User')
                ->where('record.candidate.id', $candidate->candidateProfile?->id)
                ->has('record.communication_preferences')
                ->has('record.activities'));

        $this->actingAs($admin)
            ->get(route('admin.users.activity'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Users/Activity'));

        $this->assertTrue(
            UserActivity::query()
                ->where('user_id', $candidate->id)
                ->where('type', UserActivityType::Registered->value)
                ->exists()
        );
    }

    public function test_admin_can_update_user_status_preferences_and_profile(): void
    {
        $admin = $this->makeAdmin();
        $candidate = $this->makeCandidate([
            'name' => 'Status User',
            'email' => 'status.user@example.com',
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.users.status', $candidate), [
                'account_status' => AccountStatus::Blocked->value,
            ])
            ->assertRedirect();

        $candidate->refresh();
        $this->assertSame(AccountStatus::Blocked, $candidate->accountStatus());
        $this->assertFalse($candidate->isActive());
        $this->assertTrue(
            UserActivity::query()->where('user_id', $candidate->id)->where('type', UserActivityType::Blocked->value)->exists()
        );

        $this->actingAs($admin)
            ->put(route('admin.users.preferences', $candidate), [
                'channels' => [
                    CommunicationChannel::MarketingEmail->value => true,
                    CommunicationChannel::System->value => true,
                ],
            ])
            ->assertRedirect();

        $this->assertTrue(
            UserCommunicationPreference::query()
                ->where('user_id', $candidate->id)
                ->where('channel', CommunicationChannel::MarketingEmail->value)
                ->where('opted_in', true)
                ->exists()
        );

        $this->actingAs($admin)
            ->put(route('admin.users.profile', $candidate), [
                'name' => 'Status User Updated',
                'username' => 'statususer',
                'email' => 'status.user@example.com',
                'mobile' => '9876543210',
                'location' => 'Hyderabad',
                'preferred_language' => 'te',
            ])
            ->assertRedirect();

        $candidate->refresh();
        $this->assertSame('Status User Updated', $candidate->name);
        $this->assertSame('statususer', $candidate->username);
        $this->assertSame('Hyderabad', $candidate->location);
    }

    public function test_staff_workspace_is_separate_from_platform_users(): void
    {
        Notification::fake();
        $admin = $this->makeAdmin();
        $recruiter = $this->makeStaff(StaffRoleSlug::Recruiter->value, [
            'email' => 'staff.recruiter@example.com',
        ]);
        $candidate = $this->makeCandidate(['email' => 'not-staff@example.com']);
        $role = Role::query()->where('slug', StaffRoleSlug::Finance->value)->first();

        $this->actingAs($admin)
            ->get(route('admin.staff.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Staff/Index')
                ->has('users.data'));

        $this->actingAs($admin)
            ->get(route('admin.staff.index', ['search' => 'not-staff@example.com']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Staff/Index')
                ->has('users.data', 0));

        $this->actingAs($admin)
            ->post(route('admin.staff.store'), [
                'name' => 'New Finance',
                'email' => 'new.finance@example.com',
                'role_ids' => [$role->id],
            ])
            ->assertRedirect();

        $created = User::query()->where('email', 'new.finance@example.com')->first();
        $this->assertNotNull($created);
        $this->assertTrue($created->isAdmin());
        $this->assertTrue($created->must_change_password);
        $this->assertArrayNotHasKey('password', $created->toAdminArray());
        $this->assertTrue($created->adminProfile()->exists());
        $this->assertNotSame($candidate->id, $recruiter->id);
    }

    public function test_admin_profile_is_separate_from_website_settings(): void
    {
        $admin = $this->makeAdmin(['name' => 'Venkat Admin']);

        $this->actingAs($admin)
            ->get(route('admin.settings.edit', ['tab' => 'account']))
            ->assertRedirect(route('admin.profile.show'));

        $this->actingAs($admin)
            ->get(route('admin.profile.show'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Profile/Show')
                ->where('profile.email', $admin->email));

        $this->actingAs($admin)
            ->put(route('admin.profile.update'), [
                'name' => 'Venkat Admin',
                'username' => 'venkatadmin',
                'email' => $admin->email,
                'mobile' => '9000000000',
                'location' => 'Hyderabad',
                'preferred_language' => 'en',
                'department' => 'Operations',
                'designation' => 'Super Admin',
                'joined_at' => '2026-01-01',
                'professional_details' => 'Platform owner',
                'qualifications' => 'MBA',
                'skills' => 'Recruitment',
                'employment_information' => 'OurNear',
            ])
            ->assertRedirect();

        $this->assertSame('Operations', $admin->fresh()->adminProfile?->department);
        $this->assertSame('venkatadmin', $admin->fresh()->username);
    }

    public function test_staff_must_change_password_before_using_admin(): void
    {
        $staff = $this->makeStaff(StaffRoleSlug::Recruiter->value, [
            'email' => 'temp.staff@example.com',
            'password' => 'password',
        ]);
        $staff->forceFill(['must_change_password' => true])->save();

        $this->actingAs($staff)
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.profile.password.edit'));

        $this->actingAs($staff)
            ->get(route('admin.profile.password.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Profile/Password')
                ->where('must_change', true));

        $this->actingAs($staff)
            ->put(route('admin.profile.password.update'), [
                'password' => 'password',
                'password_confirmation' => 'password',
            ])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertFalse((bool) $staff->fresh()->must_change_password);

        $this->actingAs($staff->fresh())
            ->get(route('admin.dashboard'))
            ->assertOk();
    }

    public function test_recruiter_cannot_open_user_crm_or_create_staff(): void
    {
        $recruiter = $this->makeStaff(StaffRoleSlug::Recruiter->value);
        $role = Role::query()->where('slug', StaffRoleSlug::Viewer->value)->first();

        $this->actingAs($recruiter)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($recruiter)->get(route('admin.staff.index'))->assertForbidden();
        $this->actingAs($recruiter)->post(route('admin.staff.store'), [
            'name' => 'Intruder',
            'email' => 'intruder-staff@example.com',
            'role_ids' => [$role->id],
        ])->assertForbidden();
    }

    public function test_user_business_id_prefix_matches_existing_convention(): void
    {
        $user = $this->makeCandidate();

        $this->assertSame(BusinessIdPrefix::User->value, 'ONT-U');
        $this->assertMatchesRegularExpression('/^ONT-U-\d{6}$/', (string) $user->business_id);
        $this->assertMatchesRegularExpression('/^ONT-C-\d{6}$/', (string) $user->candidateProfile?->business_id);
        $this->assertNotSame($user->business_id, $user->candidateProfile?->business_id);
    }
}
