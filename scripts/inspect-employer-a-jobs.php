<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Application;
use App\Models\EmployerProfile;
use App\Models\Job;
use App\Models\User;
use App\Support\QaHiringFixtures;

$candidate = User::query()->where('email', QaHiringFixtures::CANDIDATE_EMAIL)->firstOrFail();
$employerA = User::query()->where('email', QaHiringFixtures::EMPLOYER_A_EMAIL)->firstOrFail();
$profile = EmployerProfile::query()->where('user_id', $employerA->id)->first();

echo 'employer_a_profile_id='.($profile?->id ?? 'null').PHP_EOL;

$appliedJobIds = Application::query()->where('user_id', $candidate->id)->pluck('job_id')->all();

if ($profile) {
    $jobs = Job::query()
        ->where('employer_id', $profile->id)
        ->orderBy('id')
        ->get();
    foreach ($jobs as $job) {
        $applied = in_array((int) $job->id, array_map('intval', $appliedJobIds), true);
        echo sprintf(
            "id=%d slug=%s active=%s publishing=%s applied=%s title=%s\n",
            $job->id,
            $job->slug,
            $job->active ? '1' : '0',
            $job->publishing_status?->value ?? 'null',
            $applied ? 'yes' : 'no',
            $job->title,
        );
    }
}
