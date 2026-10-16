<?php

declare(strict_types=1);

/**
 * Local/testing only: read-only journey fixture capacity and apply throttle evidence.
 * Does not create jobs, delete data, or clear caches.
 */

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Application;
use App\Models\Job;
use App\Models\User;
use App\Support\QaHiringFixtures;
use Illuminate\Support\Carbon;

if ($app->environment('production')) {
    fwrite(STDERR, "Refusing to run in production.\n");
    exit(1);
}

if (! $app->environment(['local', 'testing'])) {
    fwrite(STDERR, "Restricted to local/testing environments.\n");
    exit(1);
}

$candidate = User::query()->where('email', QaHiringFixtures::CANDIDATE_EMAIL)->first();
$employerA = User::query()->where('email', QaHiringFixtures::EMPLOYER_A_EMAIL)->first();

if ($candidate === null || $employerA === null || $employerA->employerProfile === null) {
    echo "fixtures_ready=no\n";
    echo "fixtures_message=Run php artisan qa:hiring-fixtures first.\n";
    exit(2);
}

echo "fixtures_ready=yes\n";

$slugLimit = 60;
$appliedJobIds = Application::query()
    ->where('user_id', $candidate->id)
    ->pluck('job_id')
    ->map(fn ($id) => (int) $id)
    ->all();

$unappliedFixtureSlugs = [];
$missingFixtureSlugs = [];
$firstEligibleSlug = '';

for ($n = 1; $n <= $slugLimit; $n++) {
    $slug = $n === 1 ? 'qa-fixture-journey-apply' : 'qa-fixture-journey-apply-'.$n;
    $job = Job::query()->where('slug', $slug)->first();

    if ($job === null) {
        $missingFixtureSlugs[] = $slug;
        if ($firstEligibleSlug === '') {
            $firstEligibleSlug = $slug;
        }
        continue;
    }

    $alreadyApplied = in_array((int) $job->id, $appliedJobIds, true);
    if (! $alreadyApplied) {
        $unappliedFixtureSlugs[] = $slug;
        if ($firstEligibleSlug === '') {
            $firstEligibleSlug = $slug;
        }
    }
}

echo 'journey_apply_slug_limit='.$slugLimit."\n";
echo 'journey_apply_existing_unapplied='.count($unappliedFixtureSlugs)."\n";
echo 'journey_apply_missing_slugs='.count($missingFixtureSlugs)."\n";
echo 'journey_apply_provision_slots='.(count($unappliedFixtureSlugs) + count($missingFixtureSlugs))."\n";
echo 'next_eligible_slug='.$firstEligibleSlug."\n";
echo 'provision_would_succeed='.($firstEligibleSlug !== '' ? 'yes' : 'no')."\n";

$since = Carbon::now()->subHour();
$applicationsLastHour = Application::query()
    ->where('user_id', $candidate->id)
    ->where('created_at', '>=', $since)
    ->count();

$hourlyLimit = 8;
$remaining = max(0, $hourlyLimit - $applicationsLastHour);

echo 'candidate_applications_last_60m='.$applicationsLastHour."\n";
echo 'apply_hourly_limit='.$hourlyLimit."\n";
echo 'apply_submit_budget_remaining='.$remaining."\n";
echo 'cross_role_apply_feasible='.($remaining >= 1 && $firstEligibleSlug !== '' ? 'yes' : 'no')."\n";
echo 'new_apply_test_feasible='.($remaining >= 1 && $firstEligibleSlug !== '' ? 'yes' : 'no')."\n";

exit(0);
