<?php

declare(strict_types=1);

/**
 * Read-only: applicant display names for employer compare QA (REG-BRW-004 independent check).
 */

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Application;
use App\Models\Job;

$slug = 'qa-fixture-screening-compare';
$job = Job::query()->where('slug', $slug)->first();
if ($job === null) {
    echo "found=0\n";
    exit(2);
}

$applications = Application::query()
    ->where('job_id', $job->id)
    ->orderBy('id')
    ->get();

echo "found=1\n";
echo 'job_slug='.$slug."\n";
echo 'job_id='.$job->id."\n";
echo 'applications_count='.$applications->count()."\n";

foreach ($applications as $application) {
    echo 'application_id='.$application->id."\n";
    echo 'full_name='.($application->full_name ?? '')."\n";
    echo 'candidate_display_name='.$application->candidateDisplayName()."\n";
    echo 'business_id='.($application->business_id ?? '')."\n";
    echo "---\n";
}

exit(0);
