<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\BusinessIdPrefix;
use App\Enums\StaffRoleSlug;
use App\Models\Application;
use App\Models\Brand;
use App\Models\CandidateProfile;
use App\Models\Invoice;
use App\Models\Job;
use App\Models\User;
use App\Services\Admin\DashboardService;
use App\Services\MasterData\BusinessIdGenerator;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PhaseF6ReadinessTest extends TestCase
{
    public function test_super_admin_can_open_every_v11_admin_module(): void
    {
        $admin = $this->makeAdmin();

        $routes = [
            'admin.dashboard',
            'admin.users.index',
            'admin.users.activity',
            'admin.staff.index',
            'admin.profile.show',
            'admin.jobs.index',
            'admin.candidates.index',
            'admin.applications.index',
            'admin.screening.index',
            'admin.shortlisted.index',
            'admin.submissions.index',
            'admin.employers.index',
            'admin.employer-enquiries.index',
            'admin.crm.index',
            'admin.partners.index',
            'admin.commission.index',
            'admin.investors.index',
            'admin.campaigns.index',
            'admin.automations.index',
            'admin.work.index',
            'admin.interviews.index',
            'admin.tasks.index',
            'admin.placements.index',
            'admin.employees.index',
            'admin.replacements.index',
            'admin.finance.index',
            'admin.notifications.index',
            'admin.messages.index',
            'admin.activity-logs.index',
            'admin.login-logs.index',
            'admin.website-content.index',
            'admin.media-social.index',
            'admin.settings.edit',
            'admin.roles.index',
            'admin.master-data.index',
        ];

        foreach ($routes as $name) {
            $this->actingAs($admin)->get(route($name))->assertOk();
        }
    }

    public function test_guest_cannot_open_protected_admin_routes(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
        $this->get(route('admin.users.index'))->assertRedirect(route('admin.login'));
        $this->get(route('admin.finance.index'))->assertRedirect(route('admin.login'));
    }

    public function test_website_settings_require_website_manage_on_direct_url(): void
    {
        $this->actingAs($this->makeStaff(StaffRoleSlug::Recruiter->value))
            ->get(route('admin.settings.edit'))
            ->assertForbidden();

        $this->actingAs($this->makeStaff(StaffRoleSlug::ContentManager->value))
            ->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Settings'));

        $recruiter = $this->makeStaff(StaffRoleSlug::Recruiter->value);
        $this->actingAs($recruiter)
            ->get(route('admin.settings.edit', ['tab' => 'account']))
            ->assertRedirect(route('admin.profile.show'));
    }

    public function test_admin_profile_stays_separate_from_website_settings(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->get(route('admin.profile.show'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Profile/Show')
                ->where('profile.email', $admin->email)
                ->missing('websiteSettings'));

        $this->actingAs($admin)
            ->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Settings')
                ->has('websiteSettings')
                ->missing('profile'));
    }

    public function test_finance_legacy_tab_query_opens_payments_view(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->get(route('admin.finance.index', ['tab' => 'payments']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Finance/Index')
                ->where('filters.view', 'payments')
                ->where('dashboard.gateway_configured', false)
                ->where('dashboard.gateway_message', 'Gateway integration not configured'));

        $this->actingAs($admin)
            ->get(route('admin.finance.index', ['view' => 'payments']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('filters.view', 'payments'));
    }

    public function test_opening_records_does_not_change_application_or_invoice_status(): void
    {
        $admin = $this->makeAdmin();
        $application = Application::factory()->create(['status' => ApplicationStatus::New]);

        $this->actingAs($admin)
            ->get(route('admin.applications.index', ['application' => $application->id]))
            ->assertOk();
        $this->actingAs($admin)
            ->get(route('admin.screening.index', ['application' => $application->id]))
            ->assertOk();
        $this->actingAs($admin)
            ->get(route('admin.shortlisted.index'))
            ->assertOk();
        $this->actingAs($admin)
            ->get(route('admin.finance.index'))
            ->assertOk();

        $this->assertSame(ApplicationStatus::New, $application->fresh()->status);
        $this->assertSame(0, Invoice::query()->count());
    }

    public function test_user_is_not_treated_as_a_candidate_and_ids_stay_distinct(): void
    {
        $admin = $this->makeAdmin();
        $candidate = $this->makeCandidate(['name' => 'Asha Candidate']);

        $this->assertMatchesRegularExpression('/^ONT-U-\d{6}$/', (string) $admin->business_id);
        $this->assertMatchesRegularExpression('/^ONT-U-\d{6}$/', (string) $candidate->business_id);
        $this->assertMatchesRegularExpression('/^ONT-C-\d{6}$/', (string) $candidate->candidateProfile?->business_id);
        $this->assertNotSame($candidate->business_id, $candidate->candidateProfile?->business_id);
        $this->assertNull($admin->candidateProfile);

        $this->actingAs($admin)
            ->get(route('admin.users.show', $admin))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('record.candidate', null)
                ->where('record.user.business_id', $admin->business_id));

        $this->actingAs($admin)
            ->get(route('admin.users.show', $candidate))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('record.candidate.business_id', $candidate->candidateProfile?->business_id)
                ->where('record.candidate.workspace_url', '/admin/candidates?candidate='.$candidate->candidateProfile?->id)
                ->where('record.candidate.user_360_url', '/admin/users/'.$candidate->id));
    }

    public function test_user_360_resume_view_and_download_are_independent(): void
    {
        $admin = $this->makeAdmin();
        $candidate = $this->makeCandidate();
        $path = 'f6-resume.pdf';
        Storage::disk('resumes')->put($path, 'resume-bytes');
        $application = Application::factory()->create([
            'user_id' => $candidate->id,
            'resume_path' => $path,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.show', $candidate))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('record.documents.0.view_href', '/admin/applications/'.$application->id.'/resume?disposition=inline')
                ->where('record.documents.0.download_href', '/admin/applications/'.$application->id.'/resume'));

        $download = $this->actingAs($admin)->get(route('admin.applications.resume', $application));
        $download->assertOk();
        $this->assertStringContainsString('attachment', (string) $download->headers->get('content-disposition'));

        $view = $this->actingAs($admin)->get(route('admin.applications.resume', [
            'application' => $application,
            'disposition' => 'inline',
        ]));
        $view->assertOk();
        $this->assertStringContainsString('inline', (string) $view->headers->get('content-disposition'));
    }

    public function test_dashboard_counts_match_database_queries(): void
    {
        $admin = $this->makeAdmin();
        $this->makeCandidate();
        Job::factory()->create(['active' => true]);
        Application::factory()->create(['status' => ApplicationStatus::New]);

        $stats = app(DashboardService::class)->stats();

        $this->assertSame(User::query()->count(), $stats['platform']['total_users']['value']);
        $this->assertSame(
            User::query()->whereNotNull('email_verified_at')->count(),
            $stats['platform']['verified_users']['value'],
        );
        $this->assertSame(
            Job::query()->where('active', true)->count(),
            $stats['recruitment']['active_jobs']['value'],
        );
        $this->assertSame(Application::query()->count(), $stats['recruitment']['applications']['value']);
        $this->assertSame(
            CandidateProfile::query()->count(),
            $stats['recruitment']['candidates']['value'],
        );
        $this->assertSame(0, $stats['recruitment']['employer_submissions']['value']);
        $this->assertSame(0, $stats['recruitment']['placements']['value']);
        $this->assertSame(0, $stats['recruitment']['joined_employees']['value']);
        $this->assertSame(0, $stats['recruitment']['replacement_cases']['value']);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('stats.platform.total_users.value', $stats['platform']['total_users']['value'])
                ->where('stats.recruitment.applications.value', $stats['recruitment']['applications']['value']));
    }

    public function test_business_id_generator_stays_unique_and_does_not_regenerate_on_edit(): void
    {
        $generator = app(BusinessIdGenerator::class);
        $first = $generator->next(BusinessIdPrefix::Job);
        $second = $generator->next(BusinessIdPrefix::Job);
        $this->assertNotSame($first, $second);

        $job = Job::factory()->create(['title' => 'Original']);
        $original = $job->business_id;
        $job->update(['title' => 'Updated']);
        $job->business_id = 'ONT-J-999999';
        $job->save();

        $this->assertSame($original, $job->fresh()->business_id);
        $this->assertSame('Updated', $job->fresh()->title);
    }

    public function test_admin_payloads_do_not_expose_passwords_and_brand_association_remains(): void
    {
        $admin = $this->makeAdmin();
        $candidate = $this->makeCandidate();

        $this->assertArrayNotHasKey('password', $admin->toAdminArray());
        $this->assertArrayNotHasKey('password', $candidate->toAdminArray());
        $this->assertNotNull($candidate->brand_id);
        $this->assertSame(Brand::query()->where('is_primary', true)->value('id'), $candidate->brand_id);

        $this->actingAs($admin)
            ->get(route('admin.profile.show'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->missing('profile.password'));
    }

    public function test_viewer_can_view_jobs_but_cannot_manage_them(): void
    {
        $viewer = $this->makeStaff(StaffRoleSlug::Viewer->value);
        $job = Job::factory()->create(['title' => 'Keep Title']);

        $this->actingAs($viewer)->get(route('admin.jobs.index'))->assertOk();
        $this->actingAs($viewer)->put(route('admin.jobs.update', $job), ['title' => 'Nope'])->assertForbidden();
        $this->assertSame('Keep Title', $job->fresh()->title);
        $this->assertFalse($viewer->can('create', Invoice::class));
        $this->assertFalse($viewer->can('create', Job::class));
    }

    public function test_guest_application_without_candidate_account_still_opens(): void
    {
        $admin = $this->makeAdmin();
        $application = Application::factory()->create([
            'user_id' => null,
            'full_name' => 'Guest Applicant',
            'status' => ApplicationStatus::New,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.applications.index', ['application' => $application->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('selected.full_name', 'Guest Applicant')
                ->where('selected.candidate.id', null)
                ->where('selected.candidate.business_id', null));
    }
}
