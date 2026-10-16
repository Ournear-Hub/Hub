<?php

namespace Tests\Feature;

use App\Models\EmploymentType;
use App\Models\ExperienceRange;
use App\Models\Job;
use App\Models\JobTitle;
use App\Models\Location;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EmployerCompanyWorkspaceTest extends TestCase
{
    public function test_account_company_renders_company_workspace_for_employer(): void
    {
        $employer = $this->eligibleEmployer();
        $employer->employerProfile->update(['company_name' => 'Workspace Co']);

        $this->actingAs($employer)
            ->get(route('account.company'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Profile')
                ->where('companyWorkspace', true)
                ->where('role', 'employer')
                ->where('profile.company_name', 'Workspace Co')
                ->has('lookups.company_public_share_fields'));

        $this->actingAs($employer)
            ->get('/account/profile/company')
            ->assertRedirect('/account/company');
    }

    public function test_candidate_without_hiring_is_redirected_from_company_workspace(): void
    {
        $candidate = $this->makeCandidate();

        $this->actingAs($candidate)
            ->get(route('account.company'))
            ->assertRedirect(route('account.hiring'));
    }

    public function test_guest_cannot_access_company_workspace(): void
    {
        $this->get(route('account.company'))->assertRedirect(route('login'));
    }

    public function test_hiring_enabled_candidate_can_persist_company_profile_fields(): void
    {
        $candidate = $this->makeCandidate([
            'email_verified_at' => now(),
            'mobile_verified_at' => now(),
        ]);
        $location = $this->location();

        $this->actingAs($candidate)
            ->post(route('account.hiring.activate'), [
                'company_name' => 'Dual Role Co',
                'company_phone' => '+919876543212',
                'company_email' => $candidate->email,
                'industry_id' => $this->industryId(),
            ])
            ->assertRedirect(route('account.hiring'));

        $this->actingAs($candidate)
            ->get(route('account.company'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('companyWorkspace', true)
                ->where('role', 'employer'));

        $this->actingAs($candidate)
            ->put(route('account.profile.update'), [
                'name' => $candidate->name,
                'username' => $candidate->username,
                'preferred_language' => 'en',
                'company_name' => 'Dual Role Co Updated',
                'location_ids' => [$location->id],
                'benefits' => ['Flexible hours'],
            ])
            ->assertRedirect();

        $this->assertSame(
            'Dual Role Co Updated',
            $candidate->fresh()->employerProfile?->company_name,
        );
        $this->assertTrue($candidate->fresh()->employerProfile?->locations->contains('id', $location->id));
    }

    public function test_employer_company_profile_update_does_not_clear_omitted_optional_fields(): void
    {
        $employer = $this->eligibleEmployer();
        $employer->employerProfile->update([
            'company_name' => 'Persist Co',
            'about_company' => 'Original about text',
            'benefits' => ['Pension'],
        ]);

        $this->actingAs($employer)
            ->put(route('account.profile.update'), [
                'name' => $employer->name,
                'username' => $employer->username,
                'preferred_language' => 'en',
                'company_name' => 'Persist Co',
                'industry' => 'Engineering',
            ])
            ->assertRedirect();

        $profile = $employer->fresh()->employerProfile;
        $this->assertSame('Engineering', $profile?->industry);
        $this->assertSame('Original about text', $profile?->about_company);
        $this->assertContains('Pension', $profile?->benefitList() ?? []);
    }

    public function test_employer_job_update_remains_scoped_to_owner(): void
    {
        $owner = $this->eligibleEmployer();
        $other = $this->eligibleEmployer();
        $job = Job::factory()->create(['employer_id' => $owner->employerProfile->id, 'title' => 'Scoped Role']);

        $payload = $this->jobPayload(['title' => 'Hijacked', 'publishing_status' => 'draft']);

        $this->actingAs($other)
            ->put(route('account.employer.jobs.update', $job), $payload)
            ->assertForbidden();

        $this->actingAs($owner)
            ->put(route('account.employer.jobs.update', $job), $this->jobPayload([
                'title' => 'Scoped Role Updated',
                'publishing_status' => 'draft',
            ]))
            ->assertRedirect(route('account.employer.jobs.index'));

        $this->assertSame('Scoped Role Updated', $job->fresh()->title);
    }

    private function eligibleEmployer()
    {
        return $this->makeEmployer([
            'email_verified_at' => now(),
            'mobile_verified_at' => now(),
        ]);
    }

    private function location(): Location
    {
        return Location::query()->where('is_active', true)->first()
            ?? Location::query()->create(['name' => 'Company Workspace City', 'is_active' => true, 'sort_order' => 99]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function jobPayload(array $overrides = []): array
    {
        $title = JobTitle::query()->with('category.industry')->where('is_active', true)->firstOrFail();
        $location = $this->location();
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
