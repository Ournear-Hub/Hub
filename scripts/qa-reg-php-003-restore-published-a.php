<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Enums\JobPublishingStatus;
use App\Models\Job;
use App\Support\QaHiringFixtures;

$job = Job::query()->where('slug', QaHiringFixtures::SLUG_PUBLISHED_A)->first();
if ($job === null) {
    fwrite(STDERR, "Fixture job missing\n");
    exit(1);
}

$job->forceFill([
    'posting_tier' => 'basic',
    'trial_expires_at' => null,
    'publishing_status' => JobPublishingStatus::Published,
    'active' => true,
])->save();

echo "restored slug=".QaHiringFixtures::SLUG_PUBLISHED_A."\n";

exit(0);
