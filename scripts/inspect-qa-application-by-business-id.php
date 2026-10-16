<?php

declare(strict_types=1);

/**
 * Read-only: resolve QA application metadata by business_id (ONT-A-*).
 */

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Application;

$businessId = trim((string) ($argv[1] ?? ''));
if ($businessId === '') {
    fwrite(STDERR, "Usage: php scripts/inspect-qa-application-by-business-id.php ONT-A-000001\n");
    exit(1);
}

$application = Application::query()
    ->where('business_id', $businessId)
    ->with('job:id,slug,title')
    ->first();

if ($application === null || $application->job === null) {
    echo "found=0\n";
    exit(2);
}

$status = $application->status;
echo "found=1\n";
echo 'application_id='.$application->id."\n";
echo 'business_id='.$application->business_id."\n";
echo 'job_slug='.$application->job->slug."\n";
echo 'job_title='.$application->job->title."\n";
echo 'resume_path_set='.(trim((string) $application->getRawOriginal('resume_path')) !== '' ? 'yes' : 'no')."\n";
echo 'status='.(is_object($status) && property_exists($status, 'value') ? $status->value : (string) $status)."\n";

exit(0);
