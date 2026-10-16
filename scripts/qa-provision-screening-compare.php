<?php

declare(strict_types=1);

/**
 * Local/testing only: ensure one published job has >= 2 applications for admin screening compare QA.
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

$employerA = User::query()->where('email', QaHiringFixtures::EMPLOYER_A_EMAIL)->first();
if ($employerA === null || $employerA->employerProfile === null) {
    fwrite(STDERR, "Run php artisan qa:hiring-fixtures first.\n");
    exit(1);
}

$profile = $employerA->employerProfile;
$slug = 'qa-fixture-screening-compare';
$title = 'QA Fixture Screening Compare Role (Employer A)';

$job = Job::query()->where('slug', $slug)->first();
if ($job === null) {
    $job = Job::factory()->create([
        'employer_id' => $profile->id,
        'title' => $title,
        'project' => QaHiringFixtures::COMPANY_A,
        'slug' => $slug,
        'publishing_status' => JobPublishingStatus::Published,
        'active' => true,
    ]);
    $createdJob = 1;
} else {
    $job->forceFill([
        'employer_id' => $profile->id,
        'title' => $title,
        'publishing_status' => JobPublishingStatus::Published,
        'active' => true,
    ])->save();
    $createdJob = 0;
}

$count = Application::query()->where('job_id', $job->id)->count();
$createdApps = 0;

while ($count < 2) {
    Application::factory()->create([
        'job_id' => $job->id,
        'full_name' => 'QA Screening Applicant '.($count + 1),
    ]);
    $count++;
    $createdApps++;
}

echo 'created_job='.$createdJob."\n";
echo 'created_applications='.$createdApps."\n";
echo 'job_id='.$job->id."\n";
echo 'job_slug='.$slug."\n";
echo 'applications_count='.$count."\n";

exit(0);
