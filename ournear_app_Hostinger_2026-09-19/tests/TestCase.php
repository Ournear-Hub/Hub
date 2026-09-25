<?php

namespace Tests;

use App\Models\Application;
use App\Models\CandidateResume;
use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Storage::fake('resumes');
        Storage::fake('candidate_files');
        Storage::fake('freelancer_files');
        Storage::fake('public');
    }

    protected function makeAdmin(array $overrides = []): User
    {
        return User::factory()->create($overrides);
    }

    protected function makeStaff(string $roleSlug, array $overrides = []): User
    {
        return User::factory()->staffRole($roleSlug)->create($overrides);
    }

    protected function makeCandidate(array $overrides = []): User
    {
        return User::factory()->candidate()->create($overrides);
    }

    protected function makeEmployer(array $overrides = []): User
    {
        return User::factory()->employer()->create($overrides);
    }

    protected function fakeResume(string $name = 'resume.pdf'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $name,
            "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n%%EOF\n"
        );
    }

    protected function fakeDocument(string $name = 'document.pdf'): UploadedFile
    {
        return $this->fakeResume($name);
    }

    protected function giveCandidateResume(User $candidate, string $name = 'resume.pdf'): CandidateResume
    {
        $this->actingAs($candidate)->post(route('account.resumes.store'), [
            'file' => $this->fakeResume($name),
            'title' => 'Primary CV',
        ])->assertRedirect();

        $resume = CandidateResume::query()
            ->where('candidate_profile_id', $candidate->fresh()->candidateProfile?->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($resume);

        return $resume;
    }

    protected function applyAsCandidate(User $candidate, Job $job): Application
    {
        if (! $this->candidateHasResume($candidate)) {
            $this->giveCandidateResume($candidate);
        }

        $this->actingAs($candidate)->post(route('jobs.apply.store', $job))->assertRedirect();

        $application = Application::query()
            ->where('user_id', $candidate->id)
            ->where('job_id', $job->id)
            ->first();

        $this->assertNotNull($application);

        return $application;
    }

    protected function candidateHasResume(User $candidate): bool
    {
        $candidate->loadMissing('candidateProfile');

        return $candidate->candidateProfile?->resumes()->exists() === true;
    }
}
