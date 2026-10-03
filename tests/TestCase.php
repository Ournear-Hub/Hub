<?php

namespace Tests;

use App\Contracts\SmsOtpGateway;
use App\Contracts\WhatsAppGateway;
use App\Models\Application;
use App\Models\CandidateResume;
use App\Models\Job;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\RecordingSmsOtpGateway;
use Tests\Support\RecordingWhatsAppGateway;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected RecordingSmsOtpGateway $smsOtpGateway;

    protected RecordingWhatsAppGateway $whatsAppGateway;

    protected function setUp(): void
    {
        parent::setUp();

        $this->smsOtpGateway = new RecordingSmsOtpGateway();
        $this->app->instance(SmsOtpGateway::class, $this->smsOtpGateway);

        $this->whatsAppGateway = new RecordingWhatsAppGateway();
        $this->app->instance(WhatsAppGateway::class, $this->whatsAppGateway);

        $this->withoutVite();
        Storage::fake('resumes');
        Storage::fake('candidate_files');
        Storage::fake('employer_files');
        Storage::fake('freelancer_files');
        Storage::fake('public');
    }

    public function actingAs(Authenticatable $user, $guard = null)
    {
        if ($guard === null && $user instanceof User) {
            $guard = $user->isAdmin() ? 'admin' : 'account';
        }

        if ($guard === 'admin') {
            $this->app['auth']->guard('account')->logout();
        } elseif ($guard === 'account') {
            $this->app['auth']->guard('admin')->logout();
        }

        return parent::actingAs($user, $guard);
    }

    public function assertAuthenticatedAs($user, $guard = null)
    {
        if ($guard === null && $user instanceof User) {
            $guard = $user->isAdmin() ? 'admin' : 'account';
        }

        return parent::assertAuthenticatedAs($user, $guard);
    }

    protected function forgetAuthentication(): void
    {
        $this->app['auth']->forgetGuards();
        $this->flushSession();
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

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function registrationPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Registration User',
            'username' => 'reguser'.fake()->unique()->numerify('####'),
            'email' => fake()->unique()->safeEmail(),
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'role' => 'candidate',
            'accept_terms' => true,
        ], $overrides);
    }
}
