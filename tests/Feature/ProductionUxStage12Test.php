<?php

namespace Tests\Feature;

use App\Enums\BusinessIdPrefix;
use App\Enums\DocumentType;
use App\Models\Application;
use App\Models\Job;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProductionUxStage12Test extends TestCase
{
    public function test_existing_business_id_prefixes_remain_single_letter(): void
    {
        $candidate = $this->makeCandidate();
        $employer = $this->makeEmployer();

        $this->assertSame('ONT-U', BusinessIdPrefix::User->value);
        $this->assertSame('ONT-C', BusinessIdPrefix::Candidate->value);
        $this->assertSame('ONT-E', BusinessIdPrefix::Employer->value);
        $this->assertMatchesRegularExpression('/^ONT-U-\d{6}$/', (string) $candidate->business_id);
        $this->assertMatchesRegularExpression('/^ONT-C-\d{6}$/', (string) $candidate->candidateProfile?->business_id);
        $this->assertMatchesRegularExpression('/^ONT-E-\d{6}$/', (string) $employer->employerProfile?->business_id);
        $this->assertNotSame($candidate->business_id, $candidate->candidateProfile?->business_id);
    }

    public function test_candidate_dashboard_and_profile_present_existing_ourneer_id_and_username(): void
    {
        $candidate = $this->makeCandidate([
            'name' => 'Stage Twelve Candidate',
            'username' => 'stage-twelve-candidate',
        ]);

        $this->actingAs($candidate)
            ->get(route('account.home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Home')
                ->where('auth.user.business_id', $candidate->business_id)
                ->where('auth.user.username', 'stage-twelve-candidate')
                ->where('dashboard.header.business_id', $candidate->business_id)
                ->where('dashboard.header.username', 'stage-twelve-candidate')
                ->has('profile.completion_items'));

        $this->actingAs($candidate)
            ->get(route('account.profile.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Profile')
                ->where('profile.business_id', $candidate->business_id)
                ->where('profile.username', 'stage-twelve-candidate')
                ->has('profile.completion_items'));
    }

    public function test_public_candidate_profile_shows_user_id_and_keeps_private_fields_hidden(): void
    {
        $candidate = $this->makeCandidate([
            'name' => 'Public Stage Candidate',
            'username' => 'public-stage-candidate',
            'bio' => 'Shareable bio',
        ]);
        $candidate->candidateProfile?->update([
            'current_designation' => 'Site Engineer',
            'current_company' => 'Harbor',
        ]);

        $this->actingAs($candidate)
            ->put(route('account.profile.privacy.update'), [
                'enabled' => true,
                'bio' => true,
                'current_role' => true,
            ])
            ->assertRedirect();

        $this->get(route('profiles.show', ['user' => $candidate->username]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Profiles/Show')
                ->where('profile.name', 'Public Stage Candidate')
                ->where('profile.username', 'public-stage-candidate')
                ->missing('profile.business_id')
                ->where('profile.designation', 'Site Engineer')
                ->missing('profile.email')
                ->missing('profile.mobile')
                ->missing('profile.resumes')
                ->missing('profile.documents'));
    }

    public function test_public_company_still_presents_existing_company_id(): void
    {
        $employer = $this->makeEmployer([
            'email_verified_at' => now(),
            'mobile_verified_at' => now(),
        ]);
        $profile = $employer->employerProfile;
        $this->assertNotNull($profile);
        $profile->update(['company_name' => 'Stage Twelve Co']);

        $this->get(route('companies.show', $profile))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Companies/Show')
                ->where('company.company_name', 'Stage Twelve Co')
                ->where('company.business_id', $profile->business_id));
    }

    public function test_resume_builder_receives_existing_completion_checklist(): void
    {
        $candidate = $this->makeCandidate();

        $this->actingAs($candidate)
            ->get(route('account.resumes.builder'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Resumes/Builder')
                ->has('preview')
                ->has('completion_items')
                ->where('profile_completion', (int) $candidate->fresh()->profile_completion));
    }

    public function test_document_center_exposes_existing_document_identity(): void
    {
        $candidate = $this->makeCandidate();

        $this->actingAs($candidate)
            ->post(route('account.documents.store'), [
                'file' => $this->fakeDocument('passport.pdf'),
                'document_type' => DocumentType::Certificate->value,
                'title' => 'Safety certificate',
            ])
            ->assertRedirect();

        $this->actingAs($candidate)
            ->get(route('account.documents.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Documents/Index')
                ->has('documents', 1)
                ->where('documents.0.title', 'Safety certificate')
                ->where('documents.0.business_id', fn ($id) => is_string($id) && preg_match('/^ONT-D-\d{6}$/', $id) === 1));

        $this->actingAs($candidate)
            ->get(route('account.resumes.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Account/Resumes/Index'));
    }

    public function test_job_listing_cards_include_existing_joining_site_and_accommodation(): void
    {
        $job = Job::factory()->create([
            'title' => 'Stage Twelve Joiner',
            'joining_date' => now()->addWeeks(2)->toDateString(),
            'site' => 'North Yard',
            'accommodation' => 'Shared hostel',
        ]);

        $this->get(route('jobs.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Jobs/Index')
                ->where('jobs.data.0.joining_date', $job->joining_date?->toDateString())
                ->where('jobs.data.0.site', 'North Yard')
                ->where('jobs.data.0.accommodation', 'Shared hostel'));

        $this->get(route('jobs.show', $job))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('job.joining_date', $job->joining_date?->toDateString()));
    }

    public function test_employer_dashboard_stays_ownership_scoped_and_admin_can_open_screening(): void
    {
        $owner = $this->makeEmployer([
            'email_verified_at' => now(),
            'mobile_verified_at' => now(),
        ]);
        $other = $this->makeEmployer([
            'email_verified_at' => now(),
            'mobile_verified_at' => now(),
        ]);
        $candidate = $this->makeCandidate();
        $owned = Job::factory()->create(['employer_id' => $owner->employerProfile?->id]);
        $foreign = Job::factory()->create(['employer_id' => $other->employerProfile?->id]);
        Application::factory()->create([
            'job_id' => $owned->id,
            'user_id' => $candidate->id,
        ]);
        Application::factory()->create([
            'job_id' => $foreign->id,
        ]);

        $this->actingAs($owner)
            ->get(route('account.home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Home')
                ->where('dashboard', null));

        $this->actingAs($owner)
            ->get(route('account.hiring'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Hiring/Dashboard')
                ->where('workspace.jobs_count', 1)
                ->where('workspace.applicants_count', 1)
                ->where('workspace.business_id', $owner->employerProfile?->business_id)
                ->where('workspace.user_business_id', $owner->business_id));

        $this->actingAs($other)
            ->get(route('account.employer.jobs.edit', $owned))
            ->assertForbidden();

        $this->actingAs($other)
            ->get(route('account.employer.jobs.applicants', $owned))
            ->assertForbidden();

        $this->actingAs($other)
            ->get(route('admin.screening.index'))
            ->assertForbidden();

        $admin = $this->makeAdmin();
        $this->actingAs($admin)
            ->get(route('admin.screening.index', ['job_id' => $owned->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Screening/Index')
                ->has('applications.data'));
    }
}
