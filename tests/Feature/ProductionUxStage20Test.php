<?php

namespace Tests\Feature;

use App\Enums\BusinessIdPrefix;
use App\Enums\CampaignStatus;
use App\Enums\PermissionName;
use App\Enums\RecruitmentActivityType;
use App\Enums\RegistrationSource;
use App\Enums\ShareChannel;
use App\Enums\StaffRoleSlug;
use App\Enums\UserRole;
use App\Models\Campaign;
use App\Models\CommissionEntry;
use App\Models\CommissionPayment;
use App\Models\EmployerAttribution;
use App\Models\Invoice;
use App\Models\Job;
use App\Models\Payment;
use App\Models\RecruitmentActivity;
use App\Models\Referral;
use App\Models\ReferralLink;
use App\Models\ShareEvent;
use App\Models\User;
use App\Support\AccessCatalog;
use Carbon\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProductionUxStage20Test extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_permission_catalog_and_ids_are_unchanged(): void
    {
        $this->assertCount(34, PermissionName::cases());
        $this->assertContains(PermissionName::CampaignsView->value, AccessCatalog::permissionNames());
        $this->assertContains(PermissionName::CampaignsManage->value, AccessCatalog::permissionNames());
        $this->assertNotContains('referrals.view', AccessCatalog::permissionNames());
        $this->assertNotContains('referrals.manage', AccessCatalog::permissionNames());
        $this->assertNotContains('analytics.view', AccessCatalog::permissionNames());
        $this->assertNotContains('marketing.view', AccessCatalog::permissionNames());
        $this->assertSame('ONT-B', BusinessIdPrefix::Campaign->value);
        $this->assertSame('ONT-L', BusinessIdPrefix::ReferralLink->value);
        $this->assertSame('ONT-X', BusinessIdPrefix::ShareEvent->value);
        $this->assertSame('ONT-Z', BusinessIdPrefix::Referral->value);
        $this->assertStringNotContainsString('campaigns', (string) file_get_contents(base_path('routes/account.php')));
        $this->assertStringNotContainsString('admin.campaigns', (string) file_get_contents(base_path('routes/account.php')));
        $source = (string) file_get_contents(resource_path('js/Components/Public/JobActions.tsx'));
        $this->assertStringContainsString('job.referral_code', $source);
        $this->assertStringNotContainsString('ourneer_ref', $source);
        $this->assertStringNotContainsString('document.cookie', $source);
        $analytics = (string) file_get_contents(app_path('Services/Admin/OperationalAnalyticsService.php'));
        $this->assertStringContainsString('Placement attribution and multi-touch attribution are not modeled', $analytics);
        $this->assertStringNotContainsString('chart', strtolower($analytics));
    }

    public function test_campaign_routes_remain_admin_permission_gated(): void
    {
        $admin = $this->makeStaff(StaffRoleSlug::Admin->value);
        $super = $this->makeAdmin();
        $viewer = $this->makeStaff(StaffRoleSlug::Viewer->value);
        $recruiter = $this->makeStaff(StaffRoleSlug::Recruiter->value);
        $finance = $this->makeStaff(StaffRoleSlug::Finance->value);
        $content = $this->makeStaff(StaffRoleSlug::ContentManager->value);
        [$campaign] = $this->createActiveCampaignWithLink($super, 'GATE0001', 'Gated Campaign');

        $this->actingAs($recruiter)->get(route('admin.campaigns.index'))->assertForbidden();
        $this->actingAs($finance)->get(route('admin.campaigns.index', ['campaign' => $campaign->id]))->assertForbidden();
        $this->actingAs($content)->post(route('admin.campaigns.store'), ['name' => 'Nope'])->assertForbidden();
        $this->actingAs($recruiter)->put(route('admin.campaigns.update', $campaign), ['name' => 'Nope'])->assertForbidden();
        $this->actingAs($this->makeCandidate())->get(route('admin.campaigns.index', ['tab' => 'conversions']))->assertForbidden();
        $this->actingAs($this->makeEmployer())->post(route('admin.campaigns.links.store'), ['path' => '/jobs'])->assertForbidden();

        $this->actingAs($viewer)
            ->get(route('admin.campaigns.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Campaigns/Index')
                ->where('can_manage', false));

        $this->actingAs($viewer)
            ->put(route('admin.campaigns.update', $campaign), [
                'name' => 'Viewer cannot edit',
            ])
            ->assertForbidden();

        $this->actingAs($viewer)
            ->post(route('admin.campaigns.links.disable', ReferralLink::query()->where('code', 'GATE0001')->first()))
            ->assertForbidden();

        $this->actingAs($admin)
            ->put(route('admin.campaigns.update', $campaign), [
                'name' => 'Admin can edit',
                'summary' => 'Updated summary',
            ])
            ->assertRedirect();

        $this->assertSame('Admin can edit', $campaign->fresh()->name);

        $this->actingAs($super)
            ->get(route('admin.campaigns.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('can_manage', true));
    }

    public function test_campaign_edit_dates_validation_and_update_activity(): void
    {
        Carbon::setTestNow('2026-09-28 12:00:00');
        $admin = $this->makeAdmin();
        $this->actingAs($admin)
            ->post(route('admin.campaigns.store'), [
                'name' => 'Date Window',
                'summary' => 'Original',
                'starts_at' => '2026-09-01 00:00:00',
                'ends_at' => '2026-12-31 23:59:59',
            ])
            ->assertRedirect();

        $campaign = Campaign::query()->where('name', 'Date Window')->first();
        $this->assertNotNull($campaign);
        $this->assertNotNull($campaign->starts_at);
        $this->assertNotNull($campaign->ends_at);

        $this->actingAs($admin)
            ->put(route('admin.campaigns.update', $campaign), [
                'name' => '',
            ])
            ->assertSessionHasErrors('name');

        $this->actingAs($admin)
            ->put(route('admin.campaigns.update', $campaign), [
                'name' => 'Date Window',
                'starts_at' => '2026-12-01 00:00:00',
                'ends_at' => '2026-11-01 00:00:00',
            ])
            ->assertSessionHasErrors('ends_at');

        $this->actingAs($admin)
            ->put(route('admin.campaigns.update', $campaign), [
                'name' => 'Spring Window',
                'summary' => 'Edited summary',
                'notes' => 'Internal ops note',
                'starts_at' => '2026-09-10 08:00:00',
                'ends_at' => '2026-10-10 18:00:00',
            ])
            ->assertRedirect();

        $campaign = $campaign->fresh();
        $this->assertSame('Spring Window', $campaign->name);
        $this->assertSame('Edited summary', $campaign->summary);
        $this->assertSame('Internal ops note', $campaign->notes);
        $this->assertSame('2026-09-10 08:00:00', $campaign->starts_at?->toDateTimeString());
        $this->assertSame('2026-10-10 18:00:00', $campaign->ends_at?->toDateTimeString());

        $activity = RecruitmentActivity::query()
            ->where('campaign_id', $campaign->id)
            ->where('type', RecruitmentActivityType::CampaignUpdated)
            ->latest('id')
            ->first();
        $this->assertNotNull($activity);
        $this->assertStringContainsString('Spring Window', (string) $activity->body);
        $this->assertStringNotContainsString('Internal ops note', (string) $activity->body);

        $this->actingAs($admin)
            ->post(route('admin.campaigns.status', $campaign), ['status' => CampaignStatus::Active->value])
            ->assertRedirect();
        $this->actingAs($admin)
            ->post(route('admin.campaigns.status', $campaign), ['status' => CampaignStatus::Paused->value])
            ->assertRedirect();
        $this->assertSame(CampaignStatus::Paused, $campaign->fresh()->status);
        $this->actingAs($admin)
            ->post(route('admin.campaigns.status', $campaign), ['status' => CampaignStatus::Closed->value])
            ->assertRedirect();
        $this->assertSame(CampaignStatus::Closed, $campaign->fresh()->status);
    }

    public function test_unusable_campaign_and_disabled_link_do_not_capture_attribution(): void
    {
        Carbon::setTestNow('2026-09-28 12:00:00');
        $admin = $this->makeAdmin();
        [$expired] = $this->createActiveCampaignWithLink($admin, 'ENDED001', 'Ended Campaign');
        $this->actingAs($admin)
            ->put(route('admin.campaigns.update', $expired), [
                'name' => 'Ended Campaign',
                'ends_at' => '2026-09-01 00:00:00',
            ])
            ->assertRedirect();
        $this->assertFalse($expired->fresh()->isUsable());

        [$future] = $this->createActiveCampaignWithLink($admin, 'FUTURE01', 'Future Campaign');
        $this->actingAs($admin)
            ->put(route('admin.campaigns.update', $future), [
                'name' => 'Future Campaign',
                'starts_at' => '2026-10-15 00:00:00',
            ])
            ->assertRedirect();
        $this->assertFalse($future->fresh()->isUsable());

        [$paused] = $this->createActiveCampaignWithLink($admin, 'PAUSE001', 'Paused Campaign');
        $this->actingAs($admin)
            ->post(route('admin.campaigns.status', $paused), ['status' => CampaignStatus::Paused->value])
            ->assertRedirect();

        [, $activeLink] = $this->createActiveCampaignWithLink($admin, 'LIVE0001', 'Live Campaign');

        $this->forgetAuthentication();
        $this->get('/jobs?ref=ENDED001')->assertOk()->assertCookieMissing(ReferralLink::COOKIE_NAME);
        $this->get('/jobs?ref=FUTURE01')->assertOk()->assertCookieMissing(ReferralLink::COOKIE_NAME);
        $this->get('/jobs?ref=PAUSE001')->assertOk()->assertCookieMissing(ReferralLink::COOKIE_NAME);

        $this->actingAs($admin)
            ->post(route('admin.campaigns.links.disable', $activeLink))
            ->assertRedirect();
        $this->assertSame(\App\Enums\ReferralLinkStatus::Disabled, $activeLink->fresh()->status);
        $this->assertTrue(
            RecruitmentActivity::query()
                ->where('referral_link_id', $activeLink->id)
                ->where('type', RecruitmentActivityType::ReferralLinkDisabled)
                ->exists()
        );

        $this->forgetAuthentication();
        $this->get('/jobs?ref=LIVE0001')->assertOk()->assertCookieMissing(ReferralLink::COOKIE_NAME);
    }

    public function test_tab_scoped_queries_and_server_side_campaign_filtering(): void
    {
        $admin = $this->makeAdmin();
        [$first, $firstLink] = $this->createActiveCampaignWithLink($admin, 'ALPHA001', 'Alpha Campaign');
        [$second, $secondLink] = $this->createActiveCampaignWithLink($admin, 'BETA0001', 'Beta Campaign');
        $job = Job::factory()->create(['title' => 'Shared Filter Role']);

        $this->forgetAuthentication();
        $this->from(route('jobs.show', $job))
            ->withUnencryptedCookie(ReferralLink::COOKIE_NAME, 'ALPHA001')
            ->post(route('jobs.share', $job), ['channel' => ShareChannel::Copy->value])
            ->assertRedirect();
        $this->from(route('jobs.show', $job))
            ->withUnencryptedCookie(ReferralLink::COOKIE_NAME, 'BETA0001')
            ->post(route('jobs.share', $job), ['channel' => ShareChannel::Whatsapp->value])
            ->assertRedirect();

        $this->withUnencryptedCookie(ReferralLink::COOKIE_NAME, 'ALPHA001')
            ->post(route('register.store'), $this->registrationPayload([
                'email' => 'alpha.ref@example.com',
                'username' => 'alpharef',
            ]))
            ->assertRedirect();
        $this->forgetAuthentication();
        $this->withUnencryptedCookie(ReferralLink::COOKIE_NAME, 'BETA0001')
            ->post(route('register.store'), $this->registrationPayload([
                'email' => 'beta.ref@example.com',
                'username' => 'betaref',
            ]))
            ->assertRedirect();

        $this->actingAs($admin)
            ->get(route('admin.campaigns.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('campaigns.data', 2)
                ->has('links.data', 0)
                ->has('shares.data', 0)
                ->has('referrals.data', 0)
                ->where('job_options', [])
                ->where('campaign_options', []));

        $this->actingAs($admin)
            ->get(route('admin.campaigns.index', ['tab' => 'links', 'campaign' => $first->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('campaigns.data', 0)
                ->has('links.data', 1)
                ->where('links.data.0.code', 'ALPHA001')
                ->has('job_options')
                ->has('campaign_options')
                ->where('selected.id', $first->id));

        $this->actingAs($admin)
            ->get(route('admin.campaigns.index', ['tab' => 'shares', 'campaign' => $first->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('shares.data', 1)
                ->where('shares.data.0.code', 'ALPHA001')
                ->has('referrals.data', 0));

        $this->actingAs($admin)
            ->get(route('admin.campaigns.index', ['tab' => 'conversions', 'campaign' => $second->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('referrals.data', 1)
                ->where('referrals.data.0.code', 'BETA0001')
                ->missing('referrals.data.0.application.resume_path')
                ->missing('referrals.data.0.internal_notes')
                ->missing('referrals.data.0.match_percent')
                ->missing('referrals.data.0.scorecard')
                ->missing('referrals.data.0.storage_path'));

        $this->assertNotNull($firstLink->id);
        $this->assertNotNull($secondLink->id);
        $this->assertSame(2, ShareEvent::query()->count());
        $this->assertSame(0, EmployerAttribution::query()->count());
        $this->assertSame(0, CommissionEntry::query()->count());
        $this->assertSame(0, CommissionPayment::query()->count());
        $this->assertSame(0, Invoice::query()->count());
        $this->assertSame(0, Payment::query()->count());
    }

    public function test_first_touch_share_urls_and_application_attribution_remain_correct(): void
    {
        $admin = $this->makeAdmin();
        [$campaign, $link] = $this->createActiveCampaignWithLink($admin, 'SHARE001', 'Share Campaign');
        $this->createActiveCampaignWithLink($admin, 'SHARE002', 'Second Share Campaign');
        $job = Job::factory()->create(['title' => 'Public Share Role']);

        $this->forgetAuthentication();
        $this->get(route('jobs.show', $job))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('job.referral_code', null));

        $this->get(route('jobs.show', ['job' => $job, 'ref' => 'SHARE001']))
            ->assertOk()
            ->assertPlainCookie(ReferralLink::COOKIE_NAME, 'SHARE001')
            ->assertInertia(fn (Assert $page) => $page->where('job.referral_code', 'SHARE001'));

        $this->withUnencryptedCookie(ReferralLink::COOKIE_NAME, 'SHARE001')
            ->get(route('jobs.show', ['job' => $job, 'ref' => 'SHARE002']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('job.referral_code', 'SHARE001'));

        $this->from(route('jobs.show', $job))
            ->post(route('jobs.share', $job), ['channel' => 'linkedin'])
            ->assertSessionHasErrors('channel');

        $this->from(route('jobs.show', $job))
            ->post(route('jobs.share', $job), ['channel' => ShareChannel::Copy->value])
            ->assertRedirect();
        $this->assertFalse(
            RecruitmentActivity::query()
                ->where('type', RecruitmentActivityType::ShareRecorded)
                ->exists()
        );

        $candidate = $this->makeCandidate(['email' => 'share.stage20@example.com']);
        $this->actingAs($candidate)
            ->withUnencryptedCookie(ReferralLink::COOKIE_NAME, 'SHARE001')
            ->from(route('jobs.show', $job))
            ->post(route('jobs.share', $job), ['channel' => ShareChannel::Native->value])
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
            ->withUnencryptedCookie(ReferralLink::COOKIE_NAME, 'SHARE001')
            ->post(route('jobs.apply.store', $job))
            ->assertRedirect();
        $application = $candidate->applications()->where('job_id', $job->id)->first();
        $this->assertNotNull($application);
        $referral = Referral::query()->where('application_id', $application->id)->first();
        $this->assertNotNull($referral);
        $this->assertSame($link->id, $referral->referral_link_id);
        $this->assertTrue($referral->is_primary);
        $this->assertSame(RegistrationSource::Website, $candidate->fresh()->registration_source);

        $self = $this->makeCandidate(['email' => 'self.stage20@example.com']);
        $this->actingAs($admin)
            ->post(route('admin.campaigns.links.store'), [
                'campaign_id' => $campaign->id,
                'path' => '/jobs',
                'code' => 'SELF2001',
                'referrer_user_id' => $self->id,
            ])
            ->assertRedirect();
        if (! $this->candidateHasResume($self)) {
            $this->giveCandidateResume($self);
        }
        $selfJob = Job::factory()->create();
        $this->actingAs($self)
            ->withUnencryptedCookie(ReferralLink::COOKIE_NAME, 'SELF2001')
            ->post(route('jobs.apply.store', $selfJob))
            ->assertRedirect();
        $selfApplication = $self->applications()->where('job_id', $selfJob->id)->first();
        $this->assertNotNull($selfApplication);
        $this->assertNull(Referral::query()->where('application_id', $selfApplication->id)->first());
        $this->assertSame(0, EmployerAttribution::query()->count());
    }

    public function test_stage_19_analytics_campaign_section_is_unchanged(): void
    {
        Carbon::setTestNow('2026-09-28 12:00:00');
        $admin = $this->makeAdmin();
        $this->createActiveCampaignWithLink($admin, 'ANLY0001', 'Analytics Campaign');

        $this->actingAs($admin)
            ->get(route('admin.analytics.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Analytics/Index')
                ->has('campaigns.campaigns')
                ->has('campaigns.active')
                ->has('campaigns.share_events')
                ->has('campaigns.referrals')
                ->has('campaigns.registrations')
                ->has('campaigns.applications')
                ->where('campaigns.active.kind', 'snapshot')
                ->where('permissions.campaigns', true));

        $recruiter = $this->makeStaff(StaffRoleSlug::Recruiter->value);
        $this->actingAs($recruiter)
            ->get(route('admin.analytics.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->missing('campaigns')
                ->where('permissions.campaigns', false));

        $this->actingAs($this->makeCandidate())->get(route('admin.analytics.index'))->assertForbidden();
        $this->actingAs($this->makeEmployer())->get(route('admin.analytics.index'))->assertForbidden();
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
