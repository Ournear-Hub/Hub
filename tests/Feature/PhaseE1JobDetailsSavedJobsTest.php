<?php

namespace Tests\Feature;

use App\Enums\JobPublishingStatus;
use App\Models\EmploymentType;
use App\Models\ExperienceRange;
use App\Models\Industry;
use App\Models\Job;
use App\Models\JobCategory;
use App\Models\JobTitle;
use App\Models\Location;
use App\Models\Qualification;
use App\Models\SavedJob;
use App\Models\Skill;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Phase E1 — job skills/qualifications, saved jobs cards, employer verification label.
 */
class PhaseE1JobDetailsSavedJobsTest extends TestCase
{
    public function test_job_stores_master_qualifications_and_skills_on_public_detail(): void
    {
        $admin = $this->makeAdmin();
        $qualification = Qualification::query()->firstOrCreate(
            ['name' => 'Diploma in Civil Engineering'],
            ['is_active' => true, 'sort_order' => 1],
        );
        $skill = Skill::findOrCreateFromName('Site Supervision');

        $this->actingAs($admin)
            ->post(route('admin.jobs.store'), $this->jobPayload([
                'publishing_status' => JobPublishingStatus::Published->value,
                'qualification_ids' => [$qualification->id],
                'skill_ids' => [$skill->id],
            ]))
            ->assertRedirect();

        $job = Job::query()->where('title', 'Site Engineer')->latest('id')->first();
        $this->assertNotNull($job);
        $this->assertSame([$qualification->id], $job->qualifications()->pluck('qualifications.id')->map(fn ($id) => (int) $id)->all());
        $this->assertSame([$skill->id], $job->skills()->pluck('skills.id')->map(fn ($id) => (int) $id)->all());

        $this->get(route('jobs.show', $job))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('job.qualifications', ['Diploma in Civil Engineering'])
                ->where('job.skills', ['Site Supervision']));
    }

    public function test_unverified_employer_shows_public_label_and_saved_jobs_use_job_card_payload(): void
    {
        $employer = $this->makeEmployer();
        $employer->employerProfile()->update([
            'company_name' => 'Trial Works Ltd',
            'job_posting_approved_at' => null,
        ]);

        $job = Job::factory()->create([
            'title' => 'Trial Site Role',
            'employer_id' => $employer->employerProfile->id,
            'project' => 'Trial Works Ltd',
        ]);

        $this->get(route('jobs.show', $job))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->missing('job.company_verified'));

        $candidate = $this->makeCandidate();
        SavedJob::query()->create([
            'user_id' => $candidate->id,
            'job_id' => $job->id,
        ]);

        $this->actingAs($candidate)
            ->get(route('account.saved-jobs.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/SavedJobs')
                ->has('savedJobs.data', 1)
                ->where('savedJobs.data.0.slug', $job->slug)
                ->where('savedJobs.data.0.company_name', 'Trial Works Ltd')
                ->where('savedJobs.data.0.is_saved', true)
                ->missing('savedJobs.data.0.company_verified'));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function jobPayload(array $overrides = []): array
    {
        $industry = Industry::findByName('Construction');
        $civil = JobCategory::query()->where('name', 'Civil')->first();
        $civilTitle = JobTitle::query()->where('name', 'Site Engineer')->first();
        $location = Location::query()->firstOrCreate(['name' => 'Sheffield'], ['is_active' => true, 'sort_order' => 1]);
        $employment = EmploymentType::findByName('Full-time');
        $experience = ExperienceRange::findByName('1-3 years');

        return [
            'title' => 'Site Engineer',
            'project' => 'Test Project',
            'industry_id' => $industry?->id,
            'job_category_id' => $civil?->id,
            'job_title_id' => $civilTitle?->id,
            'location_id' => $location->id,
            'employment_type_id' => $employment?->id,
            'experience_range_id' => $experience?->id,
            'salary' => 'Competitive',
            'vacancy' => 1,
            'posted_date' => now()->toDateString(),
            'closing_date' => now()->addDays(14)->toDateString(),
            'featured' => false,
            'active' => true,
            'publishing_status' => JobPublishingStatus::Draft->value,
            'description' => 'A recruitment listing.',
            'responsibilities' => 'Deliver the work.',
            'requirements' => 'Relevant experience.',
            'what_we_offer' => 'Training.',
            ...$overrides,
        ];
    }
}
