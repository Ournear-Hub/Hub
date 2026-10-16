<?php

namespace Tests\Feature;

use App\Enums\JobPublishingStatus;
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
}
