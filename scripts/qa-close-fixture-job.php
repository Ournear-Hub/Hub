<?php

declare(strict_types=1);

/**
 * Local/testing: close a QA fixture job by setting closing_date in the past.
 * Only slugs prefixed qa-fixture- are allowed.
 *
 * Usage: php scripts/qa-close-fixture-job.php <job-slug>
 */

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Job;

if ($app->environment('production')) {
    fwrite(STDERR, "Refusing to run in production.\n");
    exit(1);
}

$slug = $argv[1] ?? '';
if ($slug === '' || ! str_starts_with($slug, 'qa-fixture-')) {
    fwrite(STDERR, "Refusing: slug must start with qa-fixture-\n");
    exit(1);
}

$job = Job::query()->where('slug', $slug)->first();
if ($job === null) {
    fwrite(STDERR, "Job not found for slug={$slug}\n");
    exit(1);
}

$job->forceFill([
    'closing_date' => now()->subDay()->startOfDay(),
    'active' => true,
])->save();

$job->refresh();

echo 'slug='.$slug.PHP_EOL;
echo 'job_id='.$job->id.PHP_EOL;
echo 'is_closed='.($job->isClosed() ? 'yes' : 'no').PHP_EOL;
echo 'accepts_applications='.($job->acceptsApplications() ? 'yes' : 'no').PHP_EOL;
