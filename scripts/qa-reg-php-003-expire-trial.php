<?php

declare(strict_types=1);

if ($argc < 2) {
    fwrite(STDERR, "Usage: php qa-reg-php-003-expire-trial.php <job-slug>\n");
    exit(1);
}

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Job;

$job = Job::query()->where('slug', $argv[1])->first();
if ($job === null) {
    fwrite(STDERR, "Job not found\n");
    exit(2);
}

$job->forceFill([
    'posting_tier' => 'trial',
    'trial_expires_at' => now()->subMinute(),
    'publishing_status' => 'published',
    'active' => true,
])->save();

echo 'expired slug='.$job->slug.' visible='.($job->fresh()->isPubliclyVisible() ? 'yes' : 'no')."\n";

exit(0);
