<?php

declare(strict_types=1);

/**
 * Local/testing: count applications for QA candidate on a job slug.
 *
 * Usage: php scripts/qa-application-count.php <job-slug>
 */

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Application;
use App\Models\Job;
use App\Models\User;
use App\Support\QaHiringFixtures;

if ($app->environment('production')) {
    fwrite(STDERR, "Refusing to run in production.\n");
    exit(1);
}

$slug = $argv[1] ?? '';
if ($slug === '') {
    fwrite(STDERR, "Usage: php scripts/qa-application-count.php <job-slug>\n");
    exit(1);
}

$candidate = User::query()->where('email', QaHiringFixtures::CANDIDATE_EMAIL)->first();
$job = Job::query()->where('slug', $slug)->first();

if ($candidate === null) {
    fwrite(STDERR, "QA candidate missing — run php artisan qa:hiring-fixtures.\n");
    exit(1);
}

if ($job === null) {
    fwrite(STDERR, "Job not found for slug={$slug}\n");
    exit(1);
}

$count = Application::query()
    ->where('user_id', $candidate->id)
    ->where('job_id', $job->id)
    ->count();

echo 'slug='.$slug.PHP_EOL;
echo 'job_id='.$job->id.PHP_EOL;
echo 'candidate_id='.$candidate->id.PHP_EOL;
echo 'application_count='.$count.PHP_EOL;
