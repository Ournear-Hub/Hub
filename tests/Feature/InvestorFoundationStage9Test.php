<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\EmployerEnquiryStatus;
use App\Enums\FreelancerStatus;
use App\Enums\InvestmentOpportunityStatus;
use App\Enums\InvestorInterestStatus;
use App\Enums\InvestorStatus;
use App\Enums\PermissionName;
use App\Enums\RecruitmentActivityType;
use App\Enums\SecurityActivityType;
use App\Enums\StaffRoleSlug;
use App\Enums\UserRole;
use App\Models\CommissionPayment;
use App\Models\CrmOpportunity;
use App\Models\Freelancer;
use App\Models\InvestmentOpportunity;
use App\Models\Investor;
use App\Models\InvestorInterest;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\RecruitmentActivity;
use App\Models\SecurityActivity;
use App\Models\Task;
use App\Models\User;
use App\Support\AccessCatalog;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class InvestorFoundationStage9Test extends TestCase
{
    public function test_schema_adds_investor_tables_without_forbidden_duplicates(): void
    {
        $this->assertTrue(Schema::hasTable('investors'));
        $this->assertTrue(Schema::hasTable('investment_opportunities'));
        $this->assertTrue(Schema::hasTable('investor_interests'));
        $this->assertTrue(Schema::hasColumn('recruitment_activities', 'investor_id'));
        $this->assertTrue(Schema::hasColumn('recruitment_activities', 'investment_opportunity_id'));
        $this->assertTrue(Schema::hasColumn('recruitment_activities', 'investor_interest_id'));
        $this->assertTrue(Schema::hasColumn('tasks', 'investor_id'));
        $this->assertTrue(Schema::hasColumn('tasks', 'investment_opportunity_id'));
        $this->assertTrue(Schema::hasColumn('tasks', 'investor_interest_id'));
        $this->assertFalse(Schema::hasTable('investor_activities'));
        $this->assertFalse(Schema::hasTable('investment_payments'));
        $this->assertFalse(Schema::hasTable('investment_commitments'));
        $this->assertTrue(Schema::hasTable('campaigns'));
        $this->assertTrue(Schema::hasTable('referral_links'));
        $this->assertTrue(Schema::hasTable('share_events'));
        $this->assertCount(34, PermissionName::cases());
        $this->assertEqualsCanonicalizing(AccessCatalog::permissionNames(), array_map(
            fn (PermissionName $permission) => $permission->value,
            PermissionName::cases(),
        ));
        $this->assertContains(PermissionName::InvestorsView->value, AccessCatalog::permissionNames());
        $this->assertContains(PermissionName::InvestorsManage->value, AccessCatalog::permissionNames());
        $this->assertNotContains('opportunities.manage', AccessCatalog::permissionNames());
        $this->assertContains(UserRole::Investor, UserRole::cases());
        $this->assertFalse(in_array(PermissionName::InvestorsView->value, AccessCatalog::rolePermissions()[StaffRoleSlug::Recruiter->value], true));
        $this->assertFalse(in_array(PermissionName::InvestorsManage->value, AccessCatalog::rolePermissions()[StaffRoleSlug::Finance->value], true));
        $this->assertTrue(in_array(PermissionName::InvestorsView->value, AccessCatalog::rolePermissions()[StaffRoleSlug::Viewer->value], true));
        $this->assertSame([
            'New',
            'Contacted',
            'In Progress',
            'Completed',
            'Closed',
        ], array_map(fn (EmployerEnquiryStatus $status) => $status->value, EmployerEnquiryStatus::cases()));
        $this->assertSame([
            'new',
            'under_review',
            'contacted',
            'shortlisted',
            'rejected',
            'converted',
        ], array_map(fn (FreelancerStatus $status) => $status->value, FreelancerStatus::cases()));
        $this->assertStringNotContainsString('StaffRoleSlug::Recruiter', file_get_contents(app_path('Services/InvestorService.php')));
        $this->assertStringNotContainsString('StaffRoleSlug::Recruiter', file_get_contents(app_path('Services/InvestmentOpportunityService.php')));
        $this->assertStringNotContainsString('StaffRoleSlug::Recruiter', file_get_contents(app_path('Services/InvestorInterestService.php')));
        $this->assertStringNotContainsString('employers.manage', file_get_contents(app_path('Policies/InvestorPolicy.php')));
        $this->assertStringNotContainsString('finance.manage', file_get_contents(app_path('Policies/InvestorPolicy.php')));
        $this->assertStringNotContainsString('users.manage', file_get_contents(app_path('Policies/InvestorPolicy.php')));
        $this->assertStringNotContainsString('freelancers.manage', file_get_contents(app_path('Policies/InvestorPolicy.php')));
    }

    public function test_admin_can_create_investor_identity_without_public_signup_or_portal(): void
    {
        Notification::fake();
        Mail::fake();
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->post(route('admin.investors.store'), [
                'display_name' => 'Northridge Capital',
                'contact_person' => 'Anita Investor',
                'email' => 'anita.investor@example.com',
                'mobile' => '9000000091',
            ])
            ->assertRedirect();

        $investor = Investor::query()->where('email', 'anita.investor@example.com')->first();
        $this->assertNotNull($investor);
        $this->assertSame(InvestorStatus::PendingVerification, $investor->status);
        $this->assertNotNull($investor->user);
        $this->assertSame(UserRole::Investor, $investor->user->role);
        $this->assertTrue($investor->user->isInvestor());
        $this->assertFalse($investor->user->isAccountMember());
        $this->assertSame(AccountStatus::PendingVerification, $investor->user->account_status);
        $this->assertFalse($investor->user->isActive());
        $this->assertTrue(
            RecruitmentActivity::query()
                ->where('investor_id', $investor->id)
                ->where('type', RecruitmentActivityType::InvestorCreated)
                ->exists()
        );

        $this->post(route('register.store'), [
            'name' => 'Public Investor',
            'username' => 'publicinvestor',
            'email' => 'public.investor@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'role' => UserRole::Investor->value,
        ])->assertSessionHasErrors('role');

        $this->actingAs($investor->user)->get(route('admin.investors.index'))->assertForbidden();
        $this->actingAs($investor->user)->get(route('account.home'))->assertRedirect(route('login'));

        $this->actingAs($admin)->post(route('admin.investors.verify', $investor))->assertRedirect();
        $this->actingAs($investor->user->fresh())->get(route('account.home'))->assertForbidden();
        $this->actingAs($this->makeCandidate())->get(route('admin.investors.index'))->assertForbidden();
        $this->actingAs($this->makeEmployer())->post(route('admin.investors.store'), [
            'display_name' => 'Nope',
            'email' => 'nope.investor@example.com',
        ])->assertForbidden();
        $this->actingAs(User::factory()->partner()->create())->get(route('admin.investors.index'))->assertForbidden();
    }

    public function test_recruiter_and_finance_cannot_manage_investors_but_viewer_can_view(): void
    {
        $recruiter = $this->makeStaff(StaffRoleSlug::Recruiter->value);
        $finance = $this->makeStaff(StaffRoleSlug::Finance->value);
        $viewer = $this->makeStaff(StaffRoleSlug::Viewer->value);
        $admin = $this->makeAdmin();

        $this->actingAs($recruiter)
            ->post(route('admin.investors.store'), [
                'display_name' => 'Recruiter Cannot',
                'email' => 'recruiter.cannot@example.com',
            ])
            ->assertForbidden();

        $this->actingAs($finance)
            ->post(route('admin.investors.store'), [
                'display_name' => 'Finance Cannot',
                'email' => 'finance.cannot.investor@example.com',
            ])
            ->assertForbidden();

        $this->actingAs($recruiter)->get(route('admin.investors.index'))->assertForbidden();
        $this->actingAs($finance)->get(route('admin.investors.index'))->assertForbidden();

        $this->actingAs($admin)
            ->post(route('admin.investors.store'), [
                'display_name' => 'Verified Desk',
                'contact_person' => 'Verified Desk',
                'email' => 'verified.desk@example.com',
            ])
            ->assertRedirect();

        $investor = Investor::query()->where('email', 'verified.desk@example.com')->first();
        $this->assertNotNull($investor);

        $this->actingAs($viewer)
            ->get(route('admin.investors.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Investors/Index')
                ->where('can_manage', false)
                ->has('investors.data', 1));

        $this->actingAs($viewer)
            ->post(route('admin.investors.verify', $investor))
            ->assertForbidden();

        $this->actingAs($admin)
            ->post(route('admin.investors.verify', $investor))
            ->assertRedirect();

        $this->assertSame(InvestorStatus::Active, $investor->fresh()->status);
        $this->assertSame(AccountStatus::Active, $investor->user->fresh()->account_status);
        $this->assertTrue(
            SecurityActivity::query()
                ->where('action', SecurityActivityType::InvestorVerified)
                ->where('subject_id', $investor->id)
                ->exists()
        );

        $investor->user->forceFill([
            'password' => Hash::make('Password1!'),
            'email_verified_at' => now(),
        ])->save();
        $this->forgetAuthentication();
        $this->post(route('login.store'), [
            'login' => 'verified.desk@example.com',
            'password' => 'Password1!',
        ])->assertSessionHasErrors('login');

        $this->actingAs($admin)
            ->post(route('admin.investors.suspend', $investor))
            ->assertRedirect();

        $this->assertSame(InvestorStatus::Suspended, $investor->fresh()->status);
        $this->assertSame(AccountStatus::Suspended, $investor->user->fresh()->account_status);
        $this->assertTrue(
            SecurityActivity::query()
                ->where('action', SecurityActivityType::InvestorSuspended)
                ->where('subject_id', $investor->id)
                ->exists()
        );
    }

    public function test_opportunity_lifecycle_and_interest_stay_non_binding(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin)
            ->post(route('admin.investors.store'), [
                'display_name' => 'Harbor Family Office',
                'email' => 'harbor.fo@example.com',
            ])
            ->assertRedirect();

        $investor = Investor::query()->where('email', 'harbor.fo@example.com')->first();
        $this->assertNotNull($investor);
        $this->actingAs($admin)->post(route('admin.investors.verify', $investor))->assertRedirect();

        $this->actingAs($admin)
            ->post(route('admin.investors.opportunities.store'), [
                'title' => 'Coimbatore Logistics Park',
                'sector' => 'Logistics',
                'location' => 'Coimbatore',
                'indicated_size' => '25000000',
            ])
            ->assertRedirect();

        $opportunity = InvestmentOpportunity::query()->where('title', 'Coimbatore Logistics Park')->first();
        $this->assertNotNull($opportunity);
        $this->assertSame(InvestmentOpportunityStatus::Draft, $opportunity->status);
        $this->assertTrue(
            RecruitmentActivity::query()
                ->where('investment_opportunity_id', $opportunity->id)
                ->where('type', RecruitmentActivityType::InvestmentOpportunityCreated)
                ->exists()
        );

        $this->actingAs($admin)
            ->post(route('admin.investors.interests.store'), [
                'investor_id' => $investor->id,
                'investment_opportunity_id' => $opportunity->id,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('investment_opportunity_id');

        $this->actingAs($admin)
            ->post(route('admin.investors.opportunities.stage', $opportunity), [
                'status' => InvestmentOpportunityStatus::OpenForInterest->value,
            ])
            ->assertRedirect();

        $this->assertSame(InvestmentOpportunityStatus::OpenForInterest, $opportunity->fresh()->status);
        $this->assertTrue(
            SecurityActivity::query()
                ->where('action', SecurityActivityType::InvestmentOpportunityOpened)
                ->where('subject_id', $opportunity->id)
                ->exists()
        );

        $this->actingAs($admin)
            ->post(route('admin.investors.interests.store'), [
                'investor_id' => $investor->id,
                'investment_opportunity_id' => $opportunity->id,
                'indicated_amount' => '5000000',
                'notes' => 'Exploratory only',
            ])
            ->assertRedirect();

        $interest = InvestorInterest::query()
            ->where('investor_id', $investor->id)
            ->where('investment_opportunity_id', $opportunity->id)
            ->first();
        $this->assertNotNull($interest);
        $this->assertSame(InvestorInterestStatus::Interested, $interest->status);
        $this->assertSame('5000000.00', (string) $interest->indicated_amount);
        $this->assertSame(InvestmentOpportunityStatus::InterestReceived, $opportunity->fresh()->status);

        $this->actingAs($admin)
            ->put(route('admin.investors.interests.update', $interest), [
                'status' => InvestorInterestStatus::Committed->value,
                'indicated_amount' => '5000000',
            ])
            ->assertRedirect();

        $freshInterest = $interest->fresh();
        $this->assertSame(InvestorInterestStatus::Committed, $freshInterest->status);
        $this->assertTrue($freshInterest->isRecordedIntent());
        $this->assertNotNull($freshInterest->committed_at);
        $this->assertTrue(
            SecurityActivity::query()
                ->where('action', SecurityActivityType::InvestorInterestCommitted)
                ->where('subject_id', $interest->id)
                ->exists()
        );
        $this->assertSame(0, Invoice::query()->count());
        $this->assertSame(0, Payment::query()->count());
        $this->assertSame(0, CommissionPayment::query()->count());
        $this->assertFalse(Schema::hasTable('investment_payments'));

        $this->actingAs($admin)
            ->post(route('admin.investors.opportunities.stage', $opportunity), [
                'status' => InvestmentOpportunityStatus::DueDiligence->value,
            ])
            ->assertRedirect();
        $this->assertSame(InvestmentOpportunityStatus::DueDiligence, $opportunity->fresh()->status);

        $this->actingAs($admin)
            ->post(route('admin.investors.interests.store'), [
                'investor_id' => $investor->id,
                'investment_opportunity_id' => $opportunity->id,
            ])
            ->assertSessionHasErrors('investor_id');
    }

    public function test_investor_tasks_and_my_work_use_investor_permissions(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin)
            ->post(route('admin.investors.store'), [
                'display_name' => 'Work Desk Investor',
                'email' => 'work.desk.investor@example.com',
            ])
            ->assertRedirect();
        $investor = Investor::query()->where('email', 'work.desk.investor@example.com')->first();
        $this->assertNotNull($investor);

        $this->actingAs($admin)
            ->post(route('admin.tasks.store'), [
                'title' => 'Call Harbor principal',
                'investor_id' => $investor->id,
                'assigned_to' => $admin->id,
            ])
            ->assertRedirect();

        $task = Task::query()->where('title', 'Call Harbor principal')->first();
        $this->assertNotNull($task);
        $this->assertTrue($task->isInvestorLinked());
        $this->assertFalse($task->isEmployerLinked());

        $this->actingAs($this->makeStaff(StaffRoleSlug::Recruiter->value))
            ->from(route('admin.tasks.index'))
            ->post(route('admin.tasks.store'), [
                'title' => 'Recruiter investor task',
                'investor_id' => $investor->id,
            ])
            ->assertRedirect(route('admin.tasks.index'))
            ->assertSessionHasErrors('title');

        $this->actingAs($admin)
            ->get(route('admin.work.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Work/Index')
                ->has('tasks', 1)
                ->where('tasks.0.title', 'Call Harbor principal')
                ->has('pending_interests'));

        $this->actingAs($admin)
            ->get(route('admin.investors.index', ['investor' => $investor->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Investors/Index')
                ->where('selected.id', $investor->id)
                ->has('assigned_work', 1)
                ->has('activity'));
    }

    public function test_stage_nine_does_not_change_crm_ats_or_freelancer_architecture(): void
    {
        $this->assertSame(CrmOpportunity::class, \App\Models\CrmOpportunity::class);
        $this->assertNotSame(CrmOpportunity::class, InvestmentOpportunity::class);
        $this->assertTrue(Schema::hasTable('crm_opportunities'));
        $this->assertTrue(Schema::hasTable('investment_opportunities'));
        $this->assertTrue(Schema::hasTable('channel_partners'));
        $this->assertTrue(Schema::hasTable('employer_enquiries'));
        $this->assertTrue(Schema::hasTable('freelancers'));
        $this->assertSame(0, Freelancer::query()->count());
        $this->assertFalse(Schema::hasColumn('crm_opportunities', 'investor_id'));
        $this->assertFalse(Schema::hasColumn('employer_profiles', 'investor_id'));
        $this->assertFalse(Schema::hasColumn('channel_partners', 'investor_id'));
    }
}
