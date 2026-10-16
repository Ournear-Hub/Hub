<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\RecruiterTalentPoolAssignmentType;
use App\Enums\StaffRoleSlug;
use App\Models\Application;
use App\Models\EmploymentType;
use App\Models\ExperienceRange;
use App\Models\Job;
use App\Models\JobTitle;
use App\Models\Location;
use App\Models\TalentPoolCompanySubmission;
use App\Models\User;
use App\Services\RecruiterTalentPoolAssignmentService;
use App\Services\TalentPoolCompanySubmissionService;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EmployerHiringAtsBatch5Test extends TestCase
{
    public function test_hiring_dashboard_prompts_create_job_when_no_jobs(): void
    {
        $employer = $this->eligibleEmployer();
        $employer->employerProfile->update(['company_name' => 'Batch5 Co']);

        $this->actingAs($employer)
            ->get(route('account.hiring'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Hiring/Dashboard')
                ->where('workspace.jobs_count', 0)
                ->where('workspace.can_post_job', true)
                ->where('workspace.next_actions.0.href', '/account/jobs/create'));
    }

    public function test_posting_eligibility_next_action_points_to_company_profile(): void
    {
        $employer = $this->makeEmployer([
            'email_verified_at' => null,
            'mobile_verified_at' => null,
        ]);
        $employer->employerProfile->update(['company_name' => 'Needs Setup Co']);

        $this->actingAs($employer)
            ->get(route('account.hiring'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('workspace.next_actions', fn (Assert $actions) => $actions
                    ->where('0.href', '/account/company')
                    ->etc()));
    }

    public function test_employer_can_create_and_list_own_job(): void
    {
        $employer = $this->eligibleEmployer();

        $this->actingAs($employer)
            ->get(route('account.employer.jobs.create'))
            ->assertOk();

        $this->actingAs($employer)
            ->post(route('account.employer.jobs.store'), $this->jobPayload(['title' => 'Batch5 QA Role']))
            ->assertRedirect();

        $job = Job::query()->where('employer_id', $employer->employerProfile->id)->latest('id')->first();
        $this->assertNotNull($job);

        $this->actingAs($employer)
            ->get(route('account.employer.jobs.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('jobs.data', 1));

        $other = $this->makeEmployer([
            'email' => 'other-batch5@example.test',
            'email_verified_at' => now(),
            'mobile_verified_at' => now(),
        ]);
        $this->actingAs($other)
            ->get(route('account.employer.jobs.edit', $job))
            ->assertForbidden();
    }

    public function test_shortlist_persists_status_and_activity_history(): void
    {
        $employer = $this->eligibleEmployer();
        $candidate = $this->makeCandidate(['email_verified_at' => now(), 'mobile_verified_at' => now()]);
        $this->giveCandidateResume($candidate);
        $job = Job::factory()->create(['employer_id' => $employer->employerProfile->id]);
        $this->applyAsCandidate($candidate, $job);
        $application = Application::query()->where('job_id', $job->id)->firstOrFail();

        $this->actingAs($employer)
            ->post(route('account.employer.jobs.application.shortlist', [$job, $application]), [
                'notes' => 'Strong fit for Batch5.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $application->refresh();
        $this->assertSame(ApplicationStatus::Shortlisted, $application->status);

        $this->assertDatabaseHas('recruitment_activities', [
            'application_id' => $application->id,
            'type' => 'shortlisted',
            'actor_id' => $employer->id,
        ]);

        $this->actingAs($employer)
            ->get(route('account.employer.jobs.application', [$job, $application]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('application.status', ApplicationStatus::Shortlisted->value)
                ->where('application.timeline', fn ($timeline) => collect($timeline)->contains(
                    fn ($row) => ($row['key'] ?? '') === 'shortlisted' && ($row['actor_name'] ?? '') === $employer->name,
                )));
    }

    public function test_talent_pool_submission_does_not_appear_in_employer_applications_index(): void
    {
        $employer = $this->eligibleEmployer();
        $recruiter = $this->makeStaff(StaffRoleSlug::Recruiter->value);
        $admin = $this->makeStaff(StaffRoleSlug::SuperAdmin->value);
        $candidate = $this->makeCandidate();
        $this->giveCandidateResume($candidate);

        app(RecruiterTalentPoolAssignmentService::class)->assign(
            $admin,
            $candidate,
            $recruiter,
            RecruiterTalentPoolAssignmentType::TalentPool,
        );

        app(TalentPoolCompanySubmissionService::class)->submit(
            $candidate,
            $employer->employerProfile,
            $recruiter,
        );

        $this->assertSame(1, TalentPoolCompanySubmission::query()->count());
        $this->assertSame(0, Application::query()->whereHas('job', fn ($q) => $q->where('employer_id', $employer->employerProfile->id))->count());

        $this->actingAs($employer)
            ->get(route('account.hiring.applications'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('applications.total', 0));

        $this->actingAs($employer)
            ->get(route('account.coordinated-talent-pool.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('submissions.data', 1));
    }

    public function test_candidate_profile_basic_update_persists(): void
    {
        $candidate = $this->makeCandidate();

        $this->actingAs($candidate)
            ->put(route('account.profile.update'), [
                'name' => $candidate->name,
                'username' => $candidate->username,
                'preferred_language' => 'en',
                'bio' => 'After batch5 persistence check',
            ])
            ->assertRedirect();

        $this->assertSame('After batch5 persistence check', $candidate->fresh()->bio);
    }

    private function eligibleEmployer(array $overrides = []): User
    {
        return $this->makeEmployer(array_merge([
            'email_verified_at' => now(),
            'mobile_verified_at' => now(),
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function jobPayload(array $overrides = []): array
    {
        $title = JobTitle::query()->with('category.industry')->where('is_active', true)->firstOrFail();
        $location = Location::query()->where('is_active', true)->first()
            ?? Location::query()->create(['name' => 'Test City', 'is_active' => true, 'sort_order' => 1]);
        $employment = EmploymentType::query()->where('is_active', true)->first()
            ?? EmploymentType::query()->create(['name' => 'Full-time', 'is_active' => true, 'sort_order' => 1]);
        $experience = ExperienceRange::query()->where('is_active', true)->first()
            ?? ExperienceRange::query()->create(['name' => '1-3 years', 'is_active' => true, 'sort_order' => 1]);

        return [
            'title' => 'Employer Posted Role',
            'project' => 'Submitted Company',
            'industry_id' => $title->category->industry_id,
            'job_category_id' => $title->job_category_id,
            'job_title_id' => $title->id,
            'location_id' => $location->id,
            'employment_type_id' => $employment->id,
            'experience_range_id' => $experience->id,
            'salary' => 'Competitive',
            'vacancy' => 2,
            'posted_date' => now()->toDateString(),
            'closing_date' => now()->addDays(14)->toDateString(),
            'featured' => false,
            'active' => true,
            'publishing_status' => 'published',
            'description' => 'Employer job description.',
            'responsibilities' => 'Deliver work.',
            'requirements' => 'Relevant experience.',
            'what_we_offer' => 'Training.',
            ...$overrides,
        ];
    }
}
