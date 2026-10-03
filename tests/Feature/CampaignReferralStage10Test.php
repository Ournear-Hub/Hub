<?php

namespace Tests\Feature;

use App\Enums\CampaignStatus;
use App\Enums\PermissionName;
use App\Enums\RecruitmentActivityType;
use App\Enums\RegistrationSource;
use App\Enums\SecurityActivityType;
use App\Enums\ShareChannel;
use App\Enums\StaffRoleSlug;
use App\Enums\UserRole;
use App\Models\Campaign;
use App\Models\CommissionPayment;
use App\Models\EmployerAttribution;
use App\Models\Invoice;
use App\Models\Job;
use App\Models\Payment;
use App\Models\RecruitmentActivity;
use App\Models\Referral;
use App\Models\ReferralLink;
use App\Models\SecurityActivity;
use App\Models\ShareEvent;
use App\Models\Task;
use App\Models\User;
use App\Services\ReferralAttributionService;
use App\Support\AccessCatalog;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CampaignReferralStage10Test extends TestCase
{
    public function test_schema_adds_campaign_tables_without_forbidden_duplicates(): void
    {
        $this->assertTrue(Schema::hasTable('campaigns'));
        $this->assertTrue(Schema::hasTable('referral_links'));
        $this->assertTrue(Schema::hasTable('share_events'));
        $this->assertTrue(Schema::hasTable('referrals'));
        $this->assertTrue(Schema::hasColumn('recruitment_activities', 'campaign_id'));
        $this->assertTrue(Schema::hasColumn('recruitment_activities', 'referral_link_id'));
        $this->assertTrue(Schema::hasColumn('recruitment_activities', 'share_event_id'));
        $this->assertTrue(Schema::hasColumn('recruitment_activities', 'referral_id'));
        $this->assertTrue(Schema::hasColumn('tasks', 'campaign_id'));
        $this->assertTrue(Schema::hasColumn('tasks', 'referral_link_id'));
        $this->assertTrue(Schema::hasColumn('tasks', 'referral_id'));
        $this->assertFalse(Schema::hasTable('campaign_activities'));
        $this->assertFalse(Schema::hasTable('communication_templates'));
        $this->assertFalse(Schema::hasTable('campaign_deliveries'));
        $this->assertFalse(Schema::hasTable('coins'));
        $this->assertFalse(Schema::hasTable('wallets'));
        $this->assertFalse(Schema::hasTable('badges'));
        $this->assertFalse(Schema::hasTable('qr_codes'));
        $this->assertCount(34, PermissionName::cases());
        $this->assertEqualsCanonicalizing(AccessCatalog::permissionNames(), array_map(
            fn (PermissionName $permission) => $permission->value,
            PermissionName::cases(),
        ));
        $this->assertContains(PermissionName::CampaignsView->value, AccessCatalog::permissionNames());
        $this->assertContains(PermissionName::CampaignsManage->value, AccessCatalog::permissionNames());
        $this->assertNotContains('campaigns.send', AccessCatalog::permissionNames());
        $this->assertNotContains('referrals.manage', AccessCatalog::permissionNames());
        $this->assertFalse(in_array(PermissionName::CampaignsView->value, AccessCatalog::rolePermissions()[StaffRoleSlug::Recruiter->value], true));
        $this->assertFalse(in_array(PermissionName::CampaignsManage->value, AccessCatalog::rolePermissions()[StaffRoleSlug::Finance->value], true));
        $this->assertFalse(in_array(PermissionName::CampaignsManage->value, AccessCatalog::rolePermissions()[StaffRoleSlug::ContentManager->value], true));
        $this->assertTrue(in_array(PermissionName::CampaignsView->value, AccessCatalog::rolePermissions()[StaffRoleSlug::Viewer->value], true));
        $this->assertSame([
            CampaignStatus::Draft,
            CampaignStatus::Active,
            CampaignStatus::Paused,
            CampaignStatus::Closed,
        ], CampaignStatus::cases());
        $this->assertSame([
            ShareChannel::Whatsapp,
            ShareChannel::Copy,
            ShareChannel::Native,
        ], ShareChannel::cases());
        $this->assertStringNotContainsString('StaffRoleSlug::Recruiter', file_get_contents(app_path('Services/CampaignService.php')));
        $this->assertStringNotContainsString('StaffRoleSlug::Recruiter', file_get_contents(app_path('Services/ReferralLinkService.php')));
        $this->assertStringNotContainsString('StaffRoleSlug::Recruiter', file_get_contents(app_path('Services/ReferralAttributionService.php')));
        $this->assertStringNotContainsString('StaffRoleSlug::Recruiter', file_get_contents(app_path('Services/ShareTrackingService.php')));
        $this->assertStringNotContainsString('website.manage', file_get_contents(app_path('Policies/CampaignPolicy.php')));
        $this->assertStringNotContainsString('users.manage', file_get_contents(app_path('Policies/CampaignPolicy.php')));
        $this->assertStringNotContainsString('employers.manage', file_get_contents(app_path('Policies/CampaignPolicy.php')));
    }

    public function test_admin_workspace_is_permission_gated_and_has_no_public_portal(): void
    {
        $admin = $this->makeAdmin();
        $recruiter = $this->makeStaff(StaffRoleSlug::Recruiter->value);
        $finance = $this->makeStaff(StaffRoleSlug::Finance->value);
        $content = $this->makeStaff(StaffRoleSlug::ContentManager->value);
        $viewer = $this->makeStaff(StaffRoleSlug::Viewer->value);

        $this->actingAs($admin)
            ->post(route('admin.campaigns.store'), [
                'name' => 'Spring Outreach',
                'summary' => 'Public job sharing',
            ])
            ->assertRedirect();

        $campaign = Campaign::query()->where('name', 'Spring Outreach')->first();
        $this->assertNotNull($campaign);
        $this->assertSame(CampaignStatus::Draft, $campaign->status);
        $this->assertTrue(
            RecruitmentActivity::query()
                ->where('campaign_id', $campaign->id)
                ->where('type', RecruitmentActivityType::CampaignCreated)
                ->exists()
        );

        $this->actingAs($admin)
            ->post(route('admin.campaigns.status', $campaign), ['status' => CampaignStatus::Active->value])
            ->assertRedirect();

        $this->assertSame(CampaignStatus::Active, $campaign->fresh()->status);
        $this->assertTrue(
            SecurityActivity::query()
                ->where('action', SecurityActivityType::CampaignActivated)
                ->exists()
        );

        $this->actingAs($admin)
            ->post(route('admin.campaigns.links.store'), [
                'campaign_id' => $campaign->id,
                'path' => '/jobs',
                'code' => 'SPRING01',
            ])
            ->assertRedirect();

        $link = ReferralLink::query()->where('code', 'SPRING01')->first();
        $this->assertNotNull($link);
        $this->assertStringContainsString('ref=SPRING01', $link->publicUrl());

        $this->actingAs($admin)
            ->post(route('admin.campaigns.links.store'), [
                'path' => '/admin/users',
            ])
            ->assertSessionHasErrors('path');

        $this->actingAs($recruiter)->get(route('admin.campaigns.index'))->assertForbidden();
        $this->actingAs($finance)->get(route('admin.campaigns.index'))->assertForbidden();
        $this->actingAs($content)->get(route('admin.campaigns.index'))->assertForbidden();
        $this->actingAs($recruiter)->post(route('admin.campaigns.store'), ['name' => 'Nope'])->assertForbidden();
        $this->actingAs($finance)->post(route('admin.campaigns.store'), ['name' => 'Nope'])->assertForbidden();
        $this->actingAs($content)->post(route('admin.campaigns.store'), ['name' => 'Nope'])->assertForbidden();

        $this->actingAs($viewer)
            ->get(route('admin.campaigns.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Campaigns/Index')
                ->where('can_manage', false)
                ->has('campaigns.data', 1)
                ->has('links.data')
                ->has('shares.data')
                ->has('referrals.data'));

        $this->actingAs($viewer)
            ->post(route('admin.campaigns.status', $campaign), ['status' => CampaignStatus::Paused->value])
            ->assertForbidden();

        $this->actingAs($this->makeCandidate())->get(route('admin.campaigns.index'))->assertForbidden();
        $this->actingAs($this->makeEmployer())->post(route('admin.campaigns.store'), ['name' => 'Nope'])->assertForbidden();
        $this->get('/campaigns')->assertNotFound();
        $this->get('/referrals')->assertNotFound();
    }

    public function test_referral_code_is_first_touch_and_ignores_unusable_campaigns(): void
    {
        $admin = $this->makeAdmin();
        $this->createActiveCampaignWithLink($admin, 'ACTIVE01', 'Active Share');
        $this->createActiveCampaignWithLink($admin, 'ACTIVE02', 'Second Share');

        $this->actingAs($admin)
            ->post(route('admin.campaigns.store'), ['name' => 'Draft Only'])
            ->assertRedirect();
        $draftCampaign = Campaign::query()->where('name', 'Draft Only')->first();
        $this->assertNotNull($draftCampaign);
        $this->actingAs($admin)
            ->post(route('admin.campaigns.links.store'), [
                'campaign_id' => $draftCampaign->id,
                'path' => '/register',
                'code' => 'DRAFT01',
            ])
            ->assertRedirect();

        $this->forgetAuthentication();
        $this->get('/jobs?ref=DRAFT01')
            ->assertOk()
            ->assertCookieMissing(ReferralLink::COOKIE_NAME);

        $this->get('/jobs?ref=ACTIVE01')
            ->assertOk()
            ->assertPlainCookie(ReferralLink::COOKIE_NAME, 'ACTIVE01');

        $this->withUnencryptedCookie(ReferralLink::COOKIE_NAME, 'ACTIVE01')
            ->get('/jobs?ref=ACTIVE02')
            ->assertOk();

        $this->withUnencryptedCookie(ReferralLink::COOKIE_NAME, 'ACTIVE01')
            ->post(route('register.store'), $this->registrationPayload([
                'email' => 'first.touch@example.com',
                'username' => 'firsttouch',
            ]))->assertRedirect();

        $user = User::query()->where('email', 'first.touch@example.com')->first();
        $this->assertNotNull($user);
        $referral = Referral::query()->where('referred_user_id', $user->id)->first();
        $this->assertNotNull($referral);
        $this->assertSame('ACTIVE01', $referral->referralLink?->code);
        $this->assertTrue($referral->is_primary);
        $this->assertSame(RegistrationSource::Campaign, $user->registration_source);
        $this->assertSame(0, EmployerAttribution::query()->count());
    }

    public function test_registration_source_changes_only_after_referral_row_exists(): void
    {
        $admin = $this->makeAdmin();
        $this->createActiveCampaignWithLink($admin, 'CANDREF1', 'Candidate Campaign');
        $this->actingAs($admin)
            ->post(route('admin.campaigns.links.store'), [
                'path' => '/register',
                'code' => 'BARELINK',
            ])
            ->assertRedirect();

        $this->forgetAuthentication();
        $this->post(route('register.store'), $this->registrationPayload([
            'email' => 'plain.site@example.com',
            'username' => 'plainsite',
        ]))->assertRedirect();

        $plain = User::query()->where('email', 'plain.site@example.com')->first();
        $this->assertNotNull($plain);
        $this->assertSame(RegistrationSource::Website, $plain->registration_source);
        $this->assertSame(0, Referral::query()->where('referred_user_id', $plain->id)->count());

        $this->forgetAuthentication();
        $this->withUnencryptedCookie(ReferralLink::COOKIE_NAME, 'CANDREF1')
            ->post(route('register.store'), $this->registrationPayload([
                'email' => 'referred.candidate@example.com',
                'username' => 'refcandidate',
            ]))
            ->assertRedirect();

        $candidate = User::query()->where('email', 'referred.candidate@example.com')->first();
        $this->assertNotNull($candidate);
        $this->assertSame(1, Referral::query()->where('referred_user_id', $candidate->id)->count());
        $this->assertSame(RegistrationSource::Campaign, $candidate->fresh()->registration_source);

        $this->forgetAuthentication();
        $this->withUnencryptedCookie(ReferralLink::COOKIE_NAME, 'BARELINK')
            ->post(route('register.store'), $this->registrationPayload([
                'name' => 'Referred Employer',
                'email' => 'referred.employer@example.com',
                'username' => 'refemployer',
                'role' => UserRole::Employer->value,
            ]))
            ->assertRedirect();

        $employer = User::query()->where('email', 'referred.employer@example.com')->first();
        $this->assertNotNull($employer);
        $this->assertTrue($employer->isEmployer());
        $this->assertSame(RegistrationSource::Referral, $employer->registration_source);
        $this->assertSame(0, EmployerAttribution::query()->count());
        $this->assertSame(0, Invoice::query()->count());
        $this->assertSame(0, Payment::query()->count());
        $this->assertSame(0, CommissionPayment::query()->count());
    }

    public function test_job_shares_and_applications_record_public_referral_not_partner_attribution(): void
    {
        $admin = $this->makeAdmin();
        [$campaign, $link] = $this->createActiveCampaignWithLink($admin, 'APPLY001', 'Apply Campaign');
        $job = Job::factory()->create(['title' => 'Shared Site Role']);
        $other = Job::factory()->create(['title' => 'Second Site Role']);

        $this->forgetAuthentication();
        $this->from(route('jobs.show', $job))
            ->post(route('jobs.share', $job), ['channel' => ShareChannel::Whatsapp->value])
            ->assertRedirect();
        $this->from(route('jobs.show', $job))
            ->post(route('jobs.share', $job), ['channel' => ShareChannel::Copy->value])
            ->assertRedirect();
        $this->from(route('jobs.show', $job))
            ->post(route('jobs.share', $job), ['channel' => ShareChannel::Native->value])
            ->assertRedirect();
        $this->from(route('jobs.show', $job))
            ->post(route('jobs.share', $job), ['channel' => 'linkedin'])
            ->assertSessionHasErrors('channel');

        $this->assertSame(3, ShareEvent::query()->where('job_id', $job->id)->count());
        $this->assertFalse(
            RecruitmentActivity::query()
                ->where('type', RecruitmentActivityType::ShareRecorded)
                ->exists()
        );

        $candidate = $this->makeCandidate(['email' => 'share.apply@example.com']);
        $this->actingAs($candidate)
            ->from(route('jobs.show', $job))
            ->withUnencryptedCookie(ReferralLink::COOKIE_NAME, 'APPLY001')
            ->post(route('jobs.share', $job), ['channel' => ShareChannel::Copy->value])
            ->assertRedirect();

        $this->assertTrue(
            RecruitmentActivity::query()
                ->where('type', RecruitmentActivityType::ShareRecorded)
                ->where('campaign_id', $campaign->id)
                ->exists()
        );

        if (! $this->candidateHasResume($candidate)) {
            $this->giveCandidateResume($candidate);
        }

        $this->actingAs($candidate)
            ->withUnencryptedCookie(ReferralLink::COOKIE_NAME, 'APPLY001')
            ->post(route('jobs.apply.store', $job))
            ->assertRedirect();

        $application = $candidate->applications()->where('job_id', $job->id)->first();
        $this->assertNotNull($application);
        $this->assertSame(RegistrationSource::Website, $candidate->fresh()->registration_source);
        $referral = Referral::query()->where('application_id', $application->id)->first();
        $this->assertNotNull($referral);
        $this->assertTrue($referral->is_primary);
        $this->assertSame($link->id, $referral->referral_link_id);
        $this->assertTrue(
            SecurityActivity::query()
                ->where('action', SecurityActivityType::ReferralAttributed)
                ->exists()
        );

        $duplicate = app(ReferralAttributionService::class)->attributeApplication(
            $application,
            request()->merge(['ref' => 'APPLY001']),
        );
        $this->assertNull($duplicate);

        $this->actingAs($candidate)
            ->withUnencryptedCookie(ReferralLink::COOKIE_NAME, 'APPLY001')
            ->post(route('jobs.apply.store', $other))
            ->assertRedirect();
        $second = $candidate->applications()->where('job_id', $other->id)->first();
        $this->assertNotNull($second);
        $this->assertNotNull(Referral::query()->where('application_id', $second->id)->first());
        $this->assertSame(2, Referral::query()->where('referred_user_id', $candidate->id)->count());
        $this->assertSame(0, EmployerAttribution::query()->count());

        $self = $this->makeCandidate(['email' => 'self.ref@example.com']);
        $this->actingAs($admin)
            ->post(route('admin.campaigns.links.store'), [
                'campaign_id' => $campaign->id,
                'path' => '/jobs',
                'code' => 'SELF0001',
                'referrer_user_id' => $self->id,
            ])
            ->assertRedirect();
        if (! $this->candidateHasResume($self)) {
            $this->giveCandidateResume($self);
        }
        $selfJob = Job::factory()->create();
        $this->actingAs($self)
            ->withUnencryptedCookie(ReferralLink::COOKIE_NAME, 'SELF0001')
            ->post(route('jobs.apply.store', $selfJob))
            ->assertRedirect();
        $selfApplication = $self->applications()->where('job_id', $selfJob->id)->first();
        $this->assertNotNull($selfApplication);
        $this->assertNull(Referral::query()->where('application_id', $selfApplication->id)->first());
    }

    public function test_tasks_my_work_and_activity_reuse_existing_infrastructure(): void
    {
        $admin = $this->makeAdmin();
        [$campaign] = $this->createActiveCampaignWithLink($admin, 'WORK0001', 'Work Campaign');

        $this->actingAs($admin)
            ->post(route('admin.tasks.store'), [
                'title' => 'Review Spring conversions',
                'assigned_to' => $admin->id,
                'campaign_id' => $campaign->id,
            ])
            ->assertRedirect();

        $task = Task::query()->where('title', 'Review Spring conversions')->first();
        $this->assertNotNull($task);
        $this->assertTrue($task->isCampaignLinked());
        $this->assertSame($campaign->id, $task->campaign_id);

        $this->actingAs($admin)
            ->get(route('admin.work.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Work/Index')
                ->has('recent_referrals')
                ->has('tasks'));

        $this->actingAs($admin)
            ->get(route('admin.campaigns.index', ['campaign' => $campaign->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Campaigns/Index')
                ->where('can_manage', true)
                ->where('selected.id', $campaign->id)
                ->has('assigned_work', 1)
                ->has('activity'));
    }

    /**
     * @return array{0: Campaign, 1: ReferralLink}
     */
    private function createActiveCampaignWithLink(User $admin, string $code, string $name): array
    {
        $this->actingAs($admin)
            ->post(route('admin.campaigns.store'), ['name' => $name])
            ->assertRedirect();

        $campaign = Campaign::query()->where('name', $name)->first();
        $this->assertNotNull($campaign);

        $this->actingAs($admin)
            ->post(route('admin.campaigns.status', $campaign), ['status' => CampaignStatus::Active->value])
            ->assertRedirect();

        $this->actingAs($admin)
            ->post(route('admin.campaigns.links.store'), [
                'campaign_id' => $campaign->id,
                'path' => '/jobs',
                'code' => $code,
            ])
            ->assertRedirect();

        $link = ReferralLink::query()->where('code', $code)->first();
        $this->assertNotNull($link);

        return [$campaign->fresh() ?? $campaign, $link];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
}
