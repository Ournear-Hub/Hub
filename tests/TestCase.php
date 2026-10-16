<?php

namespace Tests;

use App\Contracts\SmsOtpGateway;
use App\Contracts\WhatsAppGateway;
use App\Models\Application;
use App\Models\CandidateResume;
use App\Models\Industry;
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

    /** @var array<string, string>|null */
    protected ?array $storageFakeRoots = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->smsOtpGateway = new RecordingSmsOtpGateway();
        $this->app->instance(SmsOtpGateway::class, $this->smsOtpGateway);

        $this->whatsAppGateway = new RecordingWhatsAppGateway();
        $this->app->instance(WhatsAppGateway::class, $this->whatsAppGateway);

        $this->withoutVite();
        $this->configureStorageFakes(resetDirectories: true);

        $this->app->terminating(function (): void {
            $this->configureStorageFakes(resetDirectories: false);
        });
    }

    public function call($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        $this->configureStorageFakes(resetDirectories: false);

        return tap(parent::call($method, $uri, $parameters, $cookies, $files, $server, $content), function () {
            $this->configureStorageFakes(resetDirectories: false);
        });
    }

    protected function configureStorageFakes(bool $resetDirectories = false): void
    {
        $disks = ['resumes', 'candidate_files', 'employer_files', 'freelancer_files', 'public'];
        $filesystem = new \Illuminate\Filesystem\Filesystem;

        if ($resetDirectories || $this->storageFakeRoots === null) {
            $this->storageFakeRoots = [];
            foreach ($disks as $disk) {
                $this->storageFakeRoots[$disk] = storage_path(
                    'framework/testing/disks/'.$disk.'/'.str_replace('.', '', uniqid('', true))
                );
            }
        }

        foreach ($disks as $disk) {
            $root = $this->storageFakeRoots[$disk];
            if (! is_dir($root)) {
                $filesystem->makeDirectory($root, 0755, true);
            }

            Storage::persistentFake($disk, ['root' => $root]);
        }
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

    protected function industryId(): int
    {
        $existing = Industry::query()->where('is_active', true)->orderBy('id')->value('id');
        if ($existing !== null) {
            return (int) $existing;
        }

        return Industry::query()->create([
            'name' => 'General',
            'is_active' => true,
            'sort_order' => 1,
        ])->id;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function hiringActivationPayload(array $overrides = []): array
    {
        return array_merge([
            'company_name' => 'Test Co',
            'company_phone' => '+919876543210',
            'company_email' => 'employer@example.test',
            'industry_id' => $this->industryId(),
        ], $overrides);
    }

    protected function fakeResume(string $name = 'resume.pdf'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $name,
            \App\Support\ReadablePdfFixture::minimalOnePage(),
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

        $this->actingAs($candidate)
            ->post(route('jobs.apply.store', $job))
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors();

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
            'mobile' => '+97150'.fake()->unique()->numerify('######'),
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'accept_terms' => true,
        ], $overrides);
    }
}
