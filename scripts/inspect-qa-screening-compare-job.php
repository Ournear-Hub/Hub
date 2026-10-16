<?php

declare(strict_types=1);

/**
 * Read-only: job id with the most applications (for admin screening compare QA).
 */

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Application;
use App\Models\Job;
use Illuminate\Support\Facades\DB;

$preferredSlug = 'qa-fixture-screening-compare';
$preferredJob = Job::query()->where('slug', $preferredSlug)->first();
if ($preferredJob !== null) {
    $preferredCount = Application::query()->where('job_id', $preferredJob->id)->count();
    if ($preferredCount >= 2) {
        echo "found=1\n";
        echo 'job_id='.$preferredJob->id."\n";
        echo 'job_slug='.$preferredJob->slug."\n";
        echo 'applications_count='.$preferredCount."\n";
        exit(0);
    }
}

$row = Application::query()
    ->select('job_id', DB::raw('count(*) as applications_count'))
    ->groupBy('job_id')
    ->having('applications_count', '>=', 2)
    ->orderByDesc('applications_count')
    ->first();

if ($row === null) {
    echo "found=0\n";
    exit(2);
}

$job = Job::query()->find($row->job_id);
if ($job === null) {
    echo "found=0\n";
    exit(2);
}

echo "found=1\n";
echo 'job_id='.$job->id."\n";
echo 'job_slug='.$job->slug."\n";
echo 'applications_count='.$row->applications_count."\n";

exit(0);
