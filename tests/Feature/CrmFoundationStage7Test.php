<?php

namespace Tests\Feature;

use App\Enums\CrmOpportunityStatus;
use App\Enums\EmployerEnquiryStatus;
use App\Enums\PermissionName;
use App\Enums\RecruitmentActivityType;
use App\Enums\RegistrationSource;
use App\Enums\SecurityActivityType;
use App\Enums\UserRole;
use App\Models\ContactEnquiry;
use App\Models\CrmLead;
use App\Models\CrmOpportunity;
use App\Models\EmployerEnquiry;
use App\Models\EmployerProfile;
use App\Models\Permission;
use App\Models\RecruitmentActivity;
use App\Models\Role;
use App\Models\SecurityActivity;
use App\Models\Task;
use App\Models\User;
use App\Support\AccessCatalog;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CrmFoundationStage7Test extends TestCase
{
    public function test_schema_adds_crm_tables_and_extends_activities_and_tasks(): void
    {
        $this->assertTrue(Schema::hasTable('crm_leads'));
        $this->assertTrue(Schema::hasTable('crm_opportunities'));
        $this->assertFalse(Schema::hasTable('crm_activities'));
        $this->assertTrue(Schema::hasColumn('recruitment_activities', 'crm_lead_id'));
        $this->assertTrue(Schema::hasColumn('recruitment_activities', 'crm_opportunity_id'));
        $this->assertTrue(Schema::hasColumn('tasks', 'employer_profile_id'));
        $this->assertTrue(Schema::hasColumn('tasks', 'employer_enquiry_id'));
        $this->assertTrue(Schema::hasColumn('tasks', 'crm_lead_id'));
        $this->assertTrue(Schema::hasColumn('tasks', 'crm_opportunity_id'));
        $this->assertSame([
            'New',
            'Contacted',
            'In Progress',
            'Completed',
            'Closed',
        ], array_map(fn (EmployerEnquiryStatus $status) => $status->value, EmployerEnquiryStatus::cases()));
        $this->assertCount(34, PermissionName::cases());
        $this->assertEqualsCanonicalizing(AccessCatalog::permissionNames(), array_map(
            fn (PermissionName $permission) => $permission->value,
            PermissionName::cases(),
        ));
        $this->assertStringNotContainsString('StaffRoleSlug::Recruiter', file_get_contents(app_path('Services/CrmLeadService.php')));
        $this->assertStringNotContainsString('StaffRoleSlug::Recruiter', file_get_contents(app_path('Services/CrmOpportunityService.php')));
        $this->assertStringNotContainsString('StaffRoleSlug::Recruiter', file_get_contents(app_path('Services/CompanyDuplicateService.php')));
    }

    public function test_creating_a_lead_creates_a_new_lead_opportunity_and_records_activity(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->post(route('admin.crm.leads.store'), [
                'company_name' => 'Northwind Projects',
                'contact_person' => 'Pat Contact',
                'email' => 'pat@northwind.test',
                'assigned_to' => $admin->id,
            ])
            ->assertRedirect();

        $lead = CrmLead::query()->where('company_name', 'Northwind Projects')->first();
        $this->assertNotNull($lead);
        $this->assertSame($admin->id, $lead->assigned_to);
        $this->assertSame(1, $lead->opportunities()->count());
        $this->assertSame(CrmOpportunityStatus::NewLead, $lead->opportunities()->first()?->status);
        $this->assertTrue(
            RecruitmentActivity::query()
                ->where('crm_lead_id', $lead->id)
                ->where('type', RecruitmentActivityType::CrmLeadCreated)
                ->exists()
        );
        $this->assertTrue(
            RecruitmentActivity::query()
                ->where('crm_opportunity_id', $lead->opportunities()->first()?->id)
                ->where('type', RecruitmentActivityType::CrmOpportunityCreated)
                ->exists()
        );
    }

    public function test_staff_can_create_a_lead_from_an_employer_enquiry_without_changing_enquiry_status(): void
    {
        $admin = $this->makeAdmin();
        $enquiry = EmployerEnquiry::factory()->create([
            'company_name' => 'Enquiry Builders',
            'email' => 'ops@enquiry-builders.test',
            'status' => EmployerEnquiryStatus::New,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.employer-enquiries.lead', $enquiry))
            ->assertRedirect();

        $lead = CrmLead::query()->where('employer_enquiry_id', $enquiry->id)->first();
        $this->assertNotNull($lead);
        $this->assertSame('Enquiry Builders', $lead->company_name);
        $this->assertSame(EmployerEnquiryStatus::New, $enquiry->fresh()->status);
        $this->assertSame(1, $lead->opportunities()->count());
    }

    public function test_duplicate_company_warns_and_confirm_creates_without_merging(): void
    {
        $admin = $this->makeAdmin();
        $employerUser = $this->makeEmployer(['email' => 'acme@client.test']);
        $employer = $employerUser->employerProfile;
        $this->assertNotNull($employer);
        $employer->forceFill(['company_name' => 'Acme Limited'])->save();

        $this->actingAs($admin)
            ->from(route('admin.crm.index'))
            ->post(route('admin.crm.leads.store'), [
                'company_name' => 'Acme Ltd',
                'email' => 'sales@acme-new.test',
            ])
            ->assertRedirect(route('admin.crm.index'))
            ->assertSessionHasErrors('duplicates')
            ->assertSessionHas('duplicate_matches');

        $this->assertSame(0, CrmLead::query()->count());
        $this->assertSame(1, EmployerProfile::query()->count());

        $this->actingAs($admin)
            ->post(route('admin.crm.leads.store'), [
                'company_name' => 'Acme Ltd',
                'email' => 'sales@acme-new.test',
                'confirm_duplicates' => true,
            ])
            ->assertRedirect();

        $this->assertSame(1, CrmLead::query()->count());
        $this->assertSame(1, EmployerProfile::query()->count());
        $this->assertNull(CrmLead::query()->first()?->employer_profile_id);
    }

    public function test_conversion_can_link_an_existing_employer_or_create_one(): void
    {
        Mail::fake();
        Notification::fake();
        $admin = $this->makeAdmin();
        $existingEmployer = $this->makeEmployer(['email' => 'existing-client@test.com'])->employerProfile;
        $this->assertNotNull($existingEmployer);

        $this->actingAs($admin)
            ->post(route('admin.crm.leads.store'), [
                'company_name' => 'Linkable Co',
                'email' => 'linkable@test.com',
                'confirm_duplicates' => true,
            ])
            ->assertRedirect();
        $linkLead = CrmLead::query()->where('company_name', 'Linkable Co')->first();
        $this->assertNotNull($linkLead);
        $linkOpportunity = $linkLead->opportunities()->first();
        $this->assertNotNull($linkOpportunity);

        $this->actingAs($admin)
            ->post(route('admin.crm.opportunities.convert', $linkOpportunity), [
                'employer_profile_id' => $existingEmployer->id,
            ])
            ->assertRedirect();

        $this->assertSame($existingEmployer->id, $linkLead->fresh()->employer_profile_id);
        $this->assertSame($existingEmployer->id, $linkOpportunity->fresh()->employer_profile_id);
        $this->assertSame(CrmOpportunityStatus::Converted, $linkOpportunity->fresh()->status);
        $this->assertTrue(
            SecurityActivity::query()
                ->where('action', SecurityActivityType::CrmConverted)
                ->where('subject_id', $existingEmployer->id)
                ->exists()
        );

        $this->actingAs($admin)
            ->post(route('admin.crm.leads.store'), [
                'company_name' => 'Brand New Client',
                'contact_person' => 'New Contact',
                'email' => 'new-client@test.com',
            ])
            ->assertRedirect();
        $createLead = CrmLead::query()->where('company_name', 'Brand New Client')->first();
        $this->assertNotNull($createLead);
        $createOpportunity = $createLead->opportunities()->first();

        $this->actingAs($admin)
            ->post(route('admin.crm.opportunities.convert', $createOpportunity), [
                'create_employer' => true,
                'email' => 'new-client@test.com',
            ])
            ->assertRedirect();

        $createdUser = User::query()->where('email', 'new-client@test.com')->first();
        $this->assertNotNull($createdUser);
        $this->assertSame(UserRole::Employer, $createdUser->role);
        $this->assertSame(RegistrationSource::Admin, $createdUser->registration_source);
        $this->assertTrue((bool) $createdUser->must_change_password);
        $this->assertNotNull($createdUser->employerProfile);
        $this->assertSame('Brand New Client', $createdUser->employerProfile->company_name);
        $this->assertSame($createdUser->employerProfile->id, $createLead->fresh()->employer_profile_id);
        $this->assertTrue(
            SecurityActivity::query()
                ->where('action', SecurityActivityType::CrmEmployerCreated)
                ->exists()
        );
    }

    public function test_create_employer_email_collision_blocks_and_converted_requires_employer(): void
    {
        $admin = $this->makeAdmin();
        $existing = $this->makeEmployer(['email' => 'taken-employer@test.com']);
        $this->assertNotNull($existing->employerProfile);

        $this->actingAs($admin)
            ->post(route('admin.crm.leads.store'), [
                'company_name' => 'Collision Co',
                'email' => 'taken-employer@test.com',
                'confirm_duplicates' => true,
            ])
            ->assertRedirect();
        $opportunity = CrmOpportunity::query()->whereHas('lead', fn ($lead) => $lead->where('company_name', 'Collision Co'))->first();
        $this->assertNotNull($opportunity);

        $this->actingAs($admin)
            ->from(route('admin.crm.index', ['lead' => $opportunity->crm_lead_id]))
            ->post(route('admin.crm.opportunities.convert', $opportunity), [
                'create_employer' => true,
                'email' => 'taken-employer@test.com',
                'confirm_duplicates' => true,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors(['create_employer', 'employer_profile_id']);

        $this->assertNull($opportunity->fresh()->employer_profile_id);
        $this->assertSame(CrmOpportunityStatus::NewLead, $opportunity->fresh()->status);
        $this->assertSame(1, User::query()->where('email', 'taken-employer@test.com')->count());

        $this->actingAs($admin)
            ->from(route('admin.crm.index'))
            ->put(route('admin.crm.opportunities.update', $opportunity), [
                'status' => CrmOpportunityStatus::Converted->value,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('status');

        $this->actingAs($admin)
            ->put(route('admin.crm.opportunities.update', $opportunity), [
                'status' => CrmOpportunityStatus::Contacted->value,
            ])
            ->assertRedirect();
        $this->assertSame(CrmOpportunityStatus::Contacted, $opportunity->fresh()->status);
    }

    public function test_employer_linked_tasks_and_my_work_include_assigned_crm(): void
    {
        $admin = $this->makeAdmin();
        $crmOnly = $this->makeStaffWithPermissions('crm_desk', [
            PermissionName::EmployersView->value,
            PermissionName::EmployersManage->value,
        ]);
        $jobsOnly = $this->makeStaffWithPermissions('jobs_desk_s7', [
            PermissionName::JobsView->value,
            PermissionName::JobsManage->value,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.crm.leads.store'), [
                'company_name' => 'Assigned Pipeline',
                'assigned_to' => $admin->id,
            ])
            ->assertRedirect();
        $lead = CrmLead::query()->where('company_name', 'Assigned Pipeline')->first();
        $this->assertNotNull($lead);
        $opportunity = $lead->opportunities()->first();
        $this->assertNotNull($opportunity);
        $opportunity->forceFill(['assigned_to' => $admin->id])->save();

        $this->actingAs($crmOnly)
            ->post(route('admin.tasks.store'), [
                'title' => 'Follow CRM lead',
                'crm_lead_id' => $lead->id,
                'assigned_to' => $admin->id,
            ])
            ->assertRedirect();

        $this->actingAs($jobsOnly)
            ->post(route('admin.tasks.store'), [
                'title' => 'Should not create CRM task',
                'crm_lead_id' => $lead->id,
            ])
            ->assertSessionHasErrors();

        $this->assertTrue(Task::query()->where('title', 'Follow CRM lead')->where('crm_lead_id', $lead->id)->exists());
        $this->assertFalse(Task::query()->where('title', 'Should not create CRM task')->exists());

        $this->actingAs($admin)
            ->get(route('admin.work.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Work/Index')
                ->has('crm_leads', 1)
                ->where('crm_leads.0.company_name', 'Assigned Pipeline')
                ->has('crm_opportunities', 1)
                ->where('crm_opportunities.0.id', $opportunity->id));

        $this->actingAs($admin)
            ->get(route('admin.crm.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Crm/Index')
                ->has('leads.data', 1));

        $this->actingAs($this->makeCandidate())->get(route('admin.crm.index'))->assertForbidden();
        $this->actingAs($this->makeEmployer())->post(route('admin.crm.leads.store'), [
            'company_name' => 'Employer should not create',
        ])->assertForbidden();
        $this->actingAs($crmOnly)->get(route('admin.crm.index'))->assertOk();
    }

    public function test_contact_enquiry_can_become_a_lead_and_enquiry_status_change_is_recorded(): void
    {
        $admin = $this->makeAdmin();
        $contact = ContactEnquiry::factory()->create([
            'full_name' => 'Website Visitor',
            'email' => 'visitor@contact.test',
        ]);
        $enquiry = EmployerEnquiry::factory()->create([
            'company_name' => 'Status Co',
            'status' => EmployerEnquiryStatus::New,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.website-content.contact-enquiries.lead', $contact), [
                'company_name' => 'Visitor Company',
            ])
            ->assertRedirect();

        $this->assertTrue(CrmLead::query()->where('contact_enquiry_id', $contact->id)->where('company_name', 'Visitor Company')->exists());

        $this->actingAs($admin)
            ->patch(route('admin.employer-enquiries.status', $enquiry), [
                'status' => EmployerEnquiryStatus::Contacted->value,
            ])
            ->assertRedirect();

        $this->assertSame(EmployerEnquiryStatus::Contacted, $enquiry->fresh()->status);
        $this->assertTrue(
            RecruitmentActivity::query()
                ->where('employer_enquiry_id', $enquiry->id)
                ->where('type', RecruitmentActivityType::EnquiryStatusChanged)
                ->exists()
        );
    }

    /**
     * @param  list<string>  $permissions
     */
    private function makeStaffWithPermissions(string $slug, array $permissions): User
    {
        $role = Role::query()->create([
            'name' => str_replace('_', ' ', $slug),
            'slug' => $slug,
            'is_locked' => false,
        ]);
        $role->permissions()->sync(
            Permission::query()->whereIn('name', $permissions)->pluck('id')->all()
        );

        $user = User::factory()->create([
            'name' => ucfirst($slug),
            'email' => $slug.'@example.com',
        ]);
        $user->roles()->sync([$role->id]);
        $user->forgetPermissionCache();

        return $user->fresh() ?? $user;
    }
}
