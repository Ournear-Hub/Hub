<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\CandidateNotificationEvent;
use App\Enums\FileLifecycleStatus;
use App\Models\Application;
use App\Models\CandidateResume;
use App\Models\Job;
use App\Notifications\CandidateLifecycleNotification;
use App\Services\ApplicationPipelineService;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ApplicationExperienceTest extends TestCase
{
    public function test_authenticated_candidate_can_apply_and_see_confirmation(): void
    {
        $candidate = $this->makeCandidate(['email' => 'v13c.apply@example.com']);
        $job = Job::factory()->create([
            'title' => 'Site Electrician',
            'project' => 'North Works',
            'location' => 'Manchester',
        ]);

        $this->giveCandidateResume($candidate);
        $this->actingAs($candidate)
            ->post(route('jobs.apply.store', $job))
            ->assertRedirect(route('jobs.show', $job))
            ->assertSessionHas('success')
            ->assertSessionHas('application_confirmation.business_id');

        $application = Application::query()->where('email', $candidate->email)->first();
        $this->assertNotNull($application);
        $this->assertSame($candidate->id, $application->user_id);
        $this->assertTrue(str_starts_with((string) $application->business_id, 'ONT-A-'));
        Storage::disk('resumes')->assertExists($application->getAttributes()['resume_path']);
        $this->assertSame(1, CandidateResume::query()->count());

        $this->actingAs($candidate)
            ->get(route('jobs.show', $job))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Jobs/Show')
                ->where('job.title', 'Site Electrician')
                ->where('job.project', 'North Works')
                ->where('job.location', 'Manchester')
                ->where('job.apply_state', 'applied'));
    }

    public function test_guest_apply_redirects_to_login_and_does_not_create_an_application(): void
    {
        $job = Job::factory()->create();

        $this->post(route('jobs.apply.store', $job))->assertRedirect(route('login'));

        $this->assertDatabaseMissing('applications', [
            'email' => 'v13c.guest@example.com',
        ]);
    }

    public function test_duplicate_post_does_not_create_a_second_application(): void
    {
        $candidate = $this->makeCandidate(['email' => 'v13c.repeat@example.com']);
        $job = Job::factory()->create();
        $this->applyAsCandidate($candidate, $job);

        $this->actingAs($candidate)
            ->from(route('jobs.show', $job))
            ->post(route('jobs.apply.store', $job))
            ->assertSessionHasErrors('job');

        $this->assertSame(1, Application::query()->where('email', 'v13c.repeat@example.com')->count());
        $this->assertSame(
            1,
            $candidate->notifications()
                ->where('type', CandidateLifecycleNotification::class)
                ->where('data->event', CandidateNotificationEvent::ApplicationReceived->value)
                ->count()
        );
    }

    public function test_candidate_can_apply_with_a_library_resume_without_merging_the_snapshot(): void
    {
        $candidate = $this->makeCandidate(['email' => 'v13c.library@example.com']);
        $this->actingAs($candidate)->post(route('account.resumes.store'), [
            'file' => $this->fakeResume('library.pdf'),
            'title' => 'Library CV',
        ])->assertRedirect();

        $library = CandidateResume::query()->first();
        $this->assertNotNull($library);
        $libraryPath = $library->storage_path;
        $originalBytes = Storage::disk((string) $library->storage_disk)->get($libraryPath);

        $job = Job::factory()->create();

        $this->actingAs($candidate)
            ->post(route('jobs.apply.store', $job))
            ->assertRedirect(route('jobs.show', $job));

        $application = Application::query()->where('email', $candidate->email)->first();
        $this->assertNotNull($application);
        $snapshot = $application->getAttributes()['resume_path'];
        $this->assertNotSame($libraryPath, $snapshot);
        Storage::disk('resumes')->assertExists($snapshot);
        $this->assertSame($originalBytes, Storage::disk('resumes')->get($snapshot));

        $this->actingAs($candidate)->post(route('account.resumes.replace', $library), [
            'file' => $this->fakeResume('replaced-library.pdf'),
        ])->assertRedirect();

        $this->assertSame($originalBytes, Storage::disk('resumes')->get($snapshot));
        $this->assertSame($snapshot, $application->fresh()->getAttributes()['resume_path']);

        $this->actingAs($candidate)->patch(route('account.resumes.archive', $library))->assertRedirect();
        $this->assertSame(FileLifecycleStatus::Archived, $library->fresh()->status);
        $this->assertSame($snapshot, $application->fresh()->getAttributes()['resume_path']);
        Storage::disk('resumes')->assertExists($snapshot);
    }

    public function test_candidate_sees_own_applications_list_and_detail(): void
    {
        $candidate = $this->makeCandidate();
        $job = Job::factory()->create([
            'title' => 'Plant Operator',
            'project' => 'East Works',
            'location' => 'Leeds',
        ]);
        $application = Application::factory()->create([
            'user_id' => $candidate->id,
            'email' => $candidate->email,
            'full_name' => $candidate->name,
            'job_id' => $job->id,
            'status' => ApplicationStatus::New,
        ]);

        $this->actingAs($candidate)
            ->get(route('account.applications.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Applications')
                ->where('summary.total', 1)
                ->where('summary.in_progress', 1)
                ->has('applications.data', 1)
                ->where('applications.data.0.id', $application->id)
                ->where('applications.data.0.business_id', $application->business_id)
                ->where('applications.data.0.status', 'Submitted')
                ->where('applications.data.0.job.title', 'Plant Operator')
                ->where('applications.data.0.job.company', 'East Works')
                ->where('applications.data.0.job.location', 'Leeds')
                ->missing('applications.data.0.resume_path')
                ->missing('applications.data.0.internal_notes'));

        $this->actingAs($candidate)
            ->get(route('account.applications.show', $application))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Applications/Show')
                ->where('application.business_id', $application->business_id)
                ->where('application.status', 'Submitted')
                ->where('application.job.title', 'Plant Operator')
                ->where('application.timeline.0.label', 'Application submitted')
                ->missing('application.resume_path')
                ->missing('application.internal_notes')
                ->missing('application.screening')
                ->missing('application.user_id'));
    }

    public function test_timeline_uses_real_recruitment_events_only(): void
    {
        $candidate = $this->makeCandidate();
        $admin = $this->makeAdmin();
        $application = Application::factory()->create([
            'user_id' => $candidate->id,
            'email' => $candidate->email,
            'status' => ApplicationStatus::New,
        ]);

        $pipeline = app(ApplicationPipelineService::class);
        $pipeline->changeStatus($application, ApplicationStatus::Reviewing, $admin);
        $pipeline->shortlist($application->fresh(), $admin);

        $this->actingAs($candidate)
            ->get(route('account.applications.show', $application->fresh()))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('application.status', 'Shortlisted')
                ->where('application.timeline.0.label', 'Application submitted')
                ->where('application.timeline.1.label', 'Application under review')
                ->where('application.timeline.2.label', 'Shortlisted')
                ->has('application.timeline', 3));
    }

    public function test_closed_job_still_shows_historical_application(): void
    {
        $candidate = $this->makeCandidate();
        $job = Job::factory()->create(['title' => 'Closed Historical Role']);
        $application = Application::factory()->create([
            'user_id' => $candidate->id,
            'email' => $candidate->email,
            'job_id' => $job->id,
        ]);
        $job->update(['closing_date' => now()->subDay()]);
        $this->assertTrue($job->fresh()->isClosed());

        $this->actingAs($candidate)
            ->get(route('account.applications.show', $application))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('application.job.title', 'Closed Historical Role')
                ->where('application.job.is_closed', true)
                ->where('application.job.can_view', false));
    }

    public function test_candidate_cannot_access_another_candidates_application_or_change_status(): void
    {
        $owner = $this->makeCandidate();
        $other = $this->makeCandidate();
        $application = Application::factory()->create([
            'user_id' => $owner->id,
            'email' => $owner->email,
            'status' => ApplicationStatus::New,
        ]);

        $this->actingAs($other)
            ->get(route('account.applications.show', $application))
            ->assertForbidden();
        $this->actingAs($other)
            ->get(route('account.applications.resume', $application))
            ->assertForbidden();
        $this->actingAs($other)
            ->patch(route('account.applications.withdraw', $application))
            ->assertForbidden();

        $this->actingAs($owner)
            ->patch(route('admin.applications.status', $application), [
                'status' => ApplicationStatus::Shortlisted->value,
            ])
            ->assertForbidden();

        $this->assertSame(ApplicationStatus::New, $application->fresh()->status);
    }

    public function test_guest_and_employer_cannot_open_application_history(): void
    {
        $application = Application::factory()->create();

        $this->get(route('account.applications.index'))->assertRedirect(route('login'));
        $this->get(route('account.applications.show', $application))->assertRedirect(route('login'));

        $this->actingAs($this->makeEmployer())
            ->get(route('account.applications.index'))
            ->assertForbidden();
        $this->actingAs($this->makeEmployer())
            ->get(route('account.applications.show', $application))
            ->assertForbidden();
    }

    public function test_application_filters_map_to_existing_statuses(): void
    {
        $candidate = $this->makeCandidate();
        Application::factory()->create([
            'user_id' => $candidate->id,
            'email' => $candidate->email,
            'status' => ApplicationStatus::New,
        ]);
        Application::factory()->create([
            'user_id' => $candidate->id,
            'email' => 'v13c.rejected@example.com',
            'status' => ApplicationStatus::Rejected,
        ]);

        $this->actingAs($candidate)
            ->get(route('account.applications.index', ['filter' => 'active']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('applications.data', 1)->where('applications.data.0.status', 'Submitted'));

        $this->actingAs($candidate)
            ->get(route('account.applications.index', ['filter' => 'rejected']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('applications.data', 1)->where('applications.data.0.status', 'Rejected'));
    }

    public function test_existing_notifications_continue_without_duplicates(): void
    {
        $candidate = $this->makeCandidate();
        $job = Job::factory()->create();

        $this->applyAsCandidate($candidate, $job);

        $this->assertSame(
            1,
            $candidate->notifications()
                ->where('type', CandidateLifecycleNotification::class)
                ->where('data->event', CandidateNotificationEvent::ApplicationReceived->value)
                ->count()
        );
        $this->assertSame('/account/applications', $candidate->notifications()->first()?->data['url']);
    }

    public function test_admin_applications_workspace_is_unchanged(): void
    {
        $admin = $this->makeAdmin();
        $application = Application::factory()->create(['status' => ApplicationStatus::New]);

        $this->actingAs($admin)
            ->get(route('admin.applications.index', ['application' => $application->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Applications/Index')
                ->where('selected.id', $application->id));
    }

}
