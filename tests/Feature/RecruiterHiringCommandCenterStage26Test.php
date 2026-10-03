<?php

namespace Tests\Feature;

use App\Enums\StaffRoleSlug;
use App\Models\Application;
use App\Models\Job;
use App\Services\RecruiterHiringCommandCenterService;
use App\Services\ScreeningMatchService;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RecruiterHiringCommandCenterStage26Test extends TestCase
{
    public function test_command_center_reuses_existing_screening_match_and_does_not_add_a_second_engine(): void
    {
        $this->assertFileExists(app_path('Services/ScreeningMatchService.php'));
        $this->assertFileDoesNotExist(app_path('Services/RecruiterMatchingService.php'));
        $this->assertFileDoesNotExist(app_path('Services/RecruiterShortlistService.php'));
        $source = file_get_contents(app_path('Services/RecruiterHiringCommandCenterService.php'));
        $this->assertIsString($source);
        $this->assertStringNotContainsString('new ScreeningMatchService', $source);
        $this->assertStringNotContainsString('->shortlist(', $source);
        $this->assertTrue(class_exists(ScreeningMatchService::class));
        $this->assertTrue(class_exists(RecruiterHiringCommandCenterService::class));
    }

    public function test_guests_and_account_users_cannot_open_the_command_center(): void
    {
        $this->get(route('admin.hiring.index'))->assertRedirect(route('admin.login'));
        $this->actingAs($this->makeCandidate())->get(route('admin.hiring.index'))->assertForbidden();
        $this->actingAs($this->makeEmployer())->get(route('admin.hiring.index'))->assertForbidden();
    }

    public function test_content_manager_cannot_open_the_command_center(): void
    {
        $this->actingAs($this->makeStaff(StaffRoleSlug::ContentManager->value))
            ->get(route('admin.hiring.index'))
            ->assertForbidden();
    }

    public function test_recruiter_sees_ats_partnership_and_jobs_but_not_finance_invoices(): void
    {
        $job = Job::factory()->create(['title' => 'Site Supervisor']);
        Application::factory()->create([
            'job_id' => $job->id,
            'full_name' => 'Stage 26 Candidate',
            'user_id' => null,
        ]);

        $recruiter = $this->makeStaff(StaffRoleSlug::Recruiter->value);

        $this->actingAs($recruiter)
            ->get(route('admin.hiring.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Hiring/CommandCenter')
                ->where('permissions.ats', true)
                ->where('permissions.partnership', true)
                ->where('permissions.staffing', true)
                ->where('permissions.jobs', true)
                ->where('permissions.finance', false)
                ->has('ats')
                ->has('partnership')
                ->has('jobs_board')
                ->where('staffing.queue.invoices', [])
                ->has('attention')
                ->has('links.screening'));
    }

    public function test_finance_staff_cannot_read_application_queues_but_can_open_staffing(): void
    {
        Application::factory()->create(['full_name' => 'Secret Applicant']);

        $finance = $this->makeStaff(StaffRoleSlug::Finance->value);

        $this->actingAs($finance)
            ->get(route('admin.hiring.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Hiring/CommandCenter')
                ->where('permissions.ats', false)
                ->where('permissions.finance', true)
                ->where('permissions.staffing', true)
                ->where('ats', null)
                ->where('partnership', null)
                ->where('jobs_board', null)
                ->has('staffing'));
    }

    public function test_product_line_filter_hides_other_modules(): void
    {
        $recruiter = $this->makeStaff(StaffRoleSlug::Recruiter->value);

        $this->actingAs($recruiter)
            ->get(route('admin.hiring.index', ['line' => 'staffing']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.line', 'staffing')
                ->where('ats', null)
                ->where('partnership', null)
                ->where('jobs_board', null)
                ->where('activity', [])
                ->has('staffing'));
    }

    public function test_job_filter_limits_application_counts(): void
    {
        $keep = Job::factory()->create();
        $other = Job::factory()->create();
        Application::factory()->create(['job_id' => $keep->id, 'full_name' => 'Keep Me']);
        Application::factory()->create(['job_id' => $other->id, 'full_name' => 'Other Job Applicant']);

        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->get(route('admin.hiring.index', ['job_id' => $keep->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.job_id', (string) $keep->id)
                ->has('jobs_board.recent')
                ->where('jobs_board.recent.0.title', 'Keep Me'));
    }

    public function test_invalid_line_is_rejected(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->get(route('admin.hiring.index', ['line' => 'unknown']))
            ->assertSessionHasErrors('line');
    }
}
