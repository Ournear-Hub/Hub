<?php

declare(strict_types=1);

/**
 * Read-only: Employer A QA application detail + optional Recruitment Partnership submission for FE-103 browser QA.
 */

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Application;
use App\Models\EmployerSubmission;
use App\Models\Job;
use App\Models\User;
use App\Support\QaHiringFixtures;

$employerA = User::query()->where('email', QaHiringFixtures::EMPLOYER_A_EMAIL)->first();
$candidate = User::query()->where('email', QaHiringFixtures::CANDIDATE_EMAIL)->first();

if ($employerA === null || $candidate === null) {
    fwrite(STDERR, "Missing QA users. Run php artisan qa:hiring-fixtures\n");
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
    echo "application_found=0\n";
    exit(2);
}

$candidateName = trim((string) ($application->full_name ?: $candidate->name));

echo "application_found=1\n";
echo 'application_id='.$application->id."\n";
echo 'business_id='.($application->business_id ?? '')."\n";
echo 'job_slug='.$application->job->slug."\n";
echo 'job_title='.$application->job->title."\n";
echo 'candidate_name='.$candidateName."\n";
echo 'resume_path_set='.(trim((string) $application->getRawOriginal('resume_path')) !== '' ? 'yes' : 'no')."\n";
$status = $application->status;
echo 'status='.(is_object($status) && property_exists($status, 'value') ? $status->value : (string) $status)."\n";

$submission = EmployerSubmission::query()
    ->where('employer_profile_id', $profileId)
    ->where('application_id', $application->id)
    ->orderByDesc('id')
    ->first();

if ($submission === null) {
    $submission = EmployerSubmission::query()
        ->where('employer_profile_id', $profileId)
        ->orderByDesc('id')
        ->first();
}

if ($submission === null) {
    echo "submission_found=0\n";
    exit(0);
}

echo "submission_found=1\n";
echo 'submission_id='.$submission->id."\n";
echo 'submission_business_id='.($submission->business_id ?? '')."\n";
$subStatus = $submission->status;
echo 'submission_status='.(is_object($subStatus) && property_exists($subStatus, 'value') ? $subStatus->value : (string) $subStatus)."\n";

exit(0);
