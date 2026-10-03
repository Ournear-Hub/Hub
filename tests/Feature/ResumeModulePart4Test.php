<?php

namespace Tests\Feature;

use App\Enums\FileLifecycleStatus;
use App\Models\Application;
use App\Models\CandidateResume;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ResumeModulePart4Test extends TestCase
{
    public function test_second_personal_upload_replaces_the_single_active_resume(): void
    {
        $candidate = $this->makeCandidate();

        $this->actingAs($candidate)->post(route('account.resumes.store'), [
            'file' => $this->fakeResume('first.pdf'),
            'title' => 'First CV',
        ])->assertRedirect();

        $this->actingAs($candidate)->post(route('account.resumes.store'), [
            'file' => $this->fakeResume('second.pdf'),
            'title' => 'Second CV',
        ])->assertRedirect();

        $count = CandidateResume::query()
            ->where('candidate_profile_id', $candidate->candidateProfile?->id)
            ->where('status', FileLifecycleStatus::Active)
            ->count();

        $this->assertSame(1, $count);
        $this->assertSame('second.pdf', CandidateResume::query()->value('original_filename'));
    }

    public function test_jobs_apply_page_renders_and_upload_enables_apply(): void
    {
        $candidate = $this->makeCandidate();
        $job = \App\Models\Job::factory()->create();

        $this->actingAs($candidate)
            ->get(route('jobs.apply', $job))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Jobs/Apply')
                ->where('needs_resume', true));

        $this->actingAs($candidate)
            ->post(route('jobs.apply.resume', $job), [
                'file' => $this->fakeResume('apply-flow.pdf'),
                'title' => 'Apply CV',
            ])
            ->assertRedirect(route('jobs.apply', $job));

        $this->assertSame(1, CandidateResume::query()->count());

        $this->actingAs($candidate)
            ->get(route('jobs.show', $job))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('job.apply_state', 'apply'));
    }

    public function test_application_snapshot_stays_independent_from_library_replace(): void
    {
        $candidate = $this->makeCandidate();
        $this->giveCandidateResume($candidate, 'library.pdf');
        $resume = CandidateResume::query()->first();
        $this->assertNotNull($resume);

        $job = \App\Models\Job::factory()->create();
        $this->actingAs($candidate)->post(route('jobs.apply.store', $job))->assertRedirect();

        $application = Application::query()->where('user_id', $candidate->id)->first();
        $this->assertNotNull($application);
        $snapshot = $application->getAttributes()['resume_path'];
        $this->assertNotSame($resume->storage_path, $snapshot);
        Storage::disk('resumes')->assertExists($snapshot);

        $this->actingAs($candidate)->post(route('account.resumes.replace', $resume), [
            'file' => $this->fakeResume('library-replaced.pdf'),
        ])->assertRedirect();

        $this->assertSame($snapshot, $application->fresh()->getAttributes()['resume_path']);
    }
}
