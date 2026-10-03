<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\Job;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CandidateAccountTest extends TestCase
{
    public function test_candidate_can_access_account_home_and_applications(): void
    {
        $candidate = $this->makeCandidate();
        $application = Application::factory()->create([
            'user_id' => $candidate->id,
            'full_name' => $candidate->name,
            'email' => $candidate->email,
        ]);

        $this->actingAs($candidate)
            ->get(route('account.home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Home')
                ->where('role', 'candidate'));

        $this->actingAs($candidate)
            ->get(route('account.applications.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Applications')
                ->has('applications.data', 1)
                ->where('applications.data.0.id', $application->id));
    }

    public function test_candidate_can_download_and_replace_own_resume(): void
    {
        $candidate = $this->makeCandidate();
        $path = 'original-resume.pdf';
        Storage::disk('resumes')->put($path, 'original');

        $application = Application::factory()->create([
            'user_id' => $candidate->id,
            'email' => $candidate->email,
            'resume_path' => $path,
        ]);

        $this->actingAs($candidate)
            ->get(route('account.applications.resume', $application))
            ->assertOk();

        $this->actingAs($candidate)
            ->post(route('account.applications.resume.replace', $application), [
                'resume' => $this->fakeResume('updated.pdf'),
            ])
            ->assertRedirect();

        $application->refresh();
        $this->assertNotSame($path, $application->getAttributes()['resume_path']);
        Storage::disk('resumes')->assertExists($application->getAttributes()['resume_path']);
        Storage::disk('resumes')->assertMissing($path);
    }

    public function test_candidate_can_withdraw_an_open_application(): void
    {
        $candidate = $this->makeCandidate();
        $application = Application::factory()->create([
            'user_id' => $candidate->id,
            'email' => $candidate->email,
            'status' => ApplicationStatus::New,
        ]);

        $this->actingAs($candidate)
            ->patch(route('account.applications.withdraw', $application))
            ->assertRedirect();

        $this->assertSame(ApplicationStatus::Withdrawn, $application->fresh()->status);
    }

    public function test_candidate_cannot_withdraw_a_placed_application(): void
    {
        $candidate = $this->makeCandidate();
        $application = Application::factory()->create([
            'user_id' => $candidate->id,
            'email' => $candidate->email,
            'status' => ApplicationStatus::Placed,
        ]);

        $this->actingAs($candidate)
            ->from(route('account.applications.index'))
            ->patch(route('account.applications.withdraw', $application))
            ->assertSessionHasErrors('application');

        $this->assertSame(ApplicationStatus::Placed, $application->fresh()->status);
    }

    public function test_candidate_cannot_access_another_candidates_resume(): void
    {
        $owner = $this->makeCandidate();
        $other = $this->makeCandidate();
        $application = Application::factory()->for(Job::factory())->create([
            'user_id' => $owner->id,
            'email' => $owner->email,
        ]);

        $this->actingAs($other)
            ->get(route('account.applications.resume', $application))
            ->assertForbidden();
    }

    public function test_employer_cannot_access_candidate_applications(): void
    {
        $employer = $this->makeEmployer();

        $this->actingAs($employer)
            ->get(route('account.applications.index'))
            ->assertForbidden();
    }
}
