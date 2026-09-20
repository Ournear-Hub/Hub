<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\RecruitmentActivityType;
use App\Models\Application;
use App\Models\EmployerEnquiry;
use App\Models\Job;
use App\Models\RecruitmentActivity;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    public function test_admin_can_access_core_admin_pages(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Dashboard'));

        $this->actingAs($admin)->get(route('admin.jobs.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Jobs/Index'));

        $this->actingAs($admin)->get(route('admin.applications.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Applications/Index'));

        $this->actingAs($admin)->get(route('admin.employer-enquiries.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/EmployerEnquiries/Index'));

        $this->actingAs($admin)->get(route('admin.website-content.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/WebsiteContent/Index'));

        $this->actingAs($admin)->get(route('admin.media-social.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/MediaSocial/Index'));

        $this->actingAs($admin)->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Settings'));
    }

    public function test_admin_can_view_job_listing_and_detail(): void
    {
        $admin = $this->makeAdmin();
        $job = Job::factory()->create(['title' => 'Electrical Engineer']);

        $this->actingAs($admin)->get(route('admin.jobs.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Jobs/Index')
                ->has('jobs.data', 1)
                ->where('jobs.data.0.title', 'Electrical Engineer'));

        $this->actingAs($admin)->get(route('admin.jobs.show', $job))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Jobs/Index')
                ->where('selected.title', 'Electrical Engineer')
                ->where('filters.job', $job->slug));
    }

    public function test_admin_application_drawer_preserves_filters(): void
    {
        $admin = $this->makeAdmin();
        $application = Application::factory()->create([
            'full_name' => 'Priya Applicant',
            'status' => ApplicationStatus::New,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.applications.index', [
                'status' => ApplicationStatus::New->value,
                'application' => $application->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Applications/Index')
                ->where('filters.status', ApplicationStatus::New->value)
                ->where('filters.application', (string) $application->id)
                ->where('selected.id', $application->id)
                ->where('selected.full_name', 'Priya Applicant'));
    }

    public function test_admin_can_change_application_status_and_record_activity(): void
    {
        $admin = $this->makeAdmin();
        $application = Application::factory()->create([
            'status' => ApplicationStatus::New,
        ]);

        $this->actingAs($admin)
            ->from(route('admin.applications.index', ['application' => $application->id]))
            ->patch(route('admin.applications.status', $application), [
                'status' => ApplicationStatus::Reviewing->value,
            ])
            ->assertRedirect();

        $application->refresh();
        $this->assertSame(ApplicationStatus::Reviewing, $application->status);

        $activity = RecruitmentActivity::query()->where('application_id', $application->id)->latest('id')->first();
        $this->assertNotNull($activity);
        $this->assertSame(RecruitmentActivityType::StatusChanged, $activity->type);
        $this->assertSame(ApplicationStatus::New->value, $activity->from_status);
        $this->assertSame(ApplicationStatus::Reviewing->value, $activity->to_status);
        $this->assertSame($admin->id, $activity->actor_id);
    }

    public function test_admin_can_download_application_resume(): void
    {
        $admin = $this->makeAdmin();
        $path = 'admin-resume.pdf';
        Storage::disk('resumes')->put($path, 'resume-bytes');
        $application = Application::factory()->create(['resume_path' => $path]);

        $this->actingAs($admin)
            ->get(route('admin.applications.resume', $application))
            ->assertOk();
    }

    public function test_admin_employer_enquiry_drawer_preserves_filters(): void
    {
        $admin = $this->makeAdmin();
        $enquiry = EmployerEnquiry::factory()->create([
            'company_name' => 'BuildCo',
            'industry' => 'Construction',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.employer-enquiries.index', [
                'industry' => 'Construction',
                'enquiry' => $enquiry->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/EmployerEnquiries/Index')
                ->where('filters.industry', 'Construction')
                ->where('filters.enquiry', (string) $enquiry->id)
                ->where('selected.id', $enquiry->id)
                ->where('selected.company_name', 'BuildCo'));
    }
}
