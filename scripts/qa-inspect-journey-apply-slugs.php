<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Application;
use App\Models\Job;
use App\Models\User;
use App\Support\QaHiringFixtures;

$candidate = User::query()->where('email', QaHiringFixtures::CANDIDATE_EMAIL)->firstOrFail();
$applied = Application::query()->where('user_id', $candidate->id)->pluck('job_id')->all();

$jobs = Job::query()
    ->where('slug', 'like', 'qa-fixture-journey-apply%')
    ->orderBy('id')
    ->get();

$unapplied = 0;
$eligible = 0;

foreach ($jobs as $job) {
    $isApplied = in_array($job->id, $applied, true);
    if (! $isApplied) {
        $unapplied++;
        $ok = $job->acceptsApplications();
        if ($ok) {
            $eligible++;
        }
        echo sprintf(
            "unapplied slug=%s tier=%s trial=%s visible=%s accepts=%s\n",
            $job->slug,
            $job->posting_tier,
            $job->trial_expires_at?->toIso8601String() ?? 'null',
            $job->isPubliclyVisible() ? 'yes' : 'no',
            $ok ? 'yes' : 'no',
        );
    }
}

echo "total_journey_apply_jobs={$jobs->count()} unapplied={$unapplied} eligible={$eligible}\n";
