<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\DocumentType;
use App\Enums\DocumentVerificationStatus;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\CandidateDocument;
use App\Models\CandidateResume;
use App\Models\Job;
use App\Models\SavedJob;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AccountCandidateDashboardTest extends TestCase
{
    public function test_guest_account_redirects_to_login_not_admin_login(): void
    {
        $this->get(route('account.home'))
            ->assertRedirect(route('login'));

        $this->get('/account')
            ->assertRedirect(route('login'));
    }

    public function test_candidate_can_access_account_dashboard(): void
    {
        $candidate = $this->makeCandidate([
            'name' => 'Priya Dashboard',
            'username' => 'priya-dash',
            'location' => 'Hyderabad',
        ]);
        $candidate->candidateProfile?->forceFill([
            'current_designation' => 'Site Engineer',
        ])->save();

        $this->actingAs($candidate)
            ->get(route('account.home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Home')
                ->where('role', 'candidate')
                ->where('profile.username', 'priya-dash')
                ->has('profile.completion_items')
                ->where('dashboard.header.name', 'Priya Dashboard')
                ->where('dashboard.header.username', 'priya-dash')
                ->where('dashboard.header.designation', 'Site Engineer')
                ->where('dashboard.header.location', 'Hyderabad')
                ->where('dashboard.header.profile_completion', $candidate->fresh()->profile_completion)
                ->where('dashboard.resume.count', 0)
                ->where('dashboard.resume.primary', null)
                ->where('dashboard.documents.count', 0)
                ->where('dashboard.saved_jobs.count', 0)
                ->where('dashboard.applications.count', 0));
    }

    public function test_admin_cannot_use_candidate_login_or_dashboard(): void
    {
        $admin = $this->makeAdmin([
            'email' => 'v12e.admin@example.com',
            'password' => 'password',
        ]);

        $this->from(route('login'))
            ->post(route('login.store'), [
                'email' => $admin->email,
                'password' => 'password',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->actingAs($admin)
            ->get(route('account.home'))
            ->assertRedirect(route('login'));

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Dashboard'));
    }

    public function test_employer_receives_only_employer_dashboard(): void
    {
        $employer = $this->makeEmployer(['username' => 'employer-dash']);

        $this->actingAs($employer)
            ->get(route('account.home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Home')
                ->where('role', 'employer')
                ->where('profile.username', 'employer-dash')
                ->has('profile.completion_items')
                ->where('dashboard.company_name', 'Unnamed employer')
                ->where('dashboard.jobs_count', 0)
                ->missing('dashboard.resume')
                ->missing('dashboard.saved_jobs'));

        $this->actingAs($employer)->get(route('account.resumes.index'))->assertForbidden();
        $this->actingAs($employer)->get(route('account.saved-jobs.index'))->assertForbidden();
        $this->actingAs($employer)->get(route('account.applications.index'))->assertForbidden();
    }

    public function test_empty_dashboard_summaries_for_new_candidate(): void
    {
        $candidate = $this->makeCandidate();

        $this->actingAs($candidate)
            ->get(route('account.home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Home')
                ->where('dashboard.resume.primary', null)
                ->where('dashboard.resume.count', 0)
                ->where('dashboard.documents.count', 0)
                ->where('dashboard.documents.verified_count', 0)
                ->where('dashboard.saved_jobs.count', 0)
                ->where('dashboard.saved_jobs.recent', [])
                ->where('dashboard.applications.count', 0)
                ->where('dashboard.applications.recent', []));
    }

    public function test_user_without_candidate_profile_can_open_dashboard(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Candidate,
            'name' => 'No Profile Yet',
            'username' => 'no-profile-yet',
        ]);

        $this->assertNull($user->candidateProfile);

        $this->actingAs($user)
            ->get(route('account.home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Home')
                ->where('role', 'candidate')
                ->where('dashboard.header.name', 'No Profile Yet')
                ->where('dashboard.header.designation', null)
                ->where('dashboard.resume.count', 0)
                ->where('dashboard.documents.count', 0));
    }

    public function test_dashboard_shows_own_summaries_and_recent_items(): void
    {
        $candidate = $this->makeCandidate([
            'name' => 'Asha Owner',
            'username' => 'asha-owner',
            'location' => 'Manchester',
        ]);
        $profile = $candidate->candidateProfile;
        $this->assertNotNull($profile);
        $profile->forceFill(['current_designation' => 'Civil Engineer'])->save();

        $primary = CandidateResume::factory()->create([
            'candidate_profile_id' => $profile->id,
            'title' => 'Primary Civil CV',
            'is_primary' => true,
            'primary_owner_id' => $profile->id,
        ]);
        CandidateResume::factory()->create([
            'candidate_profile_id' => $profile->id,
            'title' => 'Secondary CV',
            'is_primary' => false,
        ]);

        CandidateDocument::factory()->create([
            'candidate_profile_id' => $profile->id,
            'document_type' => DocumentType::Certificate,
            'verification_status' => DocumentVerificationStatus::Verified,
        ]);
        CandidateDocument::factory()->create([
            'candidate_profile_id' => $profile->id,
            'document_type' => DocumentType::Licence,
            'verification_status' => DocumentVerificationStatus::Unverified,
        ]);

        $olderJob = Job::factory()->create([
            'title' => 'Older Saved Role',
            'project' => 'North Works',
        ]);
        $newerJob = Job::factory()->create([
            'title' => 'Newer Saved Role',
            'project' => 'South Works',
        ]);
        $closedJob = Job::factory()->create([
            'title' => 'Closed Saved Role',
            'project' => 'West Works',
        ]);
        $fourthJob = Job::factory()->create(['title' => 'Fourth Saved Role']);

        $this->saveAt($candidate, $olderJob, now()->subMinutes(4));
        $this->saveAt($candidate, $closedJob, now()->subMinutes(3));
        $this->saveAt($candidate, $fourthJob, now()->subMinutes(2));
        $this->saveAt($candidate, $newerJob, now()->subMinute());

        $closedJob->update(['closing_date' => now()->subDay()]);
        $this->assertTrue($closedJob->fresh()->isClosed());

        $olderApplication = Application::factory()->create([
            'user_id' => $candidate->id,
            'email' => $candidate->email,
            'full_name' => $candidate->name,
            'status' => ApplicationStatus::New,
            'job_id' => Job::factory()->create(['title' => 'Older Applied Role'])->id,
        ]);
        $olderApplication->forceFill([
            'created_at' => now()->subMinutes(6),
            'updated_at' => now()->subMinutes(6),
        ])->save();

        foreach (range(1, 4) as $index) {
            $application = Application::factory()->create([
                'user_id' => $candidate->id,
                'email' => $candidate->email,
                'full_name' => $candidate->name,
                'status' => ApplicationStatus::Shortlisted,
                'job_id' => Job::factory()->create(['title' => "Applied Role {$index}"])->id,
            ]);
            $application->forceFill([
                'created_at' => now()->subMinutes(5 - $index),
                'updated_at' => now()->subMinutes(5 - $index),
            ])->save();
        }

        $newestApplication = Application::factory()->create([
            'user_id' => $candidate->id,
            'email' => $candidate->email,
            'full_name' => $candidate->name,
            'status' => ApplicationStatus::New,
            'job_id' => Job::factory()->create(['title' => 'Newest Applied Role'])->id,
        ]);

        $this->actingAs($candidate)
            ->get(route('account.home', [
                'user_id' => 999999,
                'candidate_id' => 999999,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Home')
                ->where('dashboard.header.name', 'Asha Owner')
                ->where('dashboard.header.designation', 'Civil Engineer')
                ->where('dashboard.header.location', 'Manchester')
                ->where('dashboard.header.profile_completion', $candidate->fresh()->profile_completion)
                ->where('dashboard.resume.count', 2)
                ->where('dashboard.resume.primary.title', 'Primary Civil CV')
                ->where('dashboard.resume.primary.is_primary', true)
                ->where('dashboard.resume.primary.status_label', 'Active')
                ->missing('dashboard.resume.primary.id')
                ->missing('dashboard.resume.primary.storage_path')
                ->missing('dashboard.resume.primary.download_url')
                ->missing('dashboard.resume.primary.view_url')
                ->where('dashboard.documents.count', 2)
                ->where('dashboard.documents.verified_count', 1)
                ->where('dashboard.documents.categories', function ($categories): bool {
                    $byLabel = collect($categories)->keyBy('label');

                    return $byLabel->has('Certificate')
                        && $byLabel->has('Licence')
                        && (int) $byLabel['Certificate']['count'] === 1
                        && (int) $byLabel['Licence']['count'] === 1;
                })
                ->where('dashboard.saved_jobs.count', 4)
                ->has('dashboard.saved_jobs.recent', 3)
                ->where('dashboard.saved_jobs.recent.0.title', 'Newer Saved Role')
                ->where('dashboard.saved_jobs.recent.0.project', 'South Works')
                ->where('dashboard.saved_jobs.recent.0.status', 'open')
                ->missing('dashboard.saved_jobs.recent.0.user_id')
                ->missing('dashboard.saved_jobs.recent.0.job_id')
                ->where('dashboard.applications.count', 6)
                ->has('dashboard.applications.recent', 5)
                ->where('dashboard.applications.recent.0.title', 'Newest Applied Role')
                ->where('dashboard.applications.recent.0.status', 'Received')
                ->where('dashboard.applications.recent.0.slug', $newestApplication->fresh()->job?->slug)
                ->missing('dashboard.applications.recent.0.id')
                ->missing('dashboard.applications.recent.0.resume_url')
                ->missing('dashboard.applications.recent.0.resume_path'));

        $this->assertSame('Primary Civil CV', $primary->fresh()->title);
        $this->assertTrue($closedJob->fresh()->isClosed());
    }

    public function test_candidate_cannot_see_another_candidates_dashboard_data(): void
    {
        $owner = $this->makeCandidate(['name' => 'Owner Candidate', 'username' => 'owner-dash']);
        $other = $this->makeCandidate(['name' => 'Other Candidate', 'username' => 'other-dash']);
        $this->assertNotNull($owner->candidateProfile);

        CandidateResume::factory()->create([
            'candidate_profile_id' => $owner->candidateProfile->id,
            'title' => 'Owner Secret CV',
            'is_primary' => true,
            'primary_owner_id' => $owner->candidateProfile->id,
        ]);
        CandidateDocument::factory()->create([
            'candidate_profile_id' => $owner->candidateProfile->id,
            'document_type' => DocumentType::Payslip,
        ]);
        $job = Job::factory()->create(['title' => 'Owner Saved Role']);
        SavedJob::query()->create([
            'user_id' => $owner->id,
            'job_id' => $job->id,
        ]);
        Application::factory()->create([
            'user_id' => $owner->id,
            'email' => $owner->email,
            'full_name' => $owner->name,
            'job_id' => Job::factory()->create(['title' => 'Owner Applied Role'])->id,
        ]);

        $this->actingAs($other)
            ->get(route('account.home', [
                'user_id' => $owner->id,
                'id' => $owner->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Home')
                ->where('dashboard.header.name', 'Other Candidate')
                ->where('dashboard.header.username', 'other-dash')
                ->where('dashboard.resume.count', 0)
                ->where('dashboard.resume.primary', null)
                ->where('dashboard.documents.count', 0)
                ->where('dashboard.saved_jobs.count', 0)
                ->where('dashboard.applications.count', 0)
                ->where('dashboard.saved_jobs.recent', [])
                ->where('dashboard.applications.recent', []));
    }

    public function test_candidate_dashboard_renders_identity_and_clean_designation_empty_state(): void
    {
        $source = file_get_contents(resource_path('js/Pages/Account/Home.tsx'));

        $this->assertIsString($source);
        $this->assertStringContainsString('header.photo_url', $source);
        $this->assertStringContainsString('header.name', $source);
        $this->assertStringContainsString('@{header.username}', $source);
        $this->assertStringContainsString('header.designation', $source);
        $this->assertStringContainsString('Designation not added', $source);
        $this->assertStringContainsString('sm:flex-row', $source);
    }

    public function test_candidate_account_pages_remain_available_from_dashboard(): void
    {
        $candidate = $this->makeCandidate();
        $job = Job::factory()->create();
        $this->actingAs($candidate)->post(route('account.saved-jobs.store', $job))->assertRedirect();
        $this->actingAs($candidate)->post(route('account.resumes.store'), [
            'file' => $this->fakeResume('dash.pdf'),
            'title' => 'Dashboard CV',
        ])->assertRedirect();
        $this->actingAs($candidate)->post(route('account.documents.store'), [
            'file' => $this->fakeDocument('dash-doc.pdf'),
            'document_type' => DocumentType::Other->value,
        ])->assertRedirect();
        Application::factory()->create([
            'user_id' => $candidate->id,
            'email' => $candidate->email,
            'full_name' => $candidate->name,
        ]);

        $this->actingAs($candidate)->get(route('account.profile.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Account/Profile'));
        $this->actingAs($candidate)->get(route('account.resumes.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Account/Resumes/Index'));
        $this->actingAs($candidate)->get(route('account.documents.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Account/Documents/Index'));
        $this->actingAs($candidate)->get(route('account.saved-jobs.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Account/SavedJobs'));
        $this->actingAs($candidate)->get(route('account.applications.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Account/Applications'));
        $this->actingAs($candidate)->get(route('jobs.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Jobs/Index'));
    }

    public function test_register_still_creates_candidate_profile_and_lands_on_account(): void
    {
        $this->post(route('register.store'), [
            'name' => 'V12E New Candidate',
            'username' => 'v12e-new',
            'email' => 'v12e.new@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'role' => UserRole::Candidate->value,
        ])->assertRedirect(route('account.home'));

        $user = User::query()->where('email', 'v12e.new@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->isCandidate());
        $this->assertNotNull($user->candidateProfile);

        $this->actingAs($user)
            ->get(route('account.home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Home')
                ->where('dashboard.header.name', 'V12E New Candidate')
                ->where('dashboard.resume.primary', null)
                ->where('dashboard.applications.count', 0));
    }

    private function saveAt(User $candidate, Job $job, \DateTimeInterface $when): void
    {
        $saved = SavedJob::query()->create([
            'user_id' => $candidate->id,
            'job_id' => $job->id,
        ]);
        $saved->forceFill([
            'created_at' => $when,
            'updated_at' => $when,
        ])->save();
    }
}
