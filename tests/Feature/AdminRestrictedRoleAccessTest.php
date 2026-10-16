<?php

namespace Tests\Feature;

use App\Enums\StaffRoleSlug;
use App\Models\Job;
use Tests\TestCase;

/**
 * Focused regressions for staff roles without website.manage or jobs.manage.
 * NOT RUN — deferred by product owner (author-only batch).
 */
class AdminRestrictedRoleAccessTest extends TestCase
{
    public function test_recruiter_cannot_run_mail_diagnostics_or_read_prerequisites(): void
    {
        $recruiter = $this->makeStaff(StaffRoleSlug::Recruiter->value);

        $this->actingAs($recruiter)
            ->postJson(route('admin.settings.email.validate'))
            ->assertForbidden();

        $this->actingAs($recruiter)
            ->postJson(route('admin.settings.email.test-connection'))
            ->assertForbidden();

        $this->actingAs($recruiter)
            ->getJson(route('admin.settings.email.prerequisites'))
            ->assertForbidden();
    }

    public function test_content_manager_can_run_mail_validate_and_open_design_system(): void
    {
        $content = $this->makeStaff(StaffRoleSlug::ContentManager->value);

        $this->actingAs($content)
            ->postJson(route('admin.settings.email.validate'))
            ->assertOk();

        $this->actingAs($content)
            ->get(route('admin.design-system.index'))
            ->assertOk();
    }

    public function test_viewer_cannot_open_design_system_catalog(): void
    {
        $viewer = $this->makeStaff(StaffRoleSlug::Viewer->value);

        $this->actingAs($viewer)
            ->get(route('admin.design-system.index'))
            ->assertForbidden();
    }

    public function test_hr_operations_cannot_create_jobs_via_direct_post(): void
    {
        $hr = $this->makeStaff(StaffRoleSlug::HrOperations->value);
        $before = Job::query()->count();

        $this->actingAs($hr)
            ->post(route('admin.jobs.store'), [])
            ->assertForbidden();

        $this->assertSame($before, Job::query()->count());
    }

    public function test_finance_cannot_open_recruitment_jobs_index(): void
    {
        $finance = $this->makeStaff(StaffRoleSlug::Finance->value);

        $this->actingAs($finance)
            ->get(route('admin.jobs.index'))
            ->assertForbidden();
    }
}
