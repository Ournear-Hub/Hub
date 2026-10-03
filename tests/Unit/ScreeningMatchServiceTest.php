<?php

namespace Tests\Unit;

use App\Enums\ApplicationStatus;
use App\Enums\NoticePeriod;
use App\Models\Application;
use App\Models\Industry;
use App\Models\Job;
use App\Models\Skill;
use App\Services\ScreeningMatchService;
use Tests\TestCase;

class ScreeningMatchServiceTest extends TestCase
{
    public function test_matching_location_and_experience_produce_a_transparent_score(): void
    {
        $job = Job::factory()->create([
            'location' => 'Manchester',
            'experience' => '1-3 years',
        ]);
        $application = Application::factory()->create([
            'job_id' => $job->id,
            'current_location' => 'Manchester',
            'experience' => '1-3 years',
        ]);

        $result = app(ScreeningMatchService::class)->evaluate($application);
        $factors = collect($result['factors']);

        $this->assertSame('scored', $result['state']);
        $this->assertSame(100, $result['percent']);
        $this->assertSame('100%', $result['label']);
        $this->assertSame(2, $result['comparable_count']);
        $this->assertTrue($factors->firstWhere('key', 'location')['contributed']);
        $this->assertTrue($factors->firstWhere('key', 'experience')['contributed']);
        $this->assertSame('unavailable', $factors->firstWhere('key', 'skills')['state']);
        $this->assertFalse($factors->firstWhere('key', 'skills')['contributed']);
        $this->assertStringContainsString('does not determine suitability', $result['disclaimer']);
        $this->assertSame(ScreeningMatchService::DISCLAIMER, $result['disclaimer']);

        foreach ($result['factors'] as $factor) {
            $this->assertStringNotContainsString('V1.1', $factor['note']);
            $this->assertStringNotContainsString('not stored in V1.1', strtolower($factor['note']));
        }
    }

    public function test_missing_comparable_fields_return_not_enough_data(): void
    {
        $job = Job::factory()->create([
            'location' => '',
            'experience' => '',
            'industry' => '',
            'industry_id' => null,
        ]);
        $application = Application::factory()->create([
            'job_id' => $job->id,
            'current_location' => '',
            'experience' => '',
        ]);

        $result = app(ScreeningMatchService::class)->evaluate($application);

        $this->assertSame('not_enough_data', $result['state']);
        $this->assertNull($result['percent']);
        $this->assertSame('Not enough data', $result['label']);
        $this->assertSame(0, $result['comparable_count']);
    }

    public function test_guest_application_does_not_score_industry_or_relocation(): void
    {
        $industry = $this->industry('Stage Thirteen Construction');
        $job = Job::factory()->create([
            'location' => 'Manchester',
            'experience' => '1-3 years',
            'industry' => $industry->name,
            'industry_id' => $industry->id,
        ]);
        $application = Application::factory()->create([
            'job_id' => $job->id,
            'user_id' => null,
            'current_location' => 'Manchester',
            'experience' => '1-3 years',
            'qualification' => 'NVQ',
        ]);

        $result = app(ScreeningMatchService::class)->evaluate($application);
        $factors = collect($result['factors']);

        $this->assertSame(2, $result['comparable_count']);
        $this->assertSame(100, $result['percent']);
        $this->assertSame('missing', $factors->firstWhere('key', 'industry')['state']);
        $this->assertSame('missing', $factors->firstWhere('key', 'relocation')['state']);
        $this->assertFalse($factors->firstWhere('key', 'industry')['contributed']);
        $this->assertFalse($factors->firstWhere('key', 'relocation')['contributed']);
        $this->assertSame('presented', $factors->firstWhere('key', 'qualification')['state']);
        $this->assertSame('NVQ', $factors->firstWhere('key', 'qualification')['candidate_value']);
        $this->assertNull($factors->firstWhere('key', 'qualification')['job_value']);
        $this->assertFalse($factors->firstWhere('key', 'qualification')['contributed']);
        $this->assertSame('snapshot', $factors->firstWhere('key', 'location')['source']);
        $this->assertSame('snapshot', $factors->firstWhere('key', 'experience')['source']);
    }

    public function test_industry_scores_only_when_both_sides_are_recorded(): void
    {
        $industry = $this->industry('Stage Thirteen Logistics');
        $other = $this->industry('Stage Thirteen Hospitality');
        $job = Job::factory()->create([
            'location' => 'Leeds',
            'experience' => '3-5 years',
            'industry' => $industry->name,
            'industry_id' => $industry->id,
        ]);

        $withoutIndustry = $this->makeCandidate();
        $withoutIndustry->candidateProfile?->update(['industry_id' => null]);
        $missingApplication = Application::factory()->create([
            'job_id' => $job->id,
            'user_id' => $withoutIndustry->id,
            'current_location' => 'Leeds',
            'experience' => '3-5 years',
        ]);

        $missing = collect(app(ScreeningMatchService::class)->evaluate($missingApplication)['factors'])
            ->firstWhere('key', 'industry');
        $this->assertSame('missing', $missing['state']);
        $this->assertFalse($missing['contributed']);

        $matchingCandidate = $this->makeCandidate();
        $matchingCandidate->candidateProfile?->update(['industry_id' => $industry->id]);
        $matchingApplication = Application::factory()->create([
            'job_id' => $job->id,
            'user_id' => $matchingCandidate->id,
            'current_location' => 'Leeds',
            'experience' => '3-5 years',
        ]);

        $matched = collect(app(ScreeningMatchService::class)->evaluate($matchingApplication)['factors'])
            ->firstWhere('key', 'industry');
        $this->assertSame('compared', $matched['state']);
        $this->assertTrue($matched['matched']);
        $this->assertTrue($matched['contributed']);
        $this->assertSame('profile', $matched['source']);

        $mismatchCandidate = $this->makeCandidate();
        $mismatchCandidate->candidateProfile?->update(['industry_id' => $other->id]);
        $mismatchApplication = Application::factory()->create([
            'job_id' => $job->id,
            'user_id' => $mismatchCandidate->id,
            'current_location' => 'Leeds',
            'experience' => '3-5 years',
        ]);

        $mismatched = collect(app(ScreeningMatchService::class)->evaluate($mismatchApplication)['factors'])
            ->firstWhere('key', 'industry');
        $this->assertSame('compared', $mismatched['state']);
        $this->assertFalse($mismatched['matched']);
        $this->assertFalse($mismatched['contributed']);
    }

    public function test_relocation_scores_only_when_profile_and_job_location_exist(): void
    {
        $job = Job::factory()->create([
            'location' => 'Manchester',
            'experience' => '1-3 years',
        ]);
        $noLocationJob = Job::factory()->create([
            'location' => '',
            'experience' => '1-3 years',
        ]);

        $guest = Application::factory()->create([
            'job_id' => $job->id,
            'user_id' => null,
            'current_location' => 'Manchester',
            'experience' => '1-3 years',
        ]);
        $this->assertSame(
            'missing',
            collect(app(ScreeningMatchService::class)->evaluate($guest)['factors'])->firstWhere('key', 'relocation')['state'],
        );

        $candidate = $this->makeCandidate();
        $candidate->candidateProfile?->update(['willing_to_relocate' => false]);
        $noJobLocation = Application::factory()->create([
            'job_id' => $noLocationJob->id,
            'user_id' => $candidate->id,
            'current_location' => 'Manchester',
            'experience' => '1-3 years',
        ]);
        $this->assertSame(
            'missing',
            collect(app(ScreeningMatchService::class)->evaluate($noJobLocation)['factors'])->firstWhere('key', 'relocation')['state'],
        );

        $sameLocation = Application::factory()->create([
            'job_id' => $job->id,
            'user_id' => $candidate->id,
            'current_location' => 'Manchester',
            'experience' => '1-3 years',
        ]);
        $same = collect(app(ScreeningMatchService::class)->evaluate($sameLocation)['factors'])->firstWhere('key', 'relocation');
        $this->assertSame('compared', $same['state']);
        $this->assertTrue($same['matched']);

        $unwilling = $this->makeCandidate();
        $unwilling->candidateProfile?->update(['willing_to_relocate' => false]);
        $differentCity = Application::factory()->create([
            'job_id' => $job->id,
            'user_id' => $unwilling->id,
            'current_location' => 'Leeds',
            'experience' => '1-3 years',
        ]);
        $mismatch = collect(app(ScreeningMatchService::class)->evaluate($differentCity)['factors'])->firstWhere('key', 'relocation');
        $this->assertSame('compared', $mismatch['state']);
        $this->assertFalse($mismatch['matched']);

        $willing = $this->makeCandidate();
        $willing->candidateProfile?->update(['willing_to_relocate' => true]);
        $relocating = Application::factory()->create([
            'job_id' => $job->id,
            'user_id' => $willing->id,
            'current_location' => 'Leeds',
            'experience' => '1-3 years',
        ]);
        $covers = collect(app(ScreeningMatchService::class)->evaluate($relocating)['factors'])->firstWhere('key', 'relocation');
        $this->assertSame('compared', $covers['state']);
        $this->assertTrue($covers['matched']);
    }

    public function test_informational_factors_are_not_scored(): void
    {
        $job = Job::factory()->create([
            'title' => 'Site Engineer',
            'location' => 'Manchester',
            'experience' => '1-3 years',
            'salary' => '£28,000 - £32,000',
            'joining_date' => now()->addMonth()->toDateString(),
        ]);
        $candidate = $this->makeCandidate();
        $profile = $candidate->candidateProfile;
        $this->assertNotNull($profile);
        $skill = Skill::findOrCreateFromName('Scaffolding');
        $profile->skills()->sync([$skill->id]);
        $profile->update([
            'current_designation' => 'Chargehand',
            'current_salary' => 28000,
            'expected_salary' => 32000,
            'notice_period' => NoticePeriod::Days30,
        ]);
        $application = Application::factory()->create([
            'job_id' => $job->id,
            'user_id' => $candidate->id,
            'current_location' => 'Manchester',
            'experience' => '1-3 years',
            'qualification' => 'NVQ',
        ]);

        $factors = collect(app(ScreeningMatchService::class)->evaluate($application)['factors']);

        foreach (['skills', 'qualification', 'salary', 'notice', 'designation', 'keywords', 'industry_experience'] as $key) {
            $factor = $factors->firstWhere('key', $key);
            $this->assertNotNull($factor, $key.' factor missing');
            $this->assertNotSame('compared', $factor['state'], $key.' must not be scored');
            $this->assertFalse($factor['contributed']);
            $this->assertNull($factor['matched']);
        }

        $this->assertSame('presented', $factors->firstWhere('key', 'skills')['state']);
        $this->assertSame('presented', $factors->firstWhere('key', 'qualification')['state']);
        $this->assertSame('presented', $factors->firstWhere('key', 'salary')['state']);
        $this->assertSame('presented', $factors->firstWhere('key', 'notice')['state']);
        $this->assertSame('presented', $factors->firstWhere('key', 'designation')['state']);
        $this->assertSame('unavailable', $factors->firstWhere('key', 'keywords')['state']);
        $this->assertStringContainsString('structured skills', $factors->firstWhere('key', 'skills')['note']);
    }

    public function test_evaluate_does_not_change_application_status_or_screening_decision(): void
    {
        $application = Application::factory()->create([
            'status' => ApplicationStatus::New,
        ]);

        $this->assertDatabaseMissing('application_screenings', ['application_id' => $application->id]);

        app(ScreeningMatchService::class)->evaluate($application);

        $application->refresh();
        $this->assertSame(ApplicationStatus::New, $application->status);
        $this->assertDatabaseMissing('application_screenings', ['application_id' => $application->id]);
    }

    private function industry(string $name): Industry
    {
        return Industry::query()->firstOrCreate(
            ['name' => $name],
            ['is_active' => true, 'sort_order' => 90],
        );
    }
}
