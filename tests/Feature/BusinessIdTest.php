<?php

namespace Tests\Feature;

use App\Enums\BusinessIdPrefix;
use App\Models\Application;
use App\Models\Job;
use App\Services\MasterData\BusinessIdGenerator;
use Tests\TestCase;

class BusinessIdTest extends TestCase
{
    public function test_new_records_receive_stable_sequential_business_ids(): void
    {
        $candidate = $this->makeCandidate();
        $employer = $this->makeEmployer();
        $firstJob = Job::factory()->create();
        $secondJob = Job::factory()->create();
        $application = Application::factory()->create(['job_id' => $firstJob->id]);

        $this->assertMatchesRegularExpression('/^ONT-C-\d{6}$/', (string) $candidate->candidateProfile?->business_id);
        $this->assertMatchesRegularExpression('/^ONT-E-\d{6}$/', (string) $employer->employerProfile?->business_id);
        $this->assertMatchesRegularExpression('/^ONT-J-\d{6}$/', (string) $firstJob->business_id);
        $this->assertMatchesRegularExpression('/^ONT-J-\d{6}$/', (string) $secondJob->business_id);
        $this->assertMatchesRegularExpression('/^ONT-A-\d{6}$/', (string) $application->business_id);

        $firstNumber = (int) substr((string) $firstJob->business_id, -6);
        $secondNumber = (int) substr((string) $secondJob->business_id, -6);
        $this->assertSame($firstNumber + 1, $secondNumber);
        $this->assertNotSame($firstJob->business_id, $secondJob->business_id);
    }

    public function test_business_ids_are_not_regenerated_on_update(): void
    {
        $job = Job::factory()->create(['title' => 'Original Role']);
        $original = $job->business_id;

        $job->update(['title' => 'Updated Role']);
        $job->business_id = 'ONT-J-999999';
        $job->save();

        $this->assertSame($original, $job->fresh()->business_id);
        $this->assertSame('Updated Role', $job->fresh()->title);
    }

    public function test_generator_does_not_reuse_numbers(): void
    {
        $generator = app(BusinessIdGenerator::class);

        $first = $generator->next(BusinessIdPrefix::Job);
        $second = $generator->next(BusinessIdPrefix::Job);

        $this->assertNotSame($first, $second);
        $this->assertSame(
            ((int) substr($first, -6)) + 1,
            (int) substr($second, -6),
        );
    }
}
