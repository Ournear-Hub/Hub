<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Job;

$slug = $argv[1] ?? 'qa-fixture-journey-apply-36';
$job = Job::query()->where('slug', $slug)->first();
if ($job === null) {
    fwrite(STDERR, "not found\n");
    exit(1);
}

echo 'slug='.$job->slug."\n";
echo 'active='.($job->active ? '1' : '0')."\n";
echo 'publishing='.$job->publishing_status?->value."\n";
echo 'posting_tier='.$job->posting_tier."\n";
echo 'trial_expires='.($job->trial_expires_at?->toDateTimeString() ?? 'null')."\n";
echo 'closing_date='.($job->closing_date?->toDateString() ?? 'null')."\n";
echo 'isClosed='.($job->isClosed() ? 'yes' : 'no')."\n";
echo 'isPubliclyVisible='.($job->isPubliclyVisible() ? 'yes' : 'no')."\n";
