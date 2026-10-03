<?php

namespace Tests\Feature;

use App\Enums\FileLifecycleStatus;
use App\Enums\FreelancerStatus;
use App\Models\Application;
use App\Models\CandidateResume;
use App\Models\Freelancer;
use App\Models\Job;
use App\Models\SavedJob;
use App\Notifications\CandidateLifecycleNotification;
use App\Support\CandidateAccountNav;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CorrectionPassTest extends TestCase
{
    public function test_job_cards_use_relative_labels_without_posted_or_closing_prefixes(): void
    {
        $job = Job::factory()->create([
            'title' => 'Site Supervisor',
            'project' => 'North Works',
            'location' => 'Manchester',
            'employment_type' => 'Full-time',
            'experience' => '3-5 years',
            'salary' => '£32,000',
            'description' => 'Lead site teams.',
            'responsibilities' => 'Supervise daily works.',
            'requirements' => 'CSCS and experience.',
            'posted_date' => now()->subDay(),
            'closing_date' => now()->addDays(19),
        ]);

        $this->assertSame('1 day ago', $job->postedLabel());
        $this->assertSame('19 days left', $job->closingLabel());
        $this->assertStringNotContainsString('Posted', $job->postedLabel());
        $this->assertStringNotContainsString('Closing', $job->closingLabel());

        $cardSource = file_get_contents(resource_path('js/Components/Public/JobCard.tsx'));
        $this->assertIsString($cardSource);
        $this->assertStringContainsString('Posted:', $cardSource);
        $this->assertStringNotContainsString('Closing:', $cardSource);
        $this->assertStringNotContainsString('Posted on', $cardSource);

        $this->get(route('jobs.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Jobs/Index')
                ->where('selected', null)
                ->where('jobs.data.0.slug', $job->slug)
                ->where('jobs.data.0.posted_label', '1 day ago')
                ->where('jobs.data.0.closing_label', '19 days left')
                ->where('jobs.data.0.apply_state', 'guest')
                ->where('jobs.data.0.is_saved', false));

        $this->get(route('jobs.index', ['job' => $job->slug]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('selected.slug', $job->slug)
                ->where('selected.title', 'Site Supervisor')
                ->where('selected.project', 'North Works')
                ->where('selected.location', 'Manchester')
                ->where('selected.employment_type', 'Full-time')
                ->where('selected.experience', '3-5 years')
                ->where('selected.salary', '£32,000')
                ->where('selected.description', 'Lead site teams.')
                ->where('selected.responsibilities', 'Supervise daily works.')
                ->where('selected.requirements', 'CSCS and experience.')
                ->where('selected.apply_state', 'guest'));
    }

    public function test_guest_save_and_apply_redirect_to_login_without_creating_records(): void
    {
        $job = Job::factory()->create();

        $this->post(route('account.saved-jobs.store', $job))->assertRedirect(route('login'));
        $this->get(route('jobs.apply', $job))->assertRedirect(route('login'));
        $this->post(route('jobs.apply.store', $job))->assertRedirect(route('login'));

        $this->assertSame(0, SavedJob::query()->count());
        $this->assertSame(0, Application::query()->count());
    }

    public function test_candidate_save_unsave_and_apply_states_are_consistent(): void
    {
        $candidate = $this->makeCandidate();
        $job = Job::factory()->create();
        $this->giveCandidateResume($candidate);

        $this->actingAs($candidate)
            ->get(route('jobs.show', $job))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('job.apply_state', 'apply')
                ->where('job.is_saved', false)
                ->where('job.has_resume', true));

        $this->actingAs($candidate)->post(route('account.saved-jobs.store', $job))->assertRedirect();
        $this->assertDatabaseHas('saved_jobs', [
            'user_id' => $candidate->id,
            'job_id' => $job->id,
        ]);

        $this->actingAs($candidate)
            ->get(route('jobs.index', ['job' => $job->slug]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('jobs.data.0.is_saved', true)
                ->where('selected.is_saved', true)
                ->where('jobs.data.0.apply_state', 'apply')
                ->where('selected.apply_state', 'apply'));

        $this->actingAs($candidate)->delete(route('account.saved-jobs.destroy', $job))->assertRedirect();
        $this->assertSame(0, SavedJob::query()->count());

        $this->actingAs($candidate)->post(route('jobs.apply.store', $job))->assertRedirect(route('jobs.show', $job));
        $this->assertSame(1, Application::query()->where('user_id', $candidate->id)->where('job_id', $job->id)->count());

        $this->actingAs($candidate)
            ->get(route('jobs.show', $job))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('job.apply_state', 'applied')
                ->where('job.has_applied', true));

        $this->actingAs($candidate)
            ->from(route('jobs.show', $job))
            ->post(route('jobs.apply.store', $job))
            ->assertSessionHasErrors('job');
        $this->assertSame(1, Application::query()->where('job_id', $job->id)->count());
    }

    public function test_candidate_without_resume_uploads_to_library_then_applies(): void
    {
        $candidate = $this->makeCandidate();
        $job = Job::factory()->create();

        $this->actingAs($candidate)
            ->get(route('jobs.show', $job))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('job.apply_state', 'upload_resume')
                ->where('job.has_resume', false));

        $this->actingAs($candidate)
            ->from(route('jobs.show', $job))
            ->post(route('jobs.apply.store', $job))
            ->assertRedirect(route('jobs.apply', $job))
            ->assertSessionHasErrors('resume');
        $this->assertSame(0, Application::query()->count());

        $this->actingAs($candidate)
            ->get(route('jobs.apply', $job))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Jobs/Apply')
                ->where('needs_resume', true));

        $this->actingAs($candidate)
            ->post(route('jobs.apply.resume', $job), [
                'file' => $this->fakeResume('apply-library.pdf'),
                'title' => 'Apply CV',
            ])
            ->assertRedirect(route('jobs.apply', $job));

        $resume = CandidateResume::query()->first();
        $this->assertNotNull($resume);
        $this->assertTrue($resume->is_primary);
        $this->assertSame(FileLifecycleStatus::Active, $resume->status);
        $this->assertSame($candidate->fresh()->candidateProfile?->id, $resume->candidate_profile_id);
        Storage::disk('candidate_files')->assertExists($resume->storage_path);

        $this->actingAs($candidate)
            ->get(route('account.resumes.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('resumes', 1)
                ->where('resumes.0.id', $resume->id));

        $this->actingAs($candidate)
            ->get(route('jobs.show', $job))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('job.apply_state', 'apply'));

        $this->actingAs($candidate)->post(route('jobs.apply.store', $job))->assertRedirect(route('jobs.show', $job));

        $application = Application::query()->where('user_id', $candidate->id)->first();
        $this->assertNotNull($application);
        $snapshot = $application->getAttributes()['resume_path'];
        $this->assertNotSame($resume->storage_path, $snapshot);
        Storage::disk('resumes')->assertExists($snapshot);
        Storage::disk('candidate_files')->assertExists($resume->fresh()->storage_path);
    }

    public function test_closed_job_cannot_be_applied_to_and_employer_does_not_get_save_state(): void
    {
        $closed = Job::factory()->closed()->create();
        $this->assertSame('Closed', $closed->closingLabel());
        $this->get(route('jobs.show', $closed))->assertNotFound();
        $this->actingAs($this->makeCandidate())->post(route('jobs.apply.store', $closed))->assertNotFound();
        $this->assertSame(0, Application::query()->count());

        $open = Job::factory()->create();
        $this->actingAs($this->makeEmployer())
            ->get(route('jobs.show', $open))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('job.apply_state', 'hidden')
                ->where('job.is_saved', null));
    }

    public function test_profile_photo_uses_authorized_route_and_blocks_unauthorized_access(): void
    {
        $candidate = $this->makeCandidate();
        $other = $this->makeCandidate();
        $admin = $this->makeAdmin();

        $this->actingAs($candidate)
            ->post(route('account.profile.photo.update'), [
                'photo' => UploadedFile::fake()->image('avatar.jpg', 80, 80),
            ])
            ->assertRedirect();

        $candidate->refresh();
        $this->assertNotNull($candidate->photo_path);
        $this->assertStringStartsWith('users/', $candidate->photo_path);
        Storage::disk('public')->assertExists($candidate->photo_path);
        $this->assertStringContainsString('/users/'.$candidate->id.'/photo', (string) $candidate->photoUrl());

        $this->actingAs($candidate)
            ->get(route('account.profile.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('profile.photo_url', fn ($url) => is_string($url) && str_contains($url, '/users/'.$candidate->id.'/photo')));

        $originalPath = $candidate->photo_path;

        $this->actingAs($candidate)->get(route('users.photo.show', $candidate))->assertOk();
        $this->actingAs($admin)->get(route('users.photo.show', $candidate))->assertOk();
        $this->actingAs($admin)
            ->get(route('admin.users.show', $candidate))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('record.user.photo_url', fn ($url) => is_string($url) && str_contains($url, '/users/'.$candidate->id.'/photo')));

        $this->actingAs($candidate)
            ->post(route('account.profile.photo.update'), [
                'photo' => UploadedFile::fake()->image('replacement.png', 80, 80),
            ])
            ->assertRedirect();
        $replaced = $candidate->fresh()->photo_path;
        $this->assertNotSame($originalPath, $replaced);
        Storage::disk('public')->assertExists((string) $replaced);

        $this->actingAs($candidate)->delete(route('account.profile.photo.destroy'))->assertRedirect();
        $this->assertNull($candidate->fresh()->photo_path);
        $this->actingAs($candidate)
            ->get(route('account.profile.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('profile.photo_url', null));

        $this->actingAs($candidate)
            ->post(route('account.profile.photo.update'), [
                'photo' => UploadedFile::fake()->image('final.jpg', 80, 80),
            ])
            ->assertRedirect();

        $this->actingAs($other)->get(route('users.photo.show', $candidate))->assertForbidden();
        $this->actingAs($this->makeEmployer())->get(route('users.photo.show', $candidate))->assertForbidden();
        auth()->logout();
        $this->flushSession();
        $this->get(route('users.photo.show', $candidate))->assertRedirect(route('login'));
    }

    public function test_login_and_register_remain_discoverable_on_existing_routes(): void
    {
        $this->assertTrue(Route::has('login'));
        $this->assertTrue(Route::has('register'));
        $this->get(route('login'))->assertOk()->assertInertia(fn (Assert $page) => $page->component('Account/Login'));
        $this->get(route('register'))->assertOk()->assertInertia(fn (Assert $page) => $page->component('Account/Register'));
        $this->get(route('home'))->assertOk();

        $header = file_get_contents(resource_path('js/Components/Public/Header.tsx'));
        $this->assertIsString($header);
        $this->assertStringContainsString('href="/login"', $header);
        $this->assertStringContainsString('href="/register"', $header);
        $this->assertSame(2, substr_count($header, 'href="/login"'));
        $this->assertSame(2, substr_count($header, 'href="/register"'));
        $this->assertStringContainsString("MOBILE_MENU_ID = 'public-mobile-nav'", $header);
        $this->assertStringContainsString('aria-controls={MOBILE_MENU_ID}', $header);
        $this->assertStringContainsString('hidden text-ink hover:text-brand lg:inline-flex', $header);
        $this->assertStringContainsString('hidden bg-brand text-white hover:bg-brand/90 lg:inline-flex', $header);
        $this->assertStringContainsString('flex shrink-0 items-center gap-2', $header);
        $this->assertStringNotContainsString('Find Jobs', $header);
        $this->assertStringNotContainsString("Button href=\"/jobs\"", $header);

        $job = Job::factory()->create();
        $this->get(route('jobs.apply', $job))->assertRedirect(route('login'));
        $this->get(route('login'))->assertOk();
    }

    public function test_public_header_uses_existing_profile_menu_with_role_scoped_links(): void
    {
        $header = file_get_contents(resource_path('js/Components/Public/Header.tsx'));

        $this->assertIsString($header);
        $this->assertStringContainsString('user.photo_url', $header);
        $this->assertStringContainsString('initials(user)', $header);
        $this->assertStringContainsString('aria-label="Account menu"', $header);
        $this->assertStringContainsString('props.candidateNav', $header);
        $this->assertStringContainsString("href: '/account/enquiries'", $header);
        $this->assertStringContainsString("user?.role === 'admin'", $header);
        $this->assertStringContainsString("{ href: '/admin', label: 'Admin Panel' }", $header);
        $this->assertStringContainsString('href="/logout"', $header);
        $this->assertStringContainsString("window.addEventListener('mousedown', closeOutside)", $header);
    }

    public function test_freelancer_partnership_stays_public_and_admin_protected(): void
    {
        $this->get(route('freelancers'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Freelancers/Index')
                ->has('partners')
                ->has('submission_token'));

        $usersBefore = \App\Models\User::query()->count();
        $this->post(route('freelancers.store'), $this->freelancerPayload())
            ->assertRedirect(route('freelancers.received'));

        $freelancer = Freelancer::query()->where('email', 'partner.ravi@example.com')->first();
        $this->assertNotNull($freelancer);
        $this->assertTrue(str_starts_with((string) $freelancer->business_id, 'ONT-F-'));
        $this->assertSame(FreelancerStatus::New, $freelancer->status);
        $this->assertSame($usersBefore, \App\Models\User::query()->count());
        $this->assertDatabaseMissing('users', ['email' => 'partner.ravi@example.com']);
        $this->assertSame(0, \App\Models\CandidateProfile::query()->count());

        $card = $freelancer->toPublicCard();
        $this->assertSame(['name', 'business_id', 'status', 'joined_at', 'works_for'], array_keys($card));
        $this->assertSame('Pending', $card['status']);
        $this->assertArrayNotHasKey('email', $card);
        $this->assertArrayNotHasKey('mobile', $card);

        $this->get(route('admin.freelancers.index'))->assertRedirect(route('admin.login'));
        $this->actingAs($this->makeCandidate())->get(route('admin.freelancers.show', $freelancer))->assertForbidden();

        $this->actingAs($this->makeAdmin())
            ->get(route('admin.freelancers.show', $freelancer))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('freelancer.business_id', $freelancer->business_id)
                ->where('freelancer.email', 'partner.ravi@example.com')
                ->where('freelancer.status', FreelancerStatus::New->value));
    }

    public function test_candidate_nav_exposes_notifications_with_working_backend(): void
    {
        $labels = array_column(CandidateAccountNav::items(), 'label');
        $this->assertSame(array_column(CandidateAccountNav::items(), 'label'), $labels);
        $this->assertContains('Notifications', $labels);
        $this->assertContains('My Resume', $labels);
        $this->assertContains('Hire Resume', $labels);

        $candidate = $this->makeCandidate();
        $this->applyAsCandidate($candidate, Job::factory()->create());

        $this->actingAs($candidate)
            ->get(route('account.home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('candidateNav', CandidateAccountNav::items())
                ->where('candidateNav', fn ($items) => collect($items)->pluck('label')->contains('Notifications')));

        $this->assertTrue(Route::has('account.notifications.index'));
        $this->assertTrue(class_exists(CandidateLifecycleNotification::class));
        $this->assertTrue(Schema::hasTable('notifications'));

        $lifecycleCount = $candidate->fresh()->notifications()
            ->where('type', CandidateLifecycleNotification::class)
            ->count();
        $this->assertGreaterThanOrEqual(1, $lifecycleCount);

        $this->actingAs($candidate)->get(route('account.notifications.index'))->assertOk();
    }

    public function test_security_boundaries_remain_for_account_and_photos(): void
    {
        $owner = $this->makeCandidate();
        $other = $this->makeCandidate();
        $application = $this->applyAsCandidate($owner, Job::factory()->create());

        auth()->logout();
        $this->flushSession();
        $this->get(route('account.home'))->assertRedirect(route('login'));
        $this->actingAs($this->makeEmployer())->get(route('account.home'))->assertOk();
        $this->actingAs($this->makeEmployer())->get(route('account.applications.index'))->assertForbidden();
        $this->actingAs($other)->get(route('account.applications.show', $application))->assertForbidden();
        $this->actingAs($owner)->get(route('account.applications.show', $application))->assertOk();
    }

    /**
     * @return array<string, mixed>
     */
    private function freelancerPayload(): array
    {
        $location = \App\Models\Location::query()->active()->ordered()->first()
            ?? \App\Models\Location::query()->create(['name' => 'Leeds', 'is_active' => true, 'sort_order' => 90]);
        $industry = \App\Models\Industry::query()->active()->ordered()->first()
            ?? \App\Models\Industry::query()->create(['name' => 'Partnership Industry', 'is_active' => true, 'sort_order' => 90]);
        $experience = \App\Models\ExperienceRange::query()->active()->ordered()->first()
            ?? \App\Models\ExperienceRange::query()->create(['name' => '5+ years', 'is_active' => true, 'sort_order' => 90]);

        return [
            'submission_token' => str_repeat('b', 40),
            'name' => 'Ravi Partner',
            'mobile' => '07700900777',
            'email' => 'partner.ravi@example.com',
            'current_location_id' => $location->id,
            'primary_skill' => 'Recruitment',
            'industry_id' => $industry->id,
            'experience_range_id' => $experience->id,
            'consent' => '1',
            'fax' => '',
        ];
    }
}
