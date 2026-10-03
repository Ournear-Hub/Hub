<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\CandidateResume;
use App\Models\Job;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class JobApplicationTest extends TestCase
{
    public function test_guest_apply_redirects_to_login(): void
    {
        $job = Job::factory()->create();

        $this->get(route('jobs.apply', $job))->assertRedirect(route('login'));
        $this->post(route('jobs.apply.store', $job))->assertRedirect(route('login'));
        $this->assertSame(0, Application::query()->count());
    }

    public function test_logged_in_candidate_with_resume_applies_from_profile(): void
    {
        $candidate = $this->makeCandidate(['email' => 'linked@example.com', 'name' => 'Linked Candidate']);
        $job = Job::factory()->create();
        $this->giveCandidateResume($candidate);

        $this->actingAs($candidate)
            ->post(route('jobs.apply.store', $job))
            ->assertRedirect(route('jobs.show', $job));

        $application = Application::query()->where('email', 'linked@example.com')->first();
        $this->assertNotNull($application);
        $this->assertSame($candidate->id, $application->user_id);
        $this->assertSame(ApplicationStatus::New, $application->status);
        $this->assertSame('Linked Candidate', $application->full_name);
        Storage::disk('resumes')->assertExists($application->getAttributes()['resume_path']);
    }

    public function test_duplicate_candidate_cannot_apply_to_the_same_job(): void
    {
        $candidate = $this->makeCandidate(['email' => 'repeat@example.com']);
        $job = Job::factory()->create();
        $this->applyAsCandidate($candidate, $job);

        $this->actingAs($candidate)
            ->from(route('jobs.show', $job))
            ->post(route('jobs.apply.store', $job))
            ->assertRedirect(route('jobs.show', $job))
            ->assertSessionHasErrors('job');

        $this->assertSame(1, Application::query()->where('job_id', $job->id)->count());
    }

    public function test_no_resume_apply_page_wires_upload_cta_to_the_file_picker(): void
    {
        $candidate = $this->makeCandidate();
        $job = Job::factory()->create();

        $this->actingAs($candidate)
            ->get(route('jobs.apply', $job))
            ->assertOk();

        $source = file_get_contents(resource_path('js/Pages/Jobs/Apply.tsx'));
        $this->assertIsString($source);
        $this->assertStringContainsString('ref={fileInputRef}', $source);
        $this->assertStringContainsString("type={form.data.file ? 'submit' : 'button'}", $source);
        $this->assertStringContainsString('fileInputRef.current?.click()', $source);
        $this->assertStringContainsString('type="file"', $source);
        $this->assertStringContainsString('.pdf,.doc,.docx', $source);
    }

    public function test_job_resume_upload_rejects_invalid_and_unauthorized_files(): void
    {
        $candidate = $this->makeCandidate();
        $job = Job::factory()->create();

        $this->actingAs($candidate)
            ->from(route('jobs.apply', $job))
            ->post(route('jobs.apply.resume', $job), [
                'file' => $this->fakeResume('not-a-resume.txt'),
            ])
            ->assertRedirect(route('jobs.apply', $job))
            ->assertSessionHasErrors('file');

        $this->assertSame(0, CandidateResume::query()->count());

        $this->actingAs($this->makeEmployer())
            ->post(route('jobs.apply.resume', $job), [
                'file' => $this->fakeResume('employer.pdf'),
            ])
            ->assertForbidden();

        $this->assertSame(0, CandidateResume::query()->count());
    }
}
