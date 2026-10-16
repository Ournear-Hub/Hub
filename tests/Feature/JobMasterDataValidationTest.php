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
use App\Models\Skill;
use Tests\TestCase;

class JobMasterDataValidationTest extends TestCase
{
    public function test_admin_job_store_accepts_valid_master_data_ids(): void
    {
        $admin = $this->makeAdmin();
        $qualification = Qualification::query()->firstOrCreate(
            ['name' => 'Valid Qualification E1'],
            ['is_active' => true, 'sort_order' => 1],
        );
        $skill = Skill::findOrCreateFromName('Valid Skill E1');

        $this->actingAs($admin)
            ->post(route('admin.jobs.store'), $this->jobPayload([
                'qualification_ids' => [$qualification->id],
                'skill_ids' => [$skill->id],
            ]))
            ->assertRedirect();

        $job = Job::query()->where('title', 'Master Data Validation Job')->latest('id')->first();
        $this->assertNotNull($job);
        $this->assertSame([$qualification->id], $job->qualifications()->pluck('qualifications.id')->map(fn ($id) => (int) $id)->all());
        $this->assertSame([$skill->id], $job->skills()->pluck('skills.id')->map(fn ($id) => (int) $id)->all());
    }

    public function test_rejects_nonexistent_qualification_and_skill_ids(): void
    {
        $admin = $this->makeAdmin();
        $missingQualificationId = (Qualification::query()->max('id') ?? 0) + 5000;
        $missingSkillId = (Skill::query()->max('id') ?? 0) + 5000;

        $this->actingAs($admin)
            ->from(route('admin.jobs.create'))
            ->post(route('admin.jobs.store'), $this->jobPayload([
                'qualification_ids' => [$missingQualificationId],
                'skill_ids' => [$missingSkillId],
            ]))
            ->assertSessionHasErrors(['qualification_ids.0', 'skill_ids.0']);
    }

    public function test_rejects_inactive_qualification_and_master_lookup_ids(): void
    {
        $admin = $this->makeAdmin();
        $inactiveQualification = Qualification::query()->create([
            'name' => 'Inactive Qualification E1',
            'is_active' => false,
            'sort_order' => 99,
        ]);
        $inactiveLocation = Location::query()->create([
            'name' => 'Inactive City E1',
            'is_active' => false,
            'sort_order' => 99,
        ]);

        $this->actingAs($admin)
            ->from(route('admin.jobs.create'))
            ->post(route('admin.jobs.store'), $this->jobPayload([
                'qualification_ids' => [$inactiveQualification->id],
                'location_id' => $inactiveLocation->id,
            ]))
            ->assertSessionHasErrors(['qualification_ids.0', 'location_id']);
    }

    public function test_rejects_mismatched_industry_category_and_title_hierarchy(): void
    {
        $admin = $this->makeAdmin();
        $industryA = Industry::findByName('Construction');
        $industryB = Industry::query()->firstOrCreate(
            ['name' => 'Alternate Industry E1'],
            ['is_active' => true, 'sort_order' => 99],
        );
        $categoryOnB = JobCategory::query()->firstOrCreate(
            ['name' => 'Category On B E1', 'industry_id' => $industryB->id],
            ['is_active' => true, 'sort_order' => 99],
        );
        $titleOnB = JobTitle::query()->firstOrCreate(
            ['name' => 'Title On B E1', 'job_category_id' => $categoryOnB->id],
            ['is_active' => true, 'sort_order' => 99],
        );

        $this->assertNotNull($industryA);

        $this->actingAs($admin)
            ->from(route('admin.jobs.create'))
            ->post(route('admin.jobs.store'), $this->jobPayload([
                'industry_id' => $industryA->id,
                'job_category_id' => $categoryOnB->id,
                'job_title_id' => $titleOnB->id,
            ]))
            ->assertSessionHasErrors(['job_category_id', 'job_title_id']);
    }

    public function test_rejects_malformed_master_data_ids_and_array_elements(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->from(route('admin.jobs.create'))
            ->post(route('admin.jobs.store'), $this->jobPayload([
                'industry_id' => 'not-an-id',
                'qualification_ids' => ['bad-id'],
                'skill_ids' => [0],
            ]))
            ->assertSessionHasErrors(['industry_id', 'qualification_ids.0', 'skill_ids.0']);
    }

    public function test_update_without_optional_requirement_arrays_preserves_existing_links(): void
    {
        $admin = $this->makeAdmin();
        $qualification = Qualification::query()->firstOrCreate(
            ['name' => 'Persist Qualification E1'],
            ['is_active' => true, 'sort_order' => 1],
        );
        $skill = Skill::findOrCreateFromName('Persist Skill E1');

        $this->actingAs($admin)
            ->post(route('admin.jobs.store'), $this->jobPayload([
                'qualification_ids' => [$qualification->id],
                'skill_ids' => [$skill->id],
            ]))
            ->assertRedirect();

        $job = Job::query()->where('title', 'Master Data Validation Job')->latest('id')->firstOrFail();

        $this->actingAs($admin)
            ->put(route('admin.jobs.update', $job), $this->jobPayload([
                'title' => 'Master Data Validation Job Updated',
            ]))
            ->assertRedirect();

        $job->refresh();
        $this->assertSame('Master Data Validation Job Updated', $job->title);
        $this->assertSame([$qualification->id], $job->qualifications()->pluck('qualifications.id')->map(fn ($id) => (int) $id)->all());
        $this->assertSame([$skill->id], $job->skills()->pluck('skills.id')->map(fn ($id) => (int) $id)->all());
    }

    public function test_employer_cannot_assign_job_to_another_company_via_employer_id(): void
    {
        $owner = $this->makeEmployer(['email_verified_at' => now(), 'mobile_verified_at' => now()]);
        $other = $this->makeEmployer(['email_verified_at' => now(), 'mobile_verified_at' => now()]);

        $this->actingAs($owner)
            ->from(route('account.employer.jobs.create'))
            ->post(route('account.employer.jobs.store'), $this->jobPayload([
                'employer_id' => $other->employerProfile?->id,
                'publishing_status' => JobPublishingStatus::Draft->value,
            ]))
            ->assertSessionHasErrors('employer_id');
    }

    public function test_employer_job_update_rejects_invalid_skill_id(): void
    {
        $employer = $this->makeEmployer(['email_verified_at' => now(), 'mobile_verified_at' => now()]);
        $job = Job::factory()->create(['employer_id' => $employer->employerProfile->id]);
        $missingSkillId = (Skill::query()->max('id') ?? 0) + 9000;

        $this->actingAs($employer)
            ->from(route('account.employer.jobs.edit', $job))
            ->put(route('account.employer.jobs.update', $job), $this->jobPayload([
                'publishing_status' => JobPublishingStatus::Draft->value,
                'skill_ids' => [$missingSkillId],
            ]))
            ->assertSessionHasErrors('skill_ids.0');

        $this->assertSame(0, $job->fresh()->skills()->count());
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function jobPayload(array $overrides = []): array
    {
        $industry = Industry::query()->firstOrCreate(
            ['name' => 'Construction'],
            ['is_active' => true, 'sort_order' => 1],
        );
        $category = JobCategory::query()->firstOrCreate(
            ['name' => 'Civil', 'industry_id' => $industry->id],
            ['is_active' => true, 'sort_order' => 1],
        );
        $title = JobTitle::query()->firstOrCreate(
            ['name' => 'Site Engineer', 'job_category_id' => $category->id],
            ['is_active' => true, 'sort_order' => 1],
        );
        $location = Location::query()->where('is_active', true)->first()
            ?? Location::query()->create(['name' => 'Validation City', 'is_active' => true, 'sort_order' => 1]);
        $employment = EmploymentType::findByName('Full-time');
        $experience = ExperienceRange::findByName('1-3 years');

        return [
            'title' => 'Master Data Validation Job',
            'project' => 'Validation Co',
            'industry_id' => $industry->id,
            'job_category_id' => $category->id,
            'job_title_id' => $title->id,
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
            'description' => 'Validation job description.',
            'responsibilities' => 'Deliver work.',
            'requirements' => 'Relevant experience.',
            'what_we_offer' => 'Training.',
            ...$overrides,
        ];
    }
}
