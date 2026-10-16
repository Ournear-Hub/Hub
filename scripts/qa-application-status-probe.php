<?php

declare(strict_types=1);

/**
 * Local QA: read application status for a job slug + business_id (ONT-A-*).
 *
 * Usage: php scripts/qa-application-status-probe.php {job_slug} {business_id}
 */

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Application;
use App\Models\Job;

if ($app->environment('production')) {
    fwrite(STDERR, "Refusing to run in production.\n");
    exit(1);
}

$slug = (string) ($argv[1] ?? '');
$businessId = (string) ($argv[2] ?? '');

if ($slug === '' || $businessId === '') {
    fwrite(STDERR, "Usage: qa-application-status-probe.php {job_slug} {business_id}\n");
    exit(1);
}

$job = Job::query()->where('slug', $slug)->first();
if ($job === null) {
    echo "found=0\n";
    exit(0);
}

$application = Application::query()
    ->where('job_id', $job->id)
    ->where('business_id', $businessId)
    ->first();

if ($application === null) {
    echo "found=0\n";
    exit(0);
}

echo "found=1\n";
echo 'status='.$application->status->value."\n";
echo 'application_id='.$application->id."\n";
