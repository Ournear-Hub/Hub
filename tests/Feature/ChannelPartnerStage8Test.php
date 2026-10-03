<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\ChannelPartnerStatus;
use App\Enums\CommissionEntryStatus;
use App\Enums\CrmLeadSource;
use App\Enums\EmployerAttributionStatus;
use App\Enums\EmployerEnquiryStatus;
use App\Enums\FreelancerStatus;
use App\Enums\PartnerAgreementStatus;
use App\Enums\PaymentMethod;
use App\Enums\PermissionName;
use App\Enums\RecruitmentActivityType;
use App\Enums\SecurityActivityType;
use App\Enums\StaffRoleSlug;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\ChannelPartner;
use App\Models\ChannelPartnerAgreement;
use App\Models\CommissionEntry;
use App\Models\CommissionPayment;
use App\Models\CrmLead;
use App\Models\EmployerAttribution;
use App\Models\EmployerProfile;
use App\Models\EmployerSubmission;
use App\Models\Freelancer;
use App\Models\Invoice;
use App\Models\Job;
use App\Models\Placement;
use App\Models\RecruitmentActivity;
use App\Models\SecurityActivity;
use App\Models\Task;
use App\Models\User;
use App\Support\AccessCatalog;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ChannelPartnerStage8Test extends TestCase
{
    public function test_schema_adds_partner_tables_without_forbidden_duplicates(): void
    {
        $this->assertTrue(Schema::hasTable('channel_partners'));
        $this->assertTrue(Schema::hasTable('channel_partner_agreements'));
        $this->assertTrue(Schema::hasTable('employer_attributions'));
        $this->assertTrue(Schema::hasTable('commission_entries'));
        $this->assertTrue(Schema::hasTable('commission_payments'));
        $this->assertTrue(Schema::hasColumn('crm_leads', 'channel_partner_id'));
        $this->assertTrue(Schema::hasColumn('recruitment_activities', 'channel_partner_id'));
        $this->assertTrue(Schema::hasColumn('tasks', 'channel_partner_id'));
        $this->assertFalse(Schema::hasTable('crm_activities'));
        $this->assertFalse(Schema::hasTable('commission_rules'));
        $this->assertTrue(Schema::hasTable('referral_links'));
        $this->assertTrue(Schema::hasTable('share_events'));
        $this->assertTrue(Schema::hasTable('campaigns'));
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
        $this->assertCount(34, PermissionName::cases());
        $this->assertEqualsCanonicalizing(AccessCatalog::permissionNames(), array_map(
            fn (PermissionName $permission) => $permission->value,
            PermissionName::cases(),
        ));
        $this->assertContains(UserRole::Partner, UserRole::cases());
        $this->assertStringNotContainsString('StaffRoleSlug::Recruiter', file_get_contents(app_path('Services/ChannelPartnerService.php')));
        $this->assertStringNotContainsString('StaffRoleSlug::Recruiter', file_get_contents(app_path('Services/AttributionService.php')));
        $this->assertStringNotContainsString('StaffRoleSlug::Recruiter', file_get_contents(app_path('Services/CommissionService.php')));
    }

    public function test_admin_can_create_partner_identity_without_public_signup_or_portal(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->post(route('admin.partners.store'), [
                'display_name' => 'West Coast Referrals',
                'contact_person' => 'Ravi Partner',
                'email' => 'ravi.partner@example.com',
                'mobile' => '9000000001',
            ])
            ->assertRedirect();

        $partner = ChannelPartner::query()->where('email', 'ravi.partner@example.com')->first();
        $this->assertNotNull($partner);
        $this->assertSame(ChannelPartnerStatus::Pending, $partner->status);
        $this->assertNotNull($partner->user);
        $this->assertSame(UserRole::Partner, $partner->user->role);
        $this->assertTrue($partner->user->isPartner());
        $this->assertFalse($partner->user->isAccountMember());
        $this->assertTrue(
            RecruitmentActivity::query()
                ->where('channel_partner_id', $partner->id)
                ->where('type', RecruitmentActivityType::PartnerCreated)
                ->exists()
        );

        $this->post(route('register.store'), [
            'name' => 'Public Partner',
            'username' => 'publicpartner',
            'email' => 'public.partner@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'role' => UserRole::Partner->value,
        ])->assertSessionHasErrors('role');

        $this->actingAs($partner->user)->get(route('admin.partners.index'))->assertForbidden();
        $this->actingAs($partner->user)->get(route('account.home'))->assertForbidden();
        $this->actingAs($this->makeCandidate())->get(route('admin.partners.index'))->assertForbidden();
        $this->actingAs($this->makeEmployer())->post(route('admin.partners.store'), [
            'display_name' => 'Nope',
            'email' => 'nope@example.com',
        ])->assertForbidden();
    }

    public function test_agreement_activation_and_partner_approval_use_employer_permissions(): void
    {
        $recruiter = $this->makeStaff(StaffRoleSlug::Recruiter->value);
        $finance = $this->makeStaff(StaffRoleSlug::Finance->value);

        $this->actingAs($finance)
            ->post(route('admin.partners.store'), [
                'display_name' => 'Finance Cannot',
                'email' => 'finance.cannot@example.com',
            ])
            ->assertForbidden();

        $this->actingAs($recruiter)
            ->post(route('admin.partners.store'), [
                'display_name' => 'Coastal Partners',
                'contact_person' => 'Coastal Desk',
                'email' => 'coastal@example.com',
            ])
            ->assertRedirect();

        $partner = ChannelPartner::query()->where('email', 'coastal@example.com')->first();
        $this->assertNotNull($partner);

        $this->actingAs($recruiter)
            ->post(route('admin.partners.approve', $partner))
            ->assertRedirect()
            ->assertSessionHasErrors('status');

        $this->actingAs($recruiter)
            ->post(route('admin.partners.agreements.store', $partner), [
                'commission_percent' => '30',
                'duration_months' => '3',
            ])
            ->assertRedirect();

        $agreement = ChannelPartnerAgreement::query()->where('channel_partner_id', $partner->id)->first();
        $this->assertNotNull($agreement);
        $this->assertSame(PartnerAgreementStatus::Draft, $agreement->status);

        $this->actingAs($recruiter)
            ->post(route('admin.agreements.activate', $agreement))
            ->assertRedirect();

        $this->assertSame(PartnerAgreementStatus::Active, $agreement->fresh()->status);
        $this->assertSame('30.00', (string) $agreement->fresh()->commission_percent);

        $this->actingAs($recruiter)
            ->post(route('admin.partners.approve', $partner))
            ->assertRedirect();

        $this->assertSame(ChannelPartnerStatus::Active, $partner->fresh()->status);
        $this->assertTrue(
            SecurityActivity::query()
                ->where('action', SecurityActivityType::PartnerApproved)
                ->where('subject_id', $partner->id)
                ->exists()
        );

        $this->actingAs($this->makeStaff(StaffRoleSlug::Viewer->value))
            ->get(route('admin.partners.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Partners/Index')
                ->where('can_manage', false)
                ->has('partners.data', 1));
    }

    public function test_partner_lead_conversion_confirms_attribution_and_blocks_conflicts(): void
    {
        Mail::fake();
        Notification::fake();
        $admin = $this->makeAdmin();
        $partner = $this->createApprovedPartner($admin, 'Alpha Referrals', 'alpha.ref@example.com');
        $other = $this->createApprovedPartner($admin, 'Beta Referrals', 'beta.ref@example.com');

        $this->actingAs($admin)
            ->post(route('admin.crm.leads.store'), [
                'company_name' => 'Attributed Builders',
                'email' => 'ops@attributed-builders.test',
                'channel_partner_id' => $partner->id,
            ])
            ->assertRedirect();

        $lead = CrmLead::query()->where('company_name', 'Attributed Builders')->first();
        $this->assertNotNull($lead);
        $this->assertSame($partner->id, $lead->channel_partner_id);
        $this->assertSame(CrmLeadSource::ChannelPartner, $lead->source);
        $opportunity = $lead->opportunities()->first();
        $this->assertNotNull($opportunity);

        $this->actingAs($admin)
            ->post(route('admin.crm.opportunities.convert', $opportunity), [
                'create_employer' => true,
                'email' => 'ops@attributed-builders.test',
            ])
            ->assertRedirect();

        $employer = EmployerProfile::query()->where('company_name', 'Attributed Builders')->first();
        $this->assertNotNull($employer);
        $attribution = EmployerAttribution::query()
            ->where('employer_profile_id', $employer->id)
            ->where('channel_partner_id', $partner->id)
            ->first();
        $this->assertNotNull($attribution);
        $this->assertSame(EmployerAttributionStatus::Confirmed, $attribution->status);
        $this->assertSame($lead->id, $attribution->crm_lead_id);
        $this->assertSame(1, EmployerProfile::query()->where('company_name', 'Attributed Builders')->count());

        $this->actingAs($admin)
            ->from(route('admin.partners.index'))
            ->post(route('admin.attributions.store'), [
                'channel_partner_id' => $other->id,
                'employer_profile_id' => $employer->id,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors(['employer_profile_id', 'channel_partner_id']);

        $this->assertSame(1, EmployerAttribution::query()->where('employer_profile_id', $employer->id)->count());
        $this->assertSame(1, EmployerProfile::query()->count());
    }

    public function test_commission_is_created_only_from_recorded_payments_inside_agreement_window(): void
    {
        Mail::fake();
        Notification::fake();
        $admin = $this->makeAdmin();
        $recruiter = $this->makeStaff(StaffRoleSlug::Recruiter->value);
        $finance = $this->makeStaff(StaffRoleSlug::Finance->value);
        $partner = $this->createApprovedPartner($admin, 'Receipt Partners', 'receipt.partners@example.com');

        [, , , , , $employee] = $this->joinedEmployee();
        $employer = $employee->employer;
        $this->assertNotNull($employer);

        $this->actingAs($admin)
            ->post(route('admin.attributions.store'), [
                'channel_partner_id' => $partner->id,
                'employer_profile_id' => $employer->id,
            ])
            ->assertRedirect();
        $attribution = EmployerAttribution::query()->where('employer_profile_id', $employer->id)->first();
        $this->actingAs($admin)->post(route('admin.attributions.confirm', $attribution))->assertRedirect();

        $this->actingAs($admin)->post(route('admin.employees.invoices.store', $employee), [
            'amount' => '100000',
            'gst_rate' => '0',
            'billed_at' => now()->toDateString(),
            'due_at' => now()->addDays(30)->toDateString(),
        ]);
        $invoice = Invoice::query()->where('employee_id', $employee->id)->first();
        $this->assertNotNull($invoice);
        $this->actingAs($admin)->post(route('admin.invoices.issue', $invoice));
        $this->actingAs($admin)->post(route('admin.invoices.payments.store', $invoice), [
            'amount' => '40000',
            'paid_at' => now()->toDateString(),
            'method' => PaymentMethod::BankTransfer->value,
            'reference' => 'NEFT-S8',
        ])->assertRedirect();

        $entry = CommissionEntry::query()->where('invoice_id', $invoice->id)->first();
        $this->assertNotNull($entry);
        $this->assertSame(CommissionEntryStatus::Pending, $entry->status);
        $this->assertSame('40000.00', (string) $entry->basis_amount);
        $this->assertSame('30.00', (string) $entry->rate);
        $this->assertSame('12000.00', (string) $entry->commission_amount);
        $this->assertSame($partner->id, $entry->channel_partner_id);

        $this->actingAs($recruiter)
            ->post(route('admin.commission.entries.approve', $entry))
            ->assertForbidden();

        $this->actingAs($finance)
            ->get(route('admin.commission.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Commission/Index')
                ->has('entries.data', 1)
                ->where('can_manage', true));

        $this->actingAs($finance)
            ->post(route('admin.commission.entries.approve', $entry))
            ->assertRedirect();
        $this->assertSame(CommissionEntryStatus::Approved, $entry->fresh()->status);

        $this->actingAs($finance)
            ->post(route('admin.commission.payouts.store'), [
                'channel_partner_id' => $partner->id,
                'entry_ids' => [$entry->id],
                'paid_at' => now()->toDateString(),
                'method' => PaymentMethod::BankTransfer->value,
                'reference' => 'PAYOUT-S8',
            ])
            ->assertRedirect();

        $this->assertSame(CommissionEntryStatus::Paid, $entry->fresh()->status);
        $this->assertSame(1, CommissionPayment::query()->count());
        $this->assertSame('12000.00', (string) CommissionPayment::query()->first()?->amount);

        $unattributed = $this->joinedEmployee('Unattributed Co', 'unattributed@example.com', 'Bala Candidate');
        [, , , , , $plainEmployee] = $unattributed;
        $this->actingAs($admin)->post(route('admin.employees.invoices.store', $plainEmployee), [
            'amount' => '50000',
            'gst_rate' => '0',
            'billed_at' => now()->toDateString(),
            'due_at' => now()->addDays(30)->toDateString(),
        ]);
        $plainInvoice = Invoice::query()->where('employee_id', $plainEmployee->id)->first();
        $this->actingAs($admin)->post(route('admin.invoices.issue', $plainInvoice));
        $this->actingAs($admin)->post(route('admin.invoices.payments.store', $plainInvoice), [
            'amount' => '50000',
            'paid_at' => now()->toDateString(),
            'method' => PaymentMethod::BankTransfer->value,
        ]);
        $this->assertSame(1, CommissionEntry::query()->count());
    }

    public function test_partner_tasks_my_work_and_crm_page_stay_permission_gated(): void
    {
        $admin = $this->makeAdmin();
        $partner = $this->createApprovedPartner($admin, 'Desk Partner', 'desk.partner@example.com');

        $this->actingAs($admin)
            ->post(route('admin.tasks.store'), [
                'title' => 'Call partner',
                'channel_partner_id' => $partner->id,
                'assigned_to' => $admin->id,
            ])
            ->assertRedirect();

        $this->assertTrue(Task::query()->where('title', 'Call partner')->where('channel_partner_id', $partner->id)->exists());

        $this->actingAs($admin)
            ->get(route('admin.work.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Work/Index')
                ->has('tasks', 1)
                ->where('tasks.0.channel_partner_id', $partner->id));

        $this->actingAs($admin)
            ->get(route('admin.crm.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Crm/Index')
                ->has('partners'));

        $this->actingAs($this->makeStaff(StaffRoleSlug::Finance->value))
            ->get(route('admin.partners.index'))
            ->assertForbidden();

        $this->assertSame(0, Freelancer::query()->count());
    }

    private function createApprovedPartner(User $actor, string $name, string $email, int $months = 3): ChannelPartner
    {
        $this->actingAs($actor)
            ->post(route('admin.partners.store'), [
                'display_name' => $name,
                'contact_person' => $name,
                'email' => $email,
            ])
            ->assertRedirect();

        $partner = ChannelPartner::query()->where('email', $email)->first();
        $this->assertNotNull($partner);

        $this->actingAs($actor)
            ->post(route('admin.partners.agreements.store', $partner), [
                'commission_percent' => '30',
                'duration_months' => $months,
            ])
            ->assertRedirect();

        $agreement = ChannelPartnerAgreement::query()->where('channel_partner_id', $partner->id)->first();
        $this->assertNotNull($agreement);
        $this->actingAs($actor)->post(route('admin.agreements.activate', $agreement))->assertRedirect();
        $this->actingAs($actor)->post(route('admin.partners.approve', $partner))->assertRedirect();

        return $partner->fresh(['agreements', 'user']) ?? $partner;
    }

    /**
     * @return array{0: EmployerProfile, 1: Job, 2: Application, 3: EmployerSubmission, 4: Placement, 5: \App\Models\Employee}
     */
    private function joinedEmployee(
        string $company = 'Asha Employer Co',
        string $employerEmail = 'asha.employer@example.com',
        string $candidateName = 'Asha Candidate',
    ): array {
        $admin = $this->makeAdmin();
        $employer = $this->makeEmployer(['email' => $employerEmail, 'name' => $company]);
        $profile = $employer->employerProfile;
        $this->assertNotNull($profile);
        $profile->forceFill(['company_name' => $company])->save();
        $job = Job::factory()->create(['employer_id' => $profile->id, 'title' => 'Site Engineer']);
        $candidate = $this->makeCandidate(['name' => $candidateName, 'email' => strtolower(str_replace(' ', '.', $candidateName)).'@example.com']);
        $application = Application::factory()->create([
            'job_id' => $job->id,
            'user_id' => $candidate->id,
            'full_name' => $candidateName,
            'status' => ApplicationStatus::Shortlisted,
        ]);

        $this->actingAs($admin)->post(route('admin.applications.submit', $application));
        $submission = EmployerSubmission::query()->where('application_id', $application->id)->first();
        $this->actingAs($admin)->post(route('admin.submissions.select', $submission));
        $this->actingAs($admin)->post(route('admin.submissions.place', $submission->fresh()));
        $placement = Placement::query()->where('employer_submission_id', $submission->id)->first();
        $this->actingAs($admin)->post(route('admin.placements.offer', $placement), [
            'offered_at' => now()->toDateString(),
        ]);
        $this->actingAs($admin)->post(route('admin.placements.accept', $placement));
        $this->actingAs($admin)->post(route('admin.placements.confirm-joining', $placement), [
            'expected_joining_date' => now()->toDateString(),
        ]);
        $this->actingAs($admin)->post(route('admin.placements.join', $placement), [
            'actual_joining_date' => now()->toDateString(),
        ]);

        $placement->refresh();
        $employee = $placement->employee;
        $this->assertNotNull($employee);

        return [$profile, $job, $application, $submission->fresh(), $placement, $employee];
    }
}
