<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\EmployerSubmissionStatus;
use App\Enums\InterviewMode;
use App\Enums\InterviewOutcome;
use App\Enums\JobPublishingStatus;
use App\Enums\PermissionName;
use App\Enums\PlacementStatus;
use App\Enums\ScreeningDecision;
use App\Enums\ScreeningStage;
use App\Enums\SecurityActivityType;
use App\Enums\StaffRoleSlug;
use App\Models\Application;
use App\Models\ApplicationInterview;
use App\Models\ApplicationScreening;
use App\Models\EmployerSubmission;
use App\Models\EmploymentType;
use App\Models\ExperienceRange;
use App\Models\Job;
use App\Models\JobTitle;
use App\Models\Location;
use App\Models\Placement;
use App\Models\SecurityActivity;
use App\Support\AccessCatalog;
use App\Support\AtsStage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class JobsAtsFoundationStage5Test extends TestCase
{
    public function test_lifecycle_pause_hides_public_job_syncs_active_and_is_audited(): void
    {
        $admin = $this->makeAdmin();
        $job = Job::factory()->create([
            'title' => 'Public Site Engineer',
            'industry' => 'Construction',
            'location' => 'Leeds',
        ]);

        $this->get(route('jobs.show', $job))->assertOk();

        $this->actingAs($admin)
            ->patch(route('admin.jobs.lifecycle', $job), ['action' => 'pause'])
            ->assertRedirect();

        $job->refresh();
        $this->assertSame(JobPublishingStatus::Paused, $job->publishing_status);
        $this->assertFalse($job->active);
        $this->assertFalse($job->isPubliclyVisible());
        $this->get(route('jobs.show', $job))->assertNotFound();

        $this->assertTrue(
            SecurityActivity::query()
                ->where('action', SecurityActivityType::JobPaused)
                ->where('subject_id', $job->id)
                ->exists()
        );
    }

    public function test_date_closed_and_operator_closed_stay_distinct(): void
    {
        $dateClosed = Job::factory()->closed()->create();
        $this->assertTrue($dateClosed->isClosed());
        $this->assertFalse($dateClosed->isOperatorClosed());
        $this->assertSame(JobPublishingStatus::Published, $dateClosed->publishing_status);
        $this->assertFalse($dateClosed->isPubliclyVisible());

        $operatorClosed = Job::factory()->create();
        $this->actingAs($this->makeAdmin())
            ->patch(route('admin.jobs.lifecycle', $operatorClosed), ['action' => 'close'])
            ->assertRedirect();

        $operatorClosed->refresh();
        $this->assertSame(JobPublishingStatus::Closed, $operatorClosed->publishing_status);
        $this->assertTrue($operatorClosed->isOperatorClosed());
        $this->assertFalse($operatorClosed->isClosed());
        $this->assertFalse($operatorClosed->active);
        $this->assertFalse($operatorClosed->isPubliclyVisible());
    }

    public function test_employer_create_still_publishes_but_update_does_not_republish_paused_job(): void
    {
        $employer = $this->eligibleEmployer();
        $this->actingAs($employer)
            ->post(route('account.employer.jobs.store'), $this->jobPayload([
                'title' => 'Employer Foundation Role',
                'listing_priority' => 'urgent',
                'joining_date' => now()->addMonth()->toDateString(),
                'site' => 'North Yard',
                'accommodation' => 'Shared hostel',
            ]))
            ->assertRedirect(route('account.employer.jobs.index'));

        $job = Job::query()->where('title', 'Employer Foundation Role')->first();
        $this->assertNotNull($job);
        $this->assertSame(JobPublishingStatus::Published, $job->publishing_status);
        $this->assertTrue($job->active);
        $this->assertTrue($job->urgent);
        $this->assertSame('North Yard', $job->site);
        $this->assertFalse($job->featured);

        $this->actingAs($employer)
            ->patch(route('account.employer.jobs.lifecycle', $job), ['action' => 'pause'])
            ->assertRedirect();
        $this->assertSame(JobPublishingStatus::Paused, $job->fresh()->publishing_status);
        $this->assertFalse($job->fresh()->active);

        $this->actingAs($employer)
            ->put(route('account.employer.jobs.update', $job), $this->jobPayload([
                'title' => 'Still Paused Role',
                'publishing_status' => 'published',
                'active' => true,
            ]))
            ->assertRedirect(route('account.employer.jobs.index'));

        $job->refresh();
        $this->assertSame('Still Paused Role', $job->title);
        $this->assertSame(JobPublishingStatus::Paused, $job->publishing_status);
        $this->assertFalse($job->active);
    }

    public function test_employer_cannot_archive_or_use_new_permission_names(): void
    {
        $employer = $this->eligibleEmployer();
        $job = Job::factory()->create(['employer_id' => $employer->employerProfile->id]);

        $this->actingAs($employer)
            ->patch(route('account.employer.jobs.lifecycle', $job), ['action' => 'archive'])
            ->assertSessionHasErrors('action');

        $this->assertSame(34, count(AccessCatalog::permissionNames()));
        $this->assertNotContains('jobs.publish', AccessCatalog::permissionNames());
        $this->assertNotContains('jobs.pause', AccessCatalog::permissionNames());
        $this->assertContains(PermissionName::JobsManage->value, AccessCatalog::permissionNames());
    }

    public function test_similar_jobs_only_include_public_listings(): void
    {
        $visible = Job::factory()->create([
            'title' => 'Visible Twin',
            'industry' => 'Construction',
            'location' => 'Leeds',
            'category' => 'Civil',
        ]);
        $paused = Job::factory()->create([
            'title' => 'Paused Twin',
            'industry' => 'Construction',
            'location' => 'Leeds',
            'category' => 'Civil',
            'publishing_status' => JobPublishingStatus::Paused,
            'active' => false,
        ]);
        $current = Job::factory()->create([
            'title' => 'Current Listing',
            'industry' => 'Construction',
            'location' => 'Leeds',
            'category' => 'Civil',
        ]);

        $this->get(route('jobs.show', $current))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Jobs/Show')
                ->has('similar_jobs', 1)
                ->where('similar_jobs.0.slug', $visible->slug)
                ->where('job.urgent', false)
                ->where('similar_jobs.0.slug', fn ($slug) => $slug !== $paused->slug));
    }

    public function test_share_ui_is_untracked_and_job_page_exposes_new_fields(): void
    {
        $job = Job::factory()->create([
            'urgent' => true,
            'joining_date' => now()->addWeeks(3)->toDateString(),
            'site' => 'Dockside',
            'accommodation' => 'Site hostel',
        ]);

        $this->get(route('jobs.show', $job))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('job.urgent', true)
                ->where('job.site', 'Dockside')
                ->where('job.accommodation', 'Site hostel')
                ->where('job.joining_date', $job->joining_date?->toDateString()));

        $jobActions = file_get_contents(resource_path('js/Components/Public/JobActions.tsx'));
        $shareMenu = file_get_contents(resource_path('js/Components/Public/PublicShareMenu.tsx'));
        $this->assertIsString($jobActions);
        $this->assertIsString($shareMenu);
        $this->assertStringContainsString('PublicShareMenu', $jobActions);
        $this->assertStringContainsString('Copy link', $shareMenu);
        $this->assertStringNotContainsString('WhatsApp', $jobActions);
        $this->assertTrue(Schema::hasTable('share_events'));
        $this->assertTrue(Schema::hasTable('referral_links'));
        $this->assertStringContainsString('/jobs/${jobSlug}/share', $shareMenu);
        $this->assertFalse(Schema::hasTable('visibility_policies'));
        $this->assertFalse(Schema::hasColumn('applications', 'ats_stage'));
        $this->assertTrue(Schema::hasColumn('job_listings', 'urgent'));
        $this->assertTrue(Schema::hasColumn('job_listings', 'joining_date'));
        $this->assertTrue(Schema::hasColumn('job_listings', 'site'));
        $this->assertTrue(Schema::hasColumn('job_listings', 'accommodation'));
    }

    public function test_ats_stage_is_computed_from_existing_layers(): void
    {
        $employer = $this->makeEmployer();
        $profile = $employer->employerProfile;
        $this->assertNotNull($profile);
        $job = Job::factory()->create(['employer_id' => $profile->id]);

        $applied = Application::factory()->create(['job_id' => $job->id, 'status' => ApplicationStatus::New]);
        $this->assertSame(AtsStage::Applied, AtsStage::resolve($applied));

        $screening = Application::factory()->create(['job_id' => $job->id, 'status' => ApplicationStatus::Reviewing]);
        ApplicationScreening::query()->create([
            'application_id' => $screening->id,
            'decision' => ScreeningDecision::Pending,
            'stage' => ScreeningStage::Contacted,
        ]);
        $this->assertSame(AtsStage::Screening, AtsStage::resolve($screening->fresh(['screening'])));

        $shortlisted = Application::factory()->create(['job_id' => $job->id, 'status' => ApplicationStatus::Shortlisted]);
        $this->assertSame(AtsStage::Shortlisted, AtsStage::resolve($shortlisted));

        $review = Application::factory()->create(['job_id' => $job->id, 'status' => ApplicationStatus::Shortlisted]);
        $admin = $this->makeAdmin();
        EmployerSubmission::query()->create([
            'employer_profile_id' => $profile->id,
            'job_id' => $job->id,
            'application_id' => $review->id,
            'status' => EmployerSubmissionStatus::UnderReview,
            'submitted_by' => $admin->id,
            'submitted_at' => now(),
        ]);
        $this->assertSame(AtsStage::EmployerReview, AtsStage::resolve($review->fresh(['submissions'])));

        $interview = Application::factory()->create(['job_id' => $job->id, 'status' => ApplicationStatus::Interview]);
        ApplicationInterview::query()->create([
            'application_id' => $interview->id,
            'scheduled_at' => now()->addDay(),
            'mode' => InterviewMode::Video,
            'interviewer_name' => 'Recruiter',
            'outcome' => InterviewOutcome::Pending,
            'created_by' => $admin->id,
        ]);
        $this->assertSame(AtsStage::Interview, AtsStage::resolve($interview->fresh(['interview'])));

        $selected = Application::factory()->create(['job_id' => $job->id, 'status' => ApplicationStatus::Selected]);
        $this->assertSame(AtsStage::Selected, AtsStage::resolve($selected));

        $offer = Application::factory()->create(['job_id' => $job->id, 'status' => ApplicationStatus::Selected]);
        $offerSubmission = EmployerSubmission::query()->create([
            'employer_profile_id' => $profile->id,
            'job_id' => $job->id,
            'application_id' => $offer->id,
            'status' => EmployerSubmissionStatus::Selected,
            'submitted_by' => $admin->id,
            'submitted_at' => now(),
        ]);
        Placement::query()->create([
            'employer_submission_id' => $offerSubmission->id,
            'employer_profile_id' => $profile->id,
            'job_id' => $job->id,
            'application_id' => $offer->id,
            'status' => PlacementStatus::Offered,
            'created_by' => $admin->id,
        ]);
        $this->assertSame(AtsStage::Offer, AtsStage::resolve($offer->fresh(['placements', 'submissions'])));

        $joined = Application::factory()->create(['job_id' => $job->id, 'status' => ApplicationStatus::Placed]);
        $this->assertSame(AtsStage::Joined, AtsStage::resolve($joined));

        $held = Application::factory()->create(['job_id' => $job->id, 'status' => ApplicationStatus::Reviewing]);
        ApplicationScreening::query()->create([
            'application_id' => $held->id,
            'decision' => ScreeningDecision::Pending,
            'stage' => ScreeningStage::Held,
        ]);
        $this->assertSame(AtsStage::Closed, AtsStage::resolve($held->fresh(['screening'])));

        $admin = $this->makeAdmin();
        $this->actingAs($admin)
            ->get(route('admin.applications.index', ['ats_stage' => AtsStage::Joined]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Applications/Index')
                ->where('applications.data.0.id', $joined->id)
                ->where('applications.data.0.ats_stage', AtsStage::Joined));
    }

    public function test_employer_sees_own_submitted_candidates_only_and_never_the_talent_pool(): void
    {
        $owner = $this->eligibleEmployer();
        $other = $this->eligibleEmployer();
        $admin = $this->makeAdmin();
        $candidate = $this->makeCandidate([
            'name' => 'Submitted Person',
            'username' => 'submitted-person',
            'email' => 'submitted@example.com',
        ]);
        $candidate->candidateProfile?->update([
            'current_designation' => 'Supervisor',
            'public_share' => ['enabled' => false],
        ]);

        $job = Job::factory()->create(['employer_id' => $owner->employerProfile->id, 'title' => 'Owned Role']);
        $application = Application::factory()->create([
            'job_id' => $job->id,
            'user_id' => $candidate->id,
            'full_name' => 'Submitted Person',
            'email' => 'submitted@example.com',
            'mobile' => '07000001111',
            'status' => ApplicationStatus::Shortlisted,
            'internal_notes' => [['body' => 'Secret recruiter note', 'author_id' => $admin->id, 'author_name' => 'Admin', 'created_at' => now()->toDateTimeString()]],
            'resume_path' => 'applications/submitted.pdf',
        ]);
        Storage::disk('resumes')->put('applications/submitted.pdf', '%PDF-1.4');

        $this->actingAs($admin)->post(route('admin.applications.submit', $application))->assertRedirect();

        $this->actingAs($owner)
            ->get(route('account.employer.submissions.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Employer/Submissions/Index')
                ->has('submissions.data', 1)
                ->where('submissions.data.0.name', 'Submitted Person')
                ->where('submissions.data.0.designation', 'Supervisor')
                ->where('submissions.data.0.username', null)
                ->where('submissions.data.0.public_profile_url', null)
                ->where('submissions.data.0.status', EmployerSubmissionStatus::Submitted->value)
                ->missing('submissions.data.0.email')
                ->missing('submissions.data.0.mobile')
                ->missing('submissions.data.0.internal_notes')
                ->missing('submissions.data.0.notes'));

        $this->actingAs($other)
            ->get(route('account.employer.submissions.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Employer/Submissions/Index')
                ->has('submissions.data', 0));

        $this->actingAs($other)
            ->get(route('account.employer.applications.resume', $application))
            ->assertForbidden();

        $this->actingAs($owner)
            ->get(route('account.employer.applications.resume', $application))
            ->assertOk();

        $this->actingAs($owner)->get(route('admin.candidates.index'))->assertForbidden();
        $this->actingAs($other)->get(route('account.employer.jobs.applicants', $job))->assertForbidden();
    }

    public function test_recruiter_can_filter_jobs_by_new_lifecycle_without_new_permissions(): void
    {
        $recruiter = $this->makeStaff(StaffRoleSlug::Recruiter->value);
        $this->assertTrue($recruiter->hasPermission(PermissionName::JobsManage->value));
        $this->assertFalse($recruiter->hasPermission('jobs.pause'));

        $paused = Job::factory()->create([
            'title' => 'Paused Recruiter Job',
            'publishing_status' => JobPublishingStatus::Paused,
            'active' => false,
        ]);

        $this->actingAs($recruiter)
            ->get(route('admin.jobs.index', ['status' => 'paused']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('jobs.data', 1)
                ->where('jobs.data.0.id', $paused->id)
                ->where('jobs.data.0.status_label', 'Paused'));
    }

    public function test_application_status_catalog_is_unchanged(): void
    {
        $this->assertSame([
            'New',
            'Reviewing',
            'Shortlisted',
            'Interview',
            'Selected',
            'Placed',
            'Rejected',
            'Withdrawn',
        ], array_map(fn (ApplicationStatus $status) => $status->value, ApplicationStatus::cases()));
        $this->assertCount(5, JobPublishingStatus::cases());
    }

    private function eligibleEmployer()
    {
        return $this->makeEmployer([
            'email_verified_at' => now(),
            'mobile_verified_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function jobPayload(array $overrides = []): array
    {
        $title = JobTitle::query()->with('category.industry')->where('is_active', true)->firstOrFail();
        $location = Location::query()->where('is_active', true)->first()
            ?? Location::query()->create(['name' => 'Stage Five City', 'is_active' => true, 'sort_order' => 99]);
        $employment = EmploymentType::query()->where('is_active', true)->first()
            ?? EmploymentType::query()->create(['name' => 'Full-time', 'is_active' => true, 'sort_order' => 1]);
        $experience = ExperienceRange::query()->where('is_active', true)->first()
            ?? ExperienceRange::query()->create(['name' => '1-3 years', 'is_active' => true, 'sort_order' => 1]);

        return [
            'title' => 'Stage Five Role',
            'project' => 'Stage Five Co',
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
            'listing_priority' => 'standard',
            'active' => true,
            'publishing_status' => 'published',
            'description' => 'Stage five job description.',
            'responsibilities' => 'Deliver work.',
            'requirements' => 'Relevant experience.',
            'what_we_offer' => 'Training.',
            ...$overrides,
        ];
    }
}
