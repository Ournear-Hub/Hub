<?php

declare(strict_types=1);

/**
 * Read-only: latest QA candidate application on Employer A journey-apply jobs (D1/D2).
 */

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Application;
use App\Models\Job;
use App\Models\User;
use App\Support\QaHiringFixtures;

$employerA = User::query()->where('email', QaHiringFixtures::EMPLOYER_A_EMAIL)->first();
$candidate = User::query()->where('email', QaHiringFixtures::CANDIDATE_EMAIL)->first();

if ($employerA === null || $candidate === null) {
    fwrite(STDERR, "Missing QA users.\n");
    exit(1);
}

$profileId = $employerA->employerProfile?->id;
if ($profileId === null) {
    exit(1);
}

$journeyJobIds = Job::query()
    ->where('employer_id', $profileId)
    ->where('slug', 'like', 'qa-fixture-journey-apply%')
    ->pluck('id')
    ->all();

$application = Application::query()
    ->where('user_id', $candidate->id)
    ->when($journeyJobIds !== [], fn ($q) => $q->whereIn('job_id', $journeyJobIds))
    ->orderByDesc('id')
    ->with('job:id,slug,title')
    ->first();

if ($application === null) {
    $application = Application::query()
        ->where('user_id', $candidate->id)
        ->whereHas('job', fn ($q) => $q->where('employer_id', $profileId))
        ->orderByDesc('id')
        ->with('job:id,slug,title')
        ->first();
}

if ($application === null || $application->job === null) {
    echo "found=0\n";
    exit(2);
}

echo "found=1\n";
echo 'application_id='.$application->id."\n";
echo 'business_id='.$application->business_id."\n";
echo 'job_slug='.$application->job->slug."\n";
echo 'job_title='.$application->job->title."\n";
echo 'resume_path_set='.(trim((string) $application->getRawOriginal('resume_path')) !== '' ? 'yes' : 'no')."\n";
$status = $application->status;
echo 'status='.(is_object($status) && property_exists($status, 'value') ? $status->value : (string) $status)."\n";

$totalForEmployer = Application::query()
    ->whereHas('job', fn ($q) => $q->where('employer_id', $profileId))
    ->count();
echo 'employer_a_application_total='.$totalForEmployer."\n";

exit(0);
