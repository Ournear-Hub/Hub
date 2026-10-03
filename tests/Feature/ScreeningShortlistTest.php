<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\RecruitmentActivityType;
use App\Enums\ScreeningDecision;
use App\Models\Application;
use App\Models\ApplicationScreening;
use App\Models\Job;
use App\Models\RejectionReason;
use App\Models\RecruitmentActivity;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ScreeningShortlistTest extends TestCase
{
    public function test_admin_can_save_screening_decision_and_rejection_reason(): void
    {
        $admin = $this->makeAdmin();
        $application = Application::factory()->create(['status' => ApplicationStatus::New]);
        $reason = RejectionReason::query()->first();
        $this->assertNotNull($reason);

        $this->actingAs($admin)
            ->from(route('admin.screening.index', ['application' => $application->id]))
            ->put(route('admin.applications.screening', $application), [
                'decision' => ScreeningDecision::NotSuitable->value,
                'notes' => 'Does not meet the role requirements.',
                'rejection_reason_id' => $reason->id,
            ])
            ->assertRedirect();

        $application->refresh();
        $this->assertSame(ApplicationStatus::Rejected, $application->status);
        $this->assertNotNull($application->screening);
        $this->assertSame(ScreeningDecision::NotSuitable, $application->screening->decision);
        $this->assertSame($reason->id, $application->screening->rejection_reason_id);
        $this->assertSame($admin->id, $application->screening->screened_by);
        $this->assertNotNull($application->screening->screened_at);

        $this->assertTrue(RecruitmentActivity::query()
            ->where('application_id', $application->id)
            ->where('type', RecruitmentActivityType::MarkedNotSuitable->value)
            ->exists());
    }

    public function test_suitable_screening_moves_new_applications_to_reviewing(): void
    {
        $admin = $this->makeAdmin();
        $application = Application::factory()->create(['status' => ApplicationStatus::New]);

        $this->actingAs($admin)
            ->put(route('admin.applications.screening', $application), [
                'decision' => ScreeningDecision::Suitable->value,
                'notes' => 'Strong civil background.',
            ])
            ->assertRedirect();

        $application->refresh();
        $this->assertSame(ApplicationStatus::Reviewing, $application->status);
        $this->assertSame(ScreeningDecision::Suitable, $application->screening?->decision);
    }

    public function test_screening_page_lists_applications_and_is_admin_only(): void
    {
        $admin = $this->makeAdmin();
        Application::factory()->create(['full_name' => 'Screenable Candidate']);

        $this->actingAs($admin)
            ->get(route('admin.screening.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Screening/Index')
                ->has('applications.data', 1)
                ->where('applications.data.0.screening_decision_label', 'Pending'));

        $this->actingAs($this->makeCandidate())
            ->get(route('admin.screening.index'))
            ->assertForbidden();
    }

    public function test_application_can_be_shortlisted_for_one_job_without_affecting_another(): void
    {
        $admin = $this->makeAdmin();
        $candidate = $this->makeCandidate(['name' => 'Asha Candidate']);
        $civil = Job::factory()->create(['title' => 'Civil Engineer']);
        $electrical = Job::factory()->create(['title' => 'Electrical Engineer']);
        $civilApplication = Application::factory()->create([
            'job_id' => $civil->id,
            'user_id' => $candidate->id,
            'full_name' => 'Asha Candidate',
            'status' => ApplicationStatus::New,
        ]);
        $electricalApplication = Application::factory()->create([
            'job_id' => $electrical->id,
            'user_id' => $candidate->id,
            'full_name' => 'Asha Candidate',
            'status' => ApplicationStatus::New,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.applications.shortlist', $civilApplication), [
                'notes' => 'Ready for client review.',
            ])
            ->assertRedirect();

        $civilApplication->refresh();
        $electricalApplication->refresh();

        $this->assertSame(ApplicationStatus::Shortlisted, $civilApplication->status);
        $this->assertSame(ScreeningDecision::Shortlisted, $civilApplication->screening?->decision);
        $this->assertSame($admin->id, $civilApplication->screening?->shortlisted_by);
        $this->assertNotNull($civilApplication->screening?->shortlisted_at);
        $this->assertSame(ApplicationStatus::New, $electricalApplication->status);
        $this->assertNull($electricalApplication->screening);

        $this->actingAs($admin)
            ->get(route('admin.shortlisted.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Shortlisted/Index')
                ->has('applications.data', 1)
                ->where('applications.data.0.id', $civilApplication->id)
                ->where('applications.data.0.job.title', 'Civil Engineer'));
    }

    public function test_duplicate_shortlist_is_rejected_and_activity_is_recorded(): void
    {
        $admin = $this->makeAdmin();
        $application = Application::factory()->create(['status' => ApplicationStatus::Reviewing]);

        $this->actingAs($admin)
            ->post(route('admin.applications.shortlist', $application))
            ->assertRedirect();

        $this->actingAs($admin)
            ->from(route('admin.shortlisted.index', ['application' => $application->id]))
            ->post(route('admin.applications.shortlist', $application))
            ->assertSessionHasErrors('decision');

        $this->assertSame(1, ApplicationScreening::query()->where('application_id', $application->id)->count());
        $this->assertSame(1, RecruitmentActivity::query()
            ->where('application_id', $application->id)
            ->where('type', RecruitmentActivityType::Shortlisted->value)
            ->count());
        $this->assertTrue(RecruitmentActivity::query()
            ->where('application_id', $application->id)
            ->where('type', RecruitmentActivityType::StatusChanged->value)
            ->where('to_status', ApplicationStatus::Shortlisted->value)
            ->exists());
    }

    public function test_existing_v1_shortlisted_status_appears_without_a_screening_record(): void
    {
        $admin = $this->makeAdmin();
        $application = Application::factory()->create([
            'status' => ApplicationStatus::Shortlisted,
        ]);

        $this->assertNull($application->screening);

        $this->actingAs($admin)
            ->get(route('admin.shortlisted.index', ['application' => $application->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('applications.data', 1)
                ->where('selected.status', ApplicationStatus::Shortlisted->value)
                ->where('selected.screening.decision', ScreeningDecision::Pending->value));
    }

    public function test_not_suitable_requires_a_rejection_reason_and_does_not_delete_the_application(): void
    {
        $admin = $this->makeAdmin();
        $application = Application::factory()->create();

        $this->actingAs($admin)
            ->from(route('admin.screening.index', ['application' => $application->id]))
            ->put(route('admin.applications.screening', $application), [
                'decision' => ScreeningDecision::NotSuitable->value,
            ])
            ->assertSessionHasErrors('rejection_reason_id');

        $this->assertTrue(Application::query()->whereKey($application->id)->exists());
        $this->assertSame(ApplicationStatus::New, $application->fresh()->status);
    }

    public function test_candidate_cannot_shortlist(): void
    {
        $application = Application::factory()->create();

        $this->actingAs($this->makeCandidate())
            ->post(route('admin.applications.shortlist', $application))
            ->assertForbidden();

        $this->actingAs($this->makeEmployer())
            ->get(route('admin.shortlisted.index'))
            ->assertForbidden();
    }
}
