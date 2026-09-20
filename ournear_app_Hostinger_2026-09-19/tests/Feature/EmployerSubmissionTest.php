<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\EmployerSubmissionStatus;
use App\Enums\RecruitmentActivityType;
use App\Enums\ScreeningDecision;
use App\Models\Application;
use App\Models\EmployerSubmission;
use App\Models\Job;
use App\Models\RecruitmentActivity;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EmployerSubmissionTest extends TestCase
{
    public function test_shortlisted_application_can_be_submitted_with_business_id(): void
    {
        $admin = $this->makeAdmin();
        [$employer, $profile, $job, $application] = $this->shortlistedApplication();

        $this->actingAs($admin)
            ->post(route('admin.applications.submit', $application))
            ->assertRedirect();

        $submission = EmployerSubmission::query()->where('application_id', $application->id)->first();
        $this->assertNotNull($submission);
        $this->assertSame($profile->id, $submission->employer_profile_id);
        $this->assertSame($job->id, $submission->job_id);
        $this->assertSame($application->id, $submission->application_id);
        $this->assertSame(EmployerSubmissionStatus::Submitted, $submission->status);
        $this->assertNotNull($submission->business_id);
        $this->assertStringStartsWith('ONT-S-', $submission->business_id);
        $this->assertSame(ApplicationStatus::Shortlisted, $application->fresh()->status);

        $this->assertTrue(RecruitmentActivity::query()
            ->where('employer_submission_id', $submission->id)
            ->where('type', RecruitmentActivityType::CandidateSubmitted->value)
            ->exists());
    }

    public function test_non_shortlisted_and_unassigned_jobs_cannot_be_submitted(): void
    {
        $admin = $this->makeAdmin();
        $employer = $this->makeEmployer();
        $profile = $employer->employerProfile;
        $assigned = Job::factory()->create(['employer_id' => $profile->id]);
        $unassigned = Job::factory()->create(['employer_id' => null]);
        $newApplication = Application::factory()->create([
            'job_id' => $assigned->id,
            'status' => ApplicationStatus::New,
        ]);
        $shortlistedUnassigned = Application::factory()->create([
            'job_id' => $unassigned->id,
            'status' => ApplicationStatus::Shortlisted,
        ]);

        $this->actingAs($admin)
            ->from(route('admin.shortlisted.index', ['application' => $newApplication->id]))
            ->post(route('admin.applications.submit', $newApplication))
            ->assertSessionHasErrors(['application' => 'Only shortlisted applications can be submitted.']);

        $this->actingAs($admin)
            ->from(route('admin.shortlisted.index', ['application' => $shortlistedUnassigned->id]))
            ->post(route('admin.applications.submit', $shortlistedUnassigned))
            ->assertSessionHasErrors(['application' => 'This job is not assigned to an employer yet.']);

        $this->assertSame(0, EmployerSubmission::query()->count());
        $this->assertSame(ApplicationStatus::New, $newApplication->fresh()->status);
    }

    public function test_duplicate_submission_is_prevented_and_selection_is_job_specific(): void
    {
        $admin = $this->makeAdmin();
        [$employer, $profile, $job, $application] = $this->shortlistedApplication();
        $otherJob = Job::factory()->create(['employer_id' => $profile->id]);
        $otherApplication = Application::factory()->create([
            'job_id' => $otherJob->id,
            'user_id' => $application->user_id,
            'full_name' => $application->full_name,
            'status' => ApplicationStatus::Shortlisted,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.applications.submit', $application))
            ->assertRedirect();

        $this->actingAs($admin)
            ->from(route('admin.submissions.index'))
            ->post(route('admin.applications.submit', $application))
            ->assertSessionHasErrors('application');

        $this->assertSame(1, EmployerSubmission::query()->where('application_id', $application->id)->count());

        $this->actingAs($admin)
            ->post(route('admin.applications.submit', $otherApplication))
            ->assertRedirect();

        $first = EmployerSubmission::query()->where('application_id', $application->id)->first();
        $this->actingAs($admin)
            ->post(route('admin.submissions.select', $first), ['notes' => 'Proceed'])
            ->assertRedirect();

        $first->refresh();
        $this->assertSame(EmployerSubmissionStatus::Selected, $first->status);
        $this->assertSame(ApplicationStatus::Shortlisted, $application->fresh()->status);
        $this->assertSame(ApplicationStatus::Shortlisted, $otherApplication->fresh()->status);
        $this->assertSame(EmployerSubmissionStatus::Submitted, EmployerSubmission::query()->where('application_id', $otherApplication->id)->value('status'));
        $this->assertNull($application->fresh()->user?->candidateProfile?->fresh()->location_id);
        $this->assertTrue(RecruitmentActivity::query()
            ->where('employer_submission_id', $first->id)
            ->where('type', RecruitmentActivityType::EmployerSelected->value)
            ->exists());

        $this->actingAs($admin)
            ->from(route('admin.submissions.index', ['submission' => $first->id]))
            ->post(route('admin.submissions.select', $first))
            ->assertSessionHasErrors('status');
    }

    public function test_employer_rejection_does_not_change_application_status(): void
    {
        $admin = $this->makeAdmin();
        [, , , $application] = $this->shortlistedApplication();

        $this->actingAs($admin)->post(route('admin.applications.submit', $application));
        $submission = EmployerSubmission::query()->where('application_id', $application->id)->first();

        $this->actingAs($admin)
            ->post(route('admin.submissions.reject', $submission), [
                'notes' => 'Client declined.',
            ])
            ->assertRedirect();

        $this->assertSame(EmployerSubmissionStatus::Rejected, $submission->fresh()->status);
        $this->assertSame(ApplicationStatus::Shortlisted, $application->fresh()->status);
        $this->assertTrue(Application::query()->whereKey($application->id)->exists());
        $this->assertSame(ScreeningDecision::Pending, $application->fresh()->load('screening')->screeningDecision());
        $this->assertTrue(RecruitmentActivity::query()
            ->where('employer_submission_id', $submission->id)
            ->where('type', RecruitmentActivityType::EmployerRejected->value)
            ->exists());
        $this->assertFalse(Schema::hasTable('placements'));
        $this->assertFalse(Schema::hasTable('employees'));
    }

    public function test_guest_application_can_be_submitted_when_shortlisted(): void
    {
        $admin = $this->makeAdmin();
        $employer = $this->makeEmployer();
        $profile = $employer->employerProfile;
        $job = Job::factory()->create(['employer_id' => $profile->id]);
        $application = Application::factory()->create([
            'job_id' => $job->id,
            'user_id' => null,
            'status' => ApplicationStatus::Shortlisted,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.applications.submit', $application))
            ->assertRedirect();

        $this->actingAs($admin)
            ->get(route('admin.submissions.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Submissions/Index')
                ->has('submissions.data', 1)
                ->where('submissions.data.0.candidate.business_id', null));
    }

    public function test_candidate_cannot_manage_submissions(): void
    {
        [, , , $application] = $this->shortlistedApplication();

        $this->actingAs($this->makeCandidate())
            ->post(route('admin.applications.submit', $application))
            ->assertForbidden();

        $this->actingAs($this->makeEmployer())
            ->get(route('admin.submissions.index'))
            ->assertForbidden();
    }

    /**
     * @return array{0: \App\Models\User, 1: \App\Models\EmployerProfile, 2: Job, 3: Application}
     */
    private function shortlistedApplication(): array
    {
        $employer = $this->makeEmployer();
        $profile = $employer->employerProfile;
        $this->assertNotNull($profile);
        $profile->update(['company_name' => 'SiteCo']);
        $job = Job::factory()->create(['employer_id' => $profile->id, 'title' => 'Civil Engineer']);
        $candidate = $this->makeCandidate(['name' => 'Asha Candidate']);
        $application = Application::factory()->create([
            'job_id' => $job->id,
            'user_id' => $candidate->id,
            'full_name' => 'Asha Candidate',
            'status' => ApplicationStatus::Shortlisted,
        ]);

        return [$employer, $profile, $job, $application];
    }
}
