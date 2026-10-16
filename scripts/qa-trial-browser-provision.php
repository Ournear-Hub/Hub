<?php

declare(strict_types=1);

/**
 * Local QA harness for employer trial browser acceptance (2026-10-13).
 *
 * Usage:
 *   php scripts/qa-trial-browser-provision.php prepare
 *   php scripts/qa-trial-browser-provision.php inspect
 *   php scripts/qa-trial-browser-provision.php expire <job-slug>
 */

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Enums\UserRole;
use App\Models\EmployerProfile;
use App\Models\Job;
use App\Models\User;
use App\Support\QaHiringFixtures;
use Illuminate\Support\Facades\Hash;

const QA_TRIAL_BROWSER_EMAIL = 'qa-trial-browser@example.test';

function qa_trial_browser_user(bool $createIfMissing = true): ?User
{
    $user = User::query()->where('email', QA_TRIAL_BROWSER_EMAIL)->first();
    if ($user === null && $createIfMissing) {
        $user = User::factory()->employer()->create([
            'email' => QA_TRIAL_BROWSER_EMAIL,
            'username' => 'qatrialbrowser',
            'name' => 'QA Trial Browser',
            'password' => Hash::make(QaHiringFixtures::PASSWORD),
            'email_verified_at' => null,
            'mobile_verified_at' => null,
            'mobile' => null,
            'hiring_email' => null,
        ]);
    }

    return $user;
}

function qa_trial_browser_prepare(): void
{
    if (! in_array(app()->environment(), ['local', 'testing'], true)) {
        fwrite(STDERR, "Refusing to run outside local/testing.\n");
        exit(2);
    }

    $user = qa_trial_browser_user(true);
    if (! $user instanceof User) {
        fwrite(STDERR, "Could not create QA trial browser user.\n");
        exit(2);
    }

    $user->forceFill([
        'role' => UserRole::Employer,
        'password' => Hash::make(QaHiringFixtures::PASSWORD),
        'email_verified_at' => null,
        'mobile_verified_at' => null,
        'hiring_email' => null,
    ])->save();

    $profile = $user->employerProfile;
    if (! $profile instanceof EmployerProfile) {
        $profile = EmployerProfile::query()->create([
            'user_id' => $user->id,
            'company_name' => 'QA Trial Browser Co',
            'contact_person' => $user->name,
        ]);
    }

    $profile->forceFill([
        'company_name' => 'QA Trial Browser Co',
        'mobile_number' => null,
        'trial_job_used_at' => null,
        'job_posting_approved_at' => null,
        'job_posting_approved_by' => null,
    ])->save();

    Job::query()->where('employer_id', $profile->id)->delete();

    echo 'email='.QA_TRIAL_BROWSER_EMAIL."\n";
    echo 'password='.QaHiringFixtures::PASSWORD."\n";
    echo 'workspace_ready=no'."\n";
    echo 'trial_job_used_at=null'."\n";
}

function qa_trial_browser_inspect(): void
{
    $user = qa_trial_browser_user(false);
    if (! $user instanceof User) {
        echo "status=missing_user\n";
        exit(0);
    }

    $user->load('employerProfile');
    $profile = $user->employerProfile;
    $eligibility = app(\App\Services\EmployerPostingEligibilityService::class)->for($user);

    echo 'email='.$user->email."\n";
    echo 'email_verified='.($user->emailVerified() ? 'yes' : 'no')."\n";
    echo 'mobile_verified='.($user->mobileVerified() ? 'yes' : 'no')."\n";
    echo 'hiring_email='.($user->hiringEmail() ?: 'null')."\n";
    echo 'trial_job_used_at='.($profile?->trial_job_used_at?->toIso8601String() ?? 'null')."\n";
    echo 'workspace_ready='.($eligibility['workspace_ready'] ? 'yes' : 'no')."\n";
    echo 'trial_available='.($eligibility['trial_available'] ? 'yes' : 'no')."\n";
    echo 'eligible='.($eligibility['eligible'] ? 'yes' : 'no')."\n";

    if ($profile instanceof EmployerProfile) {
        $jobs = Job::query()->where('employer_id', $profile->id)->orderBy('id')->get();
        foreach ($jobs as $job) {
            echo 'job id='.$job->id
                .' slug='.$job->slug
                .' title='.str_replace(["\n", "\r"], ' ', (string) $job->title)
                .' status='.$job->publishing_status->value
                .' tier='.(string) $job->posting_tier
                .' trial_expires='.($job->trial_expires_at?->toIso8601String() ?? 'null')
                .' public='.($job->isPubliclyVisible() ? 'yes' : 'no')
                ."\n";
        }
    }
}

$action = $argv[1] ?? 'prepare';

if ($action === 'prepare') {
    qa_trial_browser_prepare();
    exit(0);
}

if ($action === 'inspect') {
    qa_trial_browser_inspect();
    exit(0);
}

if ($action === 'expire') {
    $slug = $argv[2] ?? '';
    if ($slug === '') {
        fwrite(STDERR, "Usage: php scripts/qa-trial-browser-provision.php expire <slug>\n");
        exit(1);
    }
    $job = Job::query()->where('slug', $slug)->first();
    if ($job === null) {
        fwrite(STDERR, "Job not found: {$slug}\n");
        exit(3);
    }
    $job->forceFill(['trial_expires_at' => now()->subMinutes(5)])->save();
    echo 'expired_slug='.$slug."\n";
    echo 'public_visible='.($job->fresh()->isPubliclyVisible() ? 'yes' : 'no')."\n";
    exit(0);
}

fwrite(STDERR, "Unknown action: {$action}\n");
exit(1);
