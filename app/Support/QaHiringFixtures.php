<?php

namespace App\Support;

use App\Enums\ApplicationStatus;
use App\Enums\EmployerNotificationEvent;
use App\Enums\FileLifecycleStatus;
use App\Enums\JobPublishingStatus;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\CandidateProfile;
use App\Models\EmployerProfile;
use App\Models\Job;
use App\Models\User;
use App\Services\ApplicationSubmissionService;
use App\Services\CandidateResumeLibraryService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * Deterministic local/testing hiring QA dataset (see artisan qa:hiring-fixtures).
 */
final class QaHiringFixtures
{
    public const EMPLOYER_A_EMAIL = 'qa-employer-a@example.test';

    public const EMPLOYER_B_EMAIL = 'qa-employer-b@example.test';

    public const CANDIDATE_EMAIL = 'qa-candidate@example.test';

    /** Documented local QA password (command output only; not used outside local/testing). */
    public const PASSWORD = 'Password1!';

    public const SLUG_PUBLISHED_A = 'qa-fixture-published-a';

    public const SLUG_DRAFT_A = 'qa-fixture-draft-a';

    public const SLUG_PUBLISHED_B = 'qa-fixture-published-b';

    public const TITLE_PUBLISHED_A = 'QA Fixture Published Role (Employer A)';

    public const TITLE_DRAFT_A = 'QA Fixture Draft Role (Employer A)';

    public const TITLE_PUBLISHED_B = 'QA Fixture Published Role (Employer B)';

    public const COMPANY_A = 'QA Hiring Co A';

    public const COMPANY_B = 'QA Hiring Co B';

    /** Extra published roles for Employer A so public /jobs pagination can be verified under one company label. */
    public const PUBLISHED_A_PAGINATION_JOBS = 15;

    /**
     * @return array<string, mixed>
     */
    public function provision(): array
    {
        $payload = DB::transaction(function () {
            $employerA = $this->ensureEmployer(
                self::EMPLOYER_A_EMAIL,
                'qaemployerA',
                'QA Employer A',
                self::COMPANY_A,
            );
            $employerB = $this->ensureEmployer(
                self::EMPLOYER_B_EMAIL,
                'qaemployerB',
                'QA Employer B',
                self::COMPANY_B,
            );
            $candidate = $this->ensureCandidate();

            $publishedA = $this->ensureJob(
                $employerA->employerProfile,
                self::SLUG_PUBLISHED_A,
                self::TITLE_PUBLISHED_A,
                published: true,
            );
            $draftA = $this->ensureJob(
                $employerA->employerProfile,
                self::SLUG_DRAFT_A,
                self::TITLE_DRAFT_A,
                published: false,
            );
            $publishedB = $this->ensureJob(
                $employerB->employerProfile,
                self::SLUG_PUBLISHED_B,
                self::TITLE_PUBLISHED_B,
                published: true,
            );

            for ($index = 1; $index <= self::PUBLISHED_A_PAGINATION_JOBS; $index++) {
                $suffix = str_pad((string) $index, 2, '0', STR_PAD_LEFT);
                $this->ensureJob(
                    $employerA->employerProfile,
                    'qa-fixture-published-a-'.$suffix,
                    'QA Fixture Published Role (Employer A) '.$suffix,
                    published: true,
                );
            }

            $this->ensureCandidateResume($candidate);
            $application = $this->ensureApplication($candidate, $publishedA);

            return compact(
                'employerA',
                'employerB',
                'candidate',
                'publishedA',
                'draftA',
                'publishedB',
                'application',
            );
        });

        $this->verify(
            $payload['employerA'],
            $payload['employerB'],
            $payload['candidate'],
            $payload['publishedA'],
            $payload['draftA'],
            $payload['publishedB'],
            $payload['application'],
        );

        $employerA = $payload['employerA'];
        $application = $payload['application'];

        $notification = $employerA->fresh()->notifications()
            ->where('data->event', EmployerNotificationEvent::ApplicationReceived->value)
            ->where('data->context->application_id', $application->id)
            ->first();

        return [
            'employer_a' => $employerA->fresh(['employerProfile']),
            'employer_b' => $payload['employerB']->fresh(['employerProfile']),
            'candidate' => $payload['candidate']->fresh(['candidateProfile']),
            'published_job_a' => $payload['publishedA']->fresh(),
            'draft_job_a' => $payload['draftA']->fresh(),
            'published_job_b' => $payload['publishedB']->fresh(),
            'application' => $application->fresh(['job.employer']),
            'notification_created' => $notification !== null,
            'notification_recipient' => $employerA->email,
        ];
    }

    private function ensureEmployer(string $email, string $username, string $name, string $company): User
    {
        $user = User::query()->where('email', $email)->first();
        if (! $user) {
            $user = User::factory()->employer()->create([
                'email' => $email,
                'username' => $username,
                'name' => $name,
                'password' => Hash::make(self::PASSWORD),
                'email_verified_at' => now(),
                'mobile' => '+910000000001',
                'mobile_verified_at' => now(),
            ]);
        } else {
            $user->forceFill([
                'role' => UserRole::Employer,
                'username' => $username,
                'name' => $name,
                'password' => Hash::make(self::PASSWORD),
                'email_verified_at' => $user->email_verified_at ?? now(),
                'mobile' => $user->mobile ?: '+910000000001',
                'mobile_verified_at' => $user->mobile_verified_at ?? now(),
            ])->save();
        }

        $profile = $user->employerProfile ?? EmployerProfile::query()->create(['user_id' => $user->id]);
        $profile->forceFill([
            'company_name' => $company,
            'contact_person' => $name,
        ])->save();

        return $user->fresh(['employerProfile']);
    }

    private function ensureCandidate(): User
    {
        $user = User::query()->where('email', self::CANDIDATE_EMAIL)->first();
        if (! $user) {
            $user = User::factory()->candidate()->create([
                'email' => self::CANDIDATE_EMAIL,
                'username' => 'qacandidate',
                'name' => 'QA Candidate',
                'password' => Hash::make(self::PASSWORD),
                'email_verified_at' => now(),
                'mobile' => '+910000000002',
                'mobile_verified_at' => now(),
            ]);
        } else {
            $user->forceFill([
                'role' => UserRole::Candidate,
                'username' => 'qacandidate',
                'name' => 'QA Candidate',
                'password' => Hash::make(self::PASSWORD),
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();
        }

        if ($user->candidateProfile === null) {
            CandidateProfile::query()->create(['user_id' => $user->id]);
        }

        return $user->fresh(['candidateProfile']);
    }

    private function ensureJob(EmployerProfile $profile, string $slug, string $title, bool $published): Job
    {
        $attributes = [
            'employer_id' => $profile->id,
            'title' => $title,
            'project' => $profile->displayCompany(),
            'slug' => $slug,
            'publishing_status' => $published ? JobPublishingStatus::Published : JobPublishingStatus::Draft,
            'active' => $published,
        ];

        $job = Job::query()->where('slug', $slug)->first();
        if ($job) {
            $job->forceFill($attributes)->save();

            return $job;
        }

        $factory = $published ? Job::factory() : Job::factory()->draft();

        return $factory->create(array_merge($attributes, [
            'active' => $published,
        ]));
    }

    private function ensureCandidateResume(User $candidate): void
    {
        $candidate->loadMissing('candidateProfile');
        $profile = $candidate->candidateProfile;
        if ($profile === null) {
            throw new RuntimeException('QA candidate profile missing.');
        }

        if ($profile->resumes()->where('status', FileLifecycleStatus::Active)->exists()) {
            return;
        }

        $file = UploadedFile::fake()->createWithContent(
            'qa-fixture-resume.pdf',
            ReadablePdfFixture::minimalOnePage(),
        );

        app(CandidateResumeLibraryService::class)->upload($profile, $file, [
            'title' => 'QA Fixture CV',
        ], $candidate);
    }

    private function ensureApplication(User $candidate, Job $job): Application
    {
        $existing = Application::query()
            ->where('job_id', $job->id)
            ->where('user_id', $candidate->id)
            ->first();

        if ($existing instanceof Application) {
            return $existing;
        }

        $guard = Auth::guard('account');
        $previous = $guard->user();
        $guard->login($candidate);

        try {
            return app(ApplicationSubmissionService::class)->submitFromProfile($job, $candidate);
        } finally {
            if ($previous instanceof User) {
                $guard->login($previous);
            } else {
                $guard->logout();
            }
        }
    }

    private function verify(
        User $employerA,
        User $employerB,
        User $candidate,
        Job $publishedA,
        Job $draftA,
        Job $publishedB,
        Application $application,
    ): void {
        $profileA = $employerA->employerProfile;
        $profileB = $employerB->employerProfile;

        if ($profileA === null || $profileB === null) {
            throw new RuntimeException('QA employer profiles missing.');
        }

        if ((int) $publishedA->employer_id !== (int) $profileA->id) {
            throw new RuntimeException('Published job A employer mismatch.');
        }
        if ((int) $draftA->employer_id !== (int) $profileA->id) {
            throw new RuntimeException('Draft job A employer mismatch.');
        }
        if ((int) $publishedB->employer_id !== (int) $profileB->id) {
            throw new RuntimeException('Published job B employer mismatch.');
        }
        if ((int) $application->user_id !== (int) $candidate->id) {
            throw new RuntimeException('Application candidate mismatch.');
        }
        if ((int) $application->job_id !== (int) $publishedA->id) {
            throw new RuntimeException('Application job mismatch.');
        }
        // QA lifecycle may advance the fixture application during manual audits; associations still must hold.
        if (trim((string) $application->resume_path) === '') {
            throw new RuntimeException('Application resume snapshot missing.');
        }

        $employerBNotifications = $employerB->fresh()->notifications()
            ->where('data->event', EmployerNotificationEvent::ApplicationReceived->value)
            ->where('data->context->application_id', $application->id)
            ->count();

        if ($employerBNotifications !== 0) {
            throw new RuntimeException('Employer B incorrectly received Employer A application notification.');
        }

        $employerANotifications = $employerA->fresh()->notifications()
            ->where('data->event', EmployerNotificationEvent::ApplicationReceived->value)
            ->where('data->context->application_id', $application->id)
            ->count();

        if ($employerANotifications < 1) {
            throw new RuntimeException('Employer A application notification missing.');
        }
    }

    /**
     * @return list<string>
     */
    public static function qaEmails(): array
    {
        return [
            self::EMPLOYER_A_EMAIL,
            self::EMPLOYER_B_EMAIL,
            self::CANDIDATE_EMAIL,
        ];
    }

    /**
     * @return list<string>
     */
    public static function qaJobSlugs(): array
    {
        return [
            self::SLUG_PUBLISHED_A,
            self::SLUG_DRAFT_A,
            self::SLUG_PUBLISHED_B,
        ];
    }
}
