<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\JobPublishingStatus;
use App\Models\Application;
use App\Models\EmploymentType;
use App\Models\ExperienceRange;
use App\Models\Job;
use App\Models\JobTitle;
use App\Models\Location;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class EmployerUnverifiedTrialPublishTest extends TestCase
{
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

    public function test_unverified_employer_can_activate_workspace_and_publish_one_trial_job(): void
    {
        $employer = $this->makeEmployer([
            'email_verified_at' => null,
            'mobile_verified_at' => null,
        ]);

        $title = JobTitle::query()->with('category.industry')->where('is_active', true)->firstOrFail();

        $this->actingAs($employer)
            ->post(route('account.hiring'), [
                'company_name' => 'Trial Co',
                'company_phone' => '+919876543210',
                'company_email' => 'hr@trialco.example',
                'industry_id' => $title->category->industry_id,
            ])
            ->assertRedirect();

        $employer->refresh();
        $this->assertNull($employer->email_verified_at);
        $this->assertNull($employer->mobile_verified_at);
        $this->assertSame('Trial Co', $employer->employerProfile?->company_name);

        $this->actingAs($employer)
            ->post(route('account.employer.jobs.store'), $this->jobPayload(['title' => 'Trial Listing']))
            ->assertRedirect(route('account.employer.jobs.index'));

        $job = Job::query()->where('title', 'Trial Listing')->firstOrFail();
        $this->assertSame('trial', $job->posting_tier);
        $this->assertSame('published', $job->publishing_status->value);
        $this->assertNotNull($job->trial_expires_at);
        $this->assertNotNull($employer->employerProfile?->fresh()->trial_job_used_at);

        $this->get(route('jobs.show', $job))->assertOk();
    }

    public function test_unverified_employer_cannot_publish_second_job(): void
    {
        $employer = $this->makeEmployer([
            'email_verified_at' => null,
            'mobile_verified_at' => null,
        ]);
        $profile = $employer->employerProfile;
        $this->assertNotNull($profile);
        $profile->update([
            'company_name' => 'Second Block Co',
            'mobile_number' => '+919876543211',
        ]);
        $employer->forceFill(['hiring_email' => 'ops@secondblock.example'])->save();

        $this->actingAs($employer)
            ->post(route('account.employer.jobs.store'), $this->jobPayload(['title' => 'First Trial']))
            ->assertRedirect(route('account.employer.jobs.index'));

        $this->actingAs($employer)
            ->post(route('account.employer.jobs.store'), $this->jobPayload(['title' => 'Second Attempt']))
            ->assertSessionHasErrors('posting');
    }

    public function test_expired_trial_job_moves_to_draft_and_hides_from_public_listings(): void
    {
        $employer = $this->makeEmployer([
            'email_verified_at' => null,
            'mobile_verified_at' => null,
        ]);
        $profile = $employer->employerProfile;
        $this->assertNotNull($profile);
        $profile->update([
            'company_name' => 'Expiry Co',
            'mobile_number' => '+919876543212',
        ]);
        $employer->forceFill(['hiring_email' => 'hr@expiry.example'])->save();

        $this->actingAs($employer)
            ->post(route('account.employer.jobs.store'), $this->jobPayload(['title' => 'Expiring Trial']))
            ->assertRedirect(route('account.employer.jobs.index'));

        $job = Job::query()->where('title', 'Expiring Trial')->firstOrFail();
        $job->forceFill(['trial_expires_at' => now()->subMinute()])->save();

        $this->get(route('jobs.show', $job))->assertNotFound();

        Artisan::call('jobs:expire-trials');

        $job->refresh();
        $this->assertSame(JobPublishingStatus::Draft, $job->publishing_status);
        $this->assertFalse($job->active);

        Artisan::call('jobs:expire-trials');
        $this->assertSame(JobPublishingStatus::Draft, $job->fresh()->publishing_status);
    }

    public function test_trial_job_without_expiry_is_not_publicly_visible(): void
    {
        $employer = $this->makeEmployer([
            'email_verified_at' => null,
            'mobile_verified_at' => null,
        ]);

        $this->actingAs($employer)
            ->post(route('account.hiring'), [
                'company_name' => 'Missing Expiry Co',
                'company_phone' => '+919876543214',
                'company_email' => 'hr@missing-expiry.example',
                'industry_id' => JobTitle::query()->with('category.industry')->where('is_active', true)->firstOrFail()->category->industry_id,
            ])
            ->assertRedirect();

        $this->actingAs($employer)
            ->post(route('account.employer.jobs.store'), $this->jobPayload(['title' => 'Trial Missing Expiry']))
            ->assertRedirect(route('account.employer.jobs.index'));

        $job = Job::query()->where('title', 'Trial Missing Expiry')->firstOrFail();
        $this->assertSame('trial', $job->posting_tier);
        $job->forceFill(['trial_expires_at' => null])->save();
        $job->refresh();

        $this->assertFalse($job->isPubliclyVisible());
        $this->assertFalse(Job::query()->publiclyVisible()->whereKey($job->id)->exists());
        $this->get(route('jobs.show', $job))->assertNotFound();

        Artisan::call('jobs:expire-trials');

        $job->refresh();
        $this->assertSame(JobPublishingStatus::Draft, $job->publishing_status);
        $this->assertFalse($job->active);
    }

    public function test_verified_employer_can_publish_multiple_jobs(): void
    {
        $employer = $this->makeEmployer([
            'email_verified_at' => now(),
            'mobile_verified_at' => now(),
        ]);
        $profile = $employer->employerProfile;
        $this->assertNotNull($profile);
        $profile->update([
            'company_name' => 'Verified Co',
            'mobile_number' => '+919876543213',
        ]);
        $employer->forceFill(['hiring_email' => 'hr@verified.example'])->save();
        $profile->forceFill(['trial_job_used_at' => now()])->save();

        $this->actingAs($employer)
            ->post(route('account.employer.jobs.store'), $this->jobPayload(['title' => 'Verified One']))
            ->assertRedirect(route('account.employer.jobs.index'));

        $this->actingAs($employer)
            ->post(route('account.employer.jobs.store'), $this->jobPayload(['title' => 'Verified Two']))
            ->assertRedirect(route('account.employer.jobs.index'));

        $this->assertSame(2, Job::query()->where('employer_id', $profile->id)->count());
    }

    public function test_candidate_who_activated_a_company_can_shortlist_their_own_applicant(): void
    {
        $owner = $this->makeCandidate([
            'email_verified_at' => now(),
        ]);
        $this->actingAs($owner)
            ->post(route('account.hiring'), [
                'company_name' => 'Capability Co',
                'company_phone' => '+919876543215',
                'company_email' => 'hr@capability.example',
                'industry_id' => JobTitle::query()->with('category.industry')->where('is_active', true)->firstOrFail()->category->industry_id,
            ])
            ->assertRedirect();

        $owner->refresh();
        $this->assertTrue($owner->isCandidate());
        $profile = $owner->employerProfile;
        $this->assertNotNull($profile);

        $job = Job::factory()->create([
            'employer_id' => $profile->id,
            'publishing_status' => JobPublishingStatus::Published,
            'active' => true,
        ]);
        $applicant = $this->makeCandidate();
        $application = Application::factory()->create([
            'job_id' => $job->id,
            'user_id' => $applicant->id,
            'email' => $applicant->email,
            'status' => ApplicationStatus::New,
        ]);

        $this->actingAs($owner)
            ->post(route('account.employer.jobs.application.shortlist', [$job, $application]))
            ->assertRedirect();

        $this->assertSame(ApplicationStatus::Shortlisted, $application->fresh()->status);

        $this->actingAs($applicant)
            ->post(route('account.employer.jobs.application.shortlist', [$job, $application]))
            ->assertForbidden();
    }

    public function test_company_owner_can_complete_offer_and_joining_after_opening_a_placement(): void
    {
        $owner = $this->makeCandidate([
            'email_verified_at' => now(),
        ]);
        $this->actingAs($owner)
            ->post(route('account.hiring'), [
                'company_name' => 'Placement Co',
                'company_phone' => '+919876543216',
                'company_email' => 'hr@placement.example',
                'industry_id' => JobTitle::query()->with('category.industry')->where('is_active', true)->firstOrFail()->category->industry_id,
            ])
            ->assertRedirect();

        $owner = $owner->fresh();
        $profile = $owner->employerProfile;
        $this->assertNotNull($profile);
        $job = Job::factory()->create([
            'employer_id' => $profile->id,
            'publishing_status' => JobPublishingStatus::Published,
            'active' => true,
        ]);
        $applicant = $this->makeCandidate();
        $application = Application::factory()->create([
            'job_id' => $job->id,
            'user_id' => $applicant->id,
            'email' => $applicant->email,
            'status' => ApplicationStatus::New,
        ]);

        $this->actingAs($owner)
            ->post(route('account.employer.jobs.application.shortlist', [$job, $application]))
            ->assertRedirect();
        $this->actingAs($owner)
            ->post(route('account.employer.jobs.application.select', [$job, $application]))
            ->assertRedirect();
        $this->actingAs($owner)
            ->post(route('account.employer.jobs.application.placement', [$job, $application]))
            ->assertRedirect();

        $this->actingAs($owner)
            ->get(route('account.employer.jobs.application', [$job, $application]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('recruitment.placement_status_label', 'Open')
                ->where('recruitment.can_offer', true));

        $this->actingAs($applicant)
            ->post(route('account.employer.jobs.application.offer', [$job, $application]), [
                'offered_at' => now()->toDateString(),
            ])
            ->assertForbidden();

        $this->actingAs($owner)
            ->from(route('account.employer.jobs.application', [$job, $application]))
            ->post(route('account.employer.jobs.application.offer', [$job, $application]), [])
            ->assertSessionHasErrors('offered_at');

        $this->actingAs($owner)
            ->post(route('account.employer.jobs.application.offer', [$job, $application]), [
                'offered_at' => now()->toDateString(),
                'salary' => '45000',
            ])
            ->assertRedirect();
        $this->actingAs($owner)
            ->post(route('account.employer.jobs.application.accept-offer', [$job, $application]))
            ->assertRedirect();
        $this->actingAs($owner)
            ->post(route('account.employer.jobs.application.confirm-joining', [$job, $application]), [
                'expected_joining_date' => now()->addWeek()->toDateString(),
            ])
            ->assertRedirect();
        $this->actingAs($owner)
            ->post(route('account.employer.jobs.application.join', [$job, $application]), [
                'actual_joining_date' => now()->toDateString(),
            ])
            ->assertRedirect();

        $this->assertSame(ApplicationStatus::Placed, $application->fresh()->status);
        $this->assertSame('Joined', $application->fresh()->placements()->latest('id')->first()?->status->value);
    }
}
