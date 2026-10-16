<?php

declare(strict_types=1);

/**
 * Local/testing only: ensure a published QA fixture job exists for
 * JRN-CANDIDATE-APPLY-01 (idempotent; never deletes applications).
 *
 * Uses slug qa-fixture-journey-apply, then -2, -3, … until an unapplied job exists or is created.
 */

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Enums\JobPublishingStatus;
use App\Models\Application;
use App\Models\Job;
use App\Models\User;
use App\Support\QaHiringFixtures;

if ($app->environment('production')) {
    fwrite(STDERR, "Refusing to run in production.\n");
    exit(1);
}

if (! $app->environment(['local', 'testing'])) {
    fwrite(STDERR, "Restricted to local/testing environments.\n");
    exit(1);
}

$candidate = User::query()->where('email', QaHiringFixtures::CANDIDATE_EMAIL)->first();
$employerA = User::query()->where('email', QaHiringFixtures::EMPLOYER_A_EMAIL)->first();

if ($candidate === null || $employerA === null || $employerA->employerProfile === null) {
    fwrite(STDERR, "Run php artisan qa:hiring-fixtures first.\n");
    exit(1);
}

$profile = $employerA->employerProfile;
$created = 0;
$futureClosing = now()->addDays(45)->toDateString();

$maxSuffix = (int) Job::query()
    ->where('slug', 'like', 'qa-fixture-journey-apply%')
    ->get(['slug'])
    ->map(function (Job $job): int {
        if ($job->slug === 'qa-fixture-journey-apply') {
            return 1;
        }
        if (preg_match('/^qa-fixture-journey-apply-(\d+)$/', $job->slug, $matches) === 1) {
            return (int) $matches[1];
        }

        return 0;
    })
    ->max();
$limit = max(60, $maxSuffix + 5);

for ($n = 1; $n <= $limit; $n++) {
    $slug = $n === 1 ? 'qa-fixture-journey-apply' : 'qa-fixture-journey-apply-'.$n;
    $title = $n === 1
        ? 'QA Fixture Journey Apply Role (Employer A)'
        : 'QA Fixture Journey Apply Role (Employer A) '.$n;

    $job = Job::query()->where('slug', $slug)->first();

    if ($job === null) {
        $job = Job::factory()->create([
            'employer_id' => $profile->id,
            'title' => $title,
            'project' => QaHiringFixtures::COMPANY_A,
            'slug' => $slug,
            'publishing_status' => JobPublishingStatus::Published,
            'active' => true,
            'posting_tier' => 'basic',
            'trial_expires_at' => null,
            'closing_date' => $futureClosing,
        ]);
        $created = 1;
    } else {
        $job->forceFill([
            'employer_id' => $profile->id,
            'title' => $title,
            'publishing_status' => JobPublishingStatus::Published,
            'active' => true,
            'posting_tier' => 'basic',
            'trial_expires_at' => null,
            'closing_date' => $futureClosing,
        ])->save();
    }

    $alreadyApplied = Application::query()
        ->where('user_id', $candidate->id)
        ->where('job_id', $job->id)
        ->exists();

    if (! $alreadyApplied && $job->acceptsApplications()) {
        echo 'created='.$created."\n";
        echo 'slug='.$slug."\n";
        echo 'title='.$title."\n";
        echo 'job_id='.$job->id."\n";
        echo "eligible=yes\n";
        exit(0);
    }
}

fwrite(STDERR, "Could not find or create an unapplied journey apply job (limit {$limit} slugs).\n");
exit(1);
