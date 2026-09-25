<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\EmploymentType;
use App\Models\ExperienceRange;
use App\Models\Job;
use App\Models\JobTitle;
use App\Models\Location;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EmployerAccountWorkspaceTest extends TestCase
{
    public function test_employer_dashboard_and_job_management_are_available(): void
    {
        $employer = $this->eligibleEmployer();
        $employer->employerProfile->update(['company_name' => 'BuildCo']);
        Job::factory()->create(['employer_id' => $employer->employerProfile->id]);

        $this->actingAs($employer)->get(route('account.home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Home')
                ->where('role', 'employer')
                ->where('dashboard.company_name', 'BuildCo')
                ->where('dashboard.jobs_count', 1)
                ->where('dashboard.eligibility.eligible', true));

        $this->actingAs($employer)->get(route('account.employer.jobs.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Employer/Jobs/Index')
                ->has('jobs.data', 1)
                ->where('featured.available', false));
    }

    public function test_posting_requires_both_verifications_or_admin_approval(): void
    {
        $employer = $this->makeEmployer([
            'email_verified_at' => null,
            'mobile_verified_at' => null,
        ]);

        $this->actingAs($employer)
            ->post(route('account.employer.jobs.store'), $this->jobPayload())
            ->assertSessionHasErrors('posting');
        $this->assertSame(0, Job::query()->count());

        $employer->forceFill([
            'email_verified_at' => now(),
            'mobile_verified_at' => now(),
        ])->save();

        $this->actingAs($employer)
            ->post(route('account.employer.jobs.store'), $this->jobPayload())
            ->assertRedirect(route('account.employer.jobs.index'));

        $approved = $this->makeEmployer([
            'email_verified_at' => null,
            'mobile_verified_at' => null,
        ]);
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->patch(route('admin.employers.posting-approval', $approved->employerProfile))
            ->assertRedirect();

        $this->actingAs($approved)
            ->post(route('account.employer.jobs.store'), $this->jobPayload(['title' => 'Admin Approved Role']))
            ->assertRedirect(route('account.employer.jobs.index'));
    }

    public function test_employer_jobs_are_owned_validated_trial_then_basic_and_never_unpaid_featured(): void
    {
        $employer = $this->eligibleEmployer();
        $other = $this->eligibleEmployer();
        $employer->employerProfile->update(['company_name' => 'Owner Company']);

        $this->actingAs($employer)
            ->post(route('account.employer.jobs.store'), $this->jobPayload([
                'employer_id' => $other->employerProfile->id,
                'project' => 'Spoofed Company',
                'featured' => true,
            ]))
            ->assertRedirect(route('account.employer.jobs.index'));

        $trial = Job::query()->where('title', 'Employer Posted Role')->firstOrFail();
        $this->assertSame($employer->employerProfile->id, $trial->employer_id);
        $this->assertSame('Owner Company', $trial->project);
        $this->assertSame('trial', $trial->posting_tier);
        $this->assertNotNull($trial->trial_expires_at);
        $this->assertFalse($trial->featured);
        $this->assertSame('published', $trial->publishing_status->value);

        $this->actingAs($employer)
            ->post(route('account.employer.jobs.store'), $this->jobPayload(['title' => 'Second Basic Role']))
            ->assertRedirect(route('account.employer.jobs.index'));

        $basic = Job::query()->where('title', 'Second Basic Role')->firstOrFail();
        $this->assertSame('basic', $basic->posting_tier);
        $this->assertNull($basic->trial_expires_at);
        $this->assertFalse($basic->featured);

        $this->travel(61)->minutes();
        $this->get(route('jobs.show', $trial))->assertNotFound();
        $this->get(route('jobs.show', $basic))->assertOk();
    }

    public function test_employer_can_update_and_delete_only_owned_jobs(): void
    {
        $owner = $this->eligibleEmployer();
        $other = $this->eligibleEmployer();
        $job = Job::factory()->create(['employer_id' => $owner->employerProfile->id]);

        $this->actingAs($owner)
            ->put(route('account.employer.jobs.update', $job), $this->jobPayload([
                'title' => 'Updated Employer Role',
                'publishing_status' => 'draft',
            ]))
            ->assertRedirect(route('account.employer.jobs.index'));

        $job->refresh();
        $this->assertSame('Updated Employer Role', $job->title);
        $this->assertSame('published', $job->publishing_status->value);
        $this->assertFalse($job->featured);

        $this->actingAs($other)
            ->delete(route('account.employer.jobs.destroy', $job))
            ->assertForbidden();

        $this->actingAs($owner)
            ->delete(route('account.employer.jobs.destroy', $job))
            ->assertRedirect(route('account.employer.jobs.index'));

        $this->assertDatabaseMissing('job_listings', ['id' => $job->id]);
    }

    public function test_job_ownership_and_applicant_privacy_are_enforced(): void
    {
        $owner = $this->eligibleEmployer();
        $other = $this->eligibleEmployer();
        $candidate = $this->makeCandidate([
            'name' => 'Visible Candidate',
            'username' => 'visible-candidate',
            'photo_path' => 'users/candidate.png',
        ]);
        Storage::disk('public')->put('users/candidate.png', 'image');
        $candidate->candidateProfile->update(['current_designation' => 'Engineer']);

        $job = Job::factory()->create(['employer_id' => $owner->employerProfile->id]);
        $application = Application::factory()->create([
            'job_id' => $job->id,
            'user_id' => $candidate->id,
            'full_name' => $candidate->name,
            'email' => $candidate->email,
            'resume_path' => 'applications/snapshot.pdf',
        ]);
        Storage::disk('resumes')->put('applications/snapshot.pdf', '%PDF-1.4');

        $this->actingAs($owner)->get(route('account.employer.jobs.applicants', $job))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Employer/Applicants/Index')
                ->where('applications.0.name', 'Visible Candidate')
                ->where('applications.0.username', 'visible-candidate')
                ->where('applications.0.designation', 'Engineer')
                ->where('applications.0.has_resume', true)
                ->missing('applications.0.resume_path')
                ->missing('applications.0.documents'));

        $this->actingAs($owner)
            ->get(route('account.employer.applications.resume', $application))
            ->assertOk();

        $this->actingAs($other)->get(route('account.employer.jobs.edit', $job))->assertForbidden();
        $this->actingAs($other)->get(route('account.employer.jobs.applicants', $job))->assertForbidden();
        $this->actingAs($other)->get(route('account.employer.applications.resume', $application))->assertForbidden();
        $this->actingAs($other)->delete(route('account.employer.jobs.destroy', $job))->assertForbidden();
    }

    public function test_candidate_cannot_create_employer_jobs_and_employer_cannot_access_admin(): void
    {
        $candidate = $this->makeCandidate();
        $employer = $this->eligibleEmployer();

        $this->actingAs($candidate)
            ->post(route('account.employer.jobs.store'), $this->jobPayload())
            ->assertForbidden();
        $this->actingAs($employer)->get(route('admin.jobs.index'))->assertForbidden();
        $this->actingAs($candidate)->get(route('admin.jobs.index'))->assertForbidden();
    }

    public function test_public_candidate_and_company_profiles_expose_only_public_fields(): void
    {
        $candidate = $this->makeCandidate([
            'name' => 'Public Candidate',
            'username' => 'public-candidate',
            'bio' => 'Professional summary',
        ]);
        $candidate->candidateProfile->update([
            'current_designation' => 'Project Engineer',
            'qualification' => 'BEng',
        ]);

        $employer = $this->eligibleEmployer();
        $employer->employerProfile->update([
            'company_name' => 'Safe Company',
            'about_company' => 'Public about text',
            'company_size' => '51-200',
            'company_website' => 'https://example.com',
            'company_details' => 'Public company details',
        ]);

        $this->get(route('profiles.show', ['user' => $candidate->username]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Profiles/Show')
                ->where('profile.name', 'Public Candidate')
                ->where('profile.designation', 'Project Engineer')
                ->missing('profile.email')
                ->missing('profile.mobile')
                ->missing('profile.resumes')
                ->missing('profile.documents'));

        $this->get(route('companies.show', $employer->employerProfile))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Companies/Show')
                ->where('company.company_name', 'Safe Company')
                ->where('company.company_website', 'https://example.com')
                ->missing('company.documents')
                ->missing('company.contact_person')
                ->missing('company.mobile_number'));
    }

    public function test_company_website_rejects_unsafe_urls(): void
    {
        $employer = $this->eligibleEmployer();

        $this->actingAs($employer)
            ->from(route('account.profile.edit'))
            ->put(route('account.profile.update'), [
                'name' => $employer->name,
                'username' => $employer->username,
                'preferred_language' => 'en',
                'company_website' => 'javascript:alert(1)',
            ])
            ->assertRedirect(route('account.profile.edit'))
            ->assertSessionHasErrors('company_website');
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
