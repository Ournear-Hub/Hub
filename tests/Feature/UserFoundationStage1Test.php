<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\RegistrationSource;
use App\Enums\SecurityActivityType;
use App\Enums\StaffRoleSlug;
use App\Enums\UserActivityType;
use App\Enums\UserRole;
use App\Models\SecurityActivity;
use App\Models\Tag;
use App\Models\User;
use App\Models\UserActivity;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UserFoundationStage1Test extends TestCase
{
    public function test_candidate_registration_defaults_to_website_source(): void
    {
        $this->post(route('register.store'), $this->registrationPayload([
            'role' => UserRole::Candidate->value,
            'email' => 'stage1.candidate@example.com',
            'username' => 'stage1candidate',
        ]))->assertRedirect();

        $user = User::query()->where('email', 'stage1.candidate@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->isCandidate());
        $this->assertSame(RegistrationSource::Website, $user->registration_source);
        $this->assertNotNull($user->last_activity_at);
        $this->assertTrue(
            UserActivity::query()
                ->where('user_id', $user->id)
                ->where('type', UserActivityType::Registered->value)
                ->exists()
        );
    }

    public function test_employer_registration_defaults_to_website_source(): void
    {
        $this->post(route('register.store'), $this->registrationPayload([
            'role' => UserRole::Employer->value,
            'email' => 'stage1.employer@example.com',
            'username' => 'stage1employer',
        ]))->assertRedirect();

        $user = User::query()->where('email', 'stage1.employer@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->isEmployer());
        $this->assertSame(RegistrationSource::Website, $user->registration_source);
    }

    public function test_admin_created_staff_defaults_to_admin_source(): void
    {
        Notification::fake();
        $admin = $this->makeAdmin();
        $role = \App\Models\Role::query()->where('slug', StaffRoleSlug::Finance->value)->first();
        $this->assertNotNull($role);

        $this->actingAs($admin)
            ->post(route('admin.staff.store'), [
                'name' => 'Stage1 Finance',
                'email' => 'stage1.finance@example.com',
                'role_ids' => [$role->id],
            ])
            ->assertRedirect();

        $created = User::query()->where('email', 'stage1.finance@example.com')->first();
        $this->assertNotNull($created);
        $this->assertTrue($created->isAdmin());
        $this->assertSame(RegistrationSource::Admin, $created->registration_source);
    }

    public function test_admin_can_filter_users_by_registration_source(): void
    {
        $admin = $this->makeAdmin();
        $website = $this->makeCandidate(['email' => 'source.website@example.com']);
        $referred = $this->makeCandidate([
            'email' => 'source.referral@example.com',
            'registration_source' => RegistrationSource::Referral,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['registration_source' => RegistrationSource::Referral->value]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Users/Index')
                ->where('filters.registration_source', RegistrationSource::Referral->value)
                ->has('users.data', 1)
                ->where('users.data.0.email', $referred->email));

        $this->assertSame(RegistrationSource::Website, $website->fresh()->registration_source);
    }

    public function test_admin_can_update_registration_source_and_it_is_audited(): void
    {
        $admin = $this->makeAdmin();
        $candidate = $this->makeCandidate(['email' => 'source.update@example.com']);

        $this->actingAs($admin)
            ->put(route('admin.users.source', $candidate), [
                'registration_source' => RegistrationSource::Campaign->value,
            ])
            ->assertRedirect();

        $this->assertSame(RegistrationSource::Campaign, $candidate->fresh()->registration_source);
        $this->assertTrue(
            SecurityActivity::query()
                ->where('action', SecurityActivityType::UserSourceChanged->value)
                ->where('subject_id', $candidate->id)
                ->exists()
        );
    }

    public function test_viewer_cannot_update_source_or_manage_tags(): void
    {
        $viewer = $this->makeStaff(StaffRoleSlug::Viewer->value);
        $candidate = $this->makeCandidate(['email' => 'viewer.blocked@example.com']);

        $this->actingAs($viewer)
            ->get(route('admin.users.index'))
            ->assertOk();

        $this->actingAs($viewer)
            ->put(route('admin.users.source', $candidate), [
                'registration_source' => RegistrationSource::Other->value,
            ])
            ->assertForbidden();

        $this->actingAs($viewer)
            ->post(route('admin.tags.store'), ['name' => 'ViewerTag'])
            ->assertForbidden();

        $this->assertSame(RegistrationSource::Website, $candidate->fresh()->registration_source);
        $this->assertSame(0, Tag::query()->count());
    }

    public function test_admin_can_create_rename_deactivate_and_filter_tags(): void
    {
        $admin = $this->makeAdmin();
        $candidate = $this->makeCandidate(['email' => 'tagged.user@example.com']);

        $this->actingAs($admin)
            ->post(route('admin.tags.store'), ['name' => 'Immediate Joiner'])
            ->assertRedirect();

        $tag = Tag::query()->where('name', 'Immediate Joiner')->first();
        $this->assertNotNull($tag);
        $this->assertTrue($tag->is_active);

        $this->actingAs($admin)
            ->put(route('admin.tags.update', $tag), ['name' => 'ImmediateJoiner'])
            ->assertRedirect();
        $this->assertSame('ImmediateJoiner', $tag->fresh()->name);

        $this->actingAs($admin)
            ->put(route('admin.users.tags', $candidate), ['tag_ids' => [$tag->id]])
            ->assertRedirect();
        $this->assertTrue($candidate->fresh()->tags->contains('id', $tag->id));

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['tag_id' => $tag->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.tag_id', (string) $tag->id)
                ->has('users.data', 1)
                ->where('users.data.0.email', $candidate->email));

        $this->actingAs($admin)
            ->put(route('admin.users.tags', $candidate), ['tag_ids' => []])
            ->assertRedirect();
        $this->assertCount(0, $candidate->fresh()->tags);

        $this->actingAs($admin)
            ->patch(route('admin.tags.status', $tag), ['is_active' => false])
            ->assertRedirect();
        $this->assertFalse($tag->fresh()->is_active);

        $this->actingAs($admin)
            ->put(route('admin.users.tags', $candidate), ['tag_ids' => [$tag->id]])
            ->assertRedirect()
            ->assertSessionHasErrors('tag_ids');
    }

    public function test_duplicate_tag_names_are_rejected(): void
    {
        $admin = $this->makeAdmin();
        Tag::query()->create(['name' => 'Mining', 'slug' => 'mining', 'is_active' => true]);

        $this->actingAs($admin)
            ->post(route('admin.tags.store'), ['name' => 'mining'])
            ->assertRedirect()
            ->assertSessionHasErrors('name');
    }

    public function test_invalid_registration_source_is_rejected(): void
    {
        $admin = $this->makeAdmin();
        $candidate = $this->makeCandidate();

        $this->actingAs($admin)
            ->put(route('admin.users.source', $candidate), [
                'registration_source' => 'not-a-source',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('registration_source');
    }

    public function test_admin_can_filter_users_by_last_activity(): void
    {
        $admin = $this->makeAdmin();
        $recent = $this->makeCandidate(['email' => 'recent.activity@example.com']);
        $older = $this->makeCandidate(['email' => 'older.activity@example.com']);

        $older->forceFill(['last_activity_at' => now()->subDays(10)])->saveQuietly();
        $recent->forceFill(['last_activity_at' => now()])->saveQuietly();

        $this->actingAs($admin)
            ->get(route('admin.users.index', [
                'last_activity_to' => now()->subDays(8)->toDateString(),
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('users.data', 1)
                ->where('users.data.0.email', $older->email));
    }

    public function test_last_activity_updates_when_activity_is_recorded(): void
    {
        $candidate = $this->makeCandidate();
        $this->assertNotNull($candidate->fresh()->last_activity_at);
        $before = $candidate->fresh()->last_activity_at;

        $this->travel(2)->minutes();

        app(\App\Services\UserActivityService::class)->record(
            $candidate,
            UserActivityType::UpdatedProfile,
            'Touched the profile.',
            $candidate,
        );

        $this->assertTrue($candidate->fresh()->last_activity_at->greaterThan($before));
    }

    public function test_existing_account_status_still_blocks_sign_in(): void
    {
        $admin = $this->makeAdmin();
        $candidate = $this->makeCandidate([
            'email' => 'status.still.works@example.com',
            'password' => Hash::make('Password1!'),
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.users.status', $candidate), [
                'account_status' => AccountStatus::Suspended->value,
            ])
            ->assertRedirect();

        $this->forgetAuthentication();

        $this->post(route('login.store'), [
            'login' => $candidate->email,
            'password' => 'Password1!',
        ])->assertSessionHasErrors();

        $this->assertGuest('account');
        $this->assertSame(AccountStatus::Suspended, $candidate->fresh()->accountStatus());
    }

    public function test_dual_guard_independence_still_holds(): void
    {
        $admin = $this->makeAdmin([
            'email' => 'stage1.dual.admin@example.com',
            'password' => Hash::make('password'),
        ]);
        $candidate = $this->makeCandidate([
            'username' => 'stage1dualcandidate',
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

        $this->assertAuthenticatedAs($admin, 'admin');
        $this->assertAuthenticatedAs($candidate, 'account');
        $this->get(route('admin.dashboard'))->assertOk();
        $this->get(route('account.home'))->assertOk();
        $this->get('/admin')->assertOk();
    }

}
