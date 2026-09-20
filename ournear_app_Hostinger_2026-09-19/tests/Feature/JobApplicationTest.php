<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\Job;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class JobApplicationTest extends TestCase
{
    public function test_guest_can_submit_an_application(): void
    {
        $job = Job::factory()->create();

        $response = $this->post(route('jobs.apply.store', $job), $this->validPayload('guest@example.com'));

        $response->assertRedirect(route('jobs.apply', $job));
        $response->assertSessionHas('success');

        $application = Application::query()->where('email', 'guest@example.com')->first();
        $this->assertNotNull($application);
        $this->assertSame($job->id, $application->job_id);
        $this->assertNull($application->user_id);
        $this->assertSame(ApplicationStatus::New, $application->status);
        $this->assertSame('Guest Applicant', $application->full_name);
        Storage::disk('resumes')->assertExists($application->getAttributes()['resume_path']);
    }

    public function test_logged_in_candidate_application_is_linked_to_the_account(): void
    {
        $candidate = $this->makeCandidate(['email' => 'linked@example.com']);
        $job = Job::factory()->create();

        $this->actingAs($candidate)
            ->post(route('jobs.apply.store', $job), $this->validPayload('linked@example.com', 'Linked Candidate'))
            ->assertRedirect(route('jobs.apply', $job));

        $application = Application::query()->where('email', 'linked@example.com')->first();
        $this->assertNotNull($application);
        $this->assertSame($candidate->id, $application->user_id);
        $this->assertSame(ApplicationStatus::New, $application->status);
    }

    public function test_duplicate_email_cannot_apply_to_the_same_job(): void
    {
        $job = Job::factory()->create();
        Application::factory()->create([
            'job_id' => $job->id,
            'email' => 'repeat@example.com',
        ]);

        $this->from(route('jobs.apply', $job))
            ->post(route('jobs.apply.store', $job), $this->validPayload('repeat@example.com'))
            ->assertSessionHasErrors('email');

        $this->assertSame(1, Application::query()->where('job_id', $job->id)->count());
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(string $email, string $name = 'Guest Applicant'): array
    {
        return [
            'full_name' => $name,
            'mobile' => '07700900123',
            'email' => $email,
            'current_location' => 'Manchester',
            'experience' => '3-5 years',
            'qualification' => 'Degree',
            'resume' => $this->fakeResume(),
            'additional_information' => 'Available immediately.',
            'consent' => '1',
        ];
    }
}
