<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Enums\JobPublishingStatus;
use App\Models\Job;
use App\Models\User;
use App\Support\QaHiringFixtures;

foreach ([QaHiringFixtures::EMPLOYER_A_EMAIL, QaHiringFixtures::EMPLOYER_B_EMAIL] as $email) {
    $user = User::query()->where('email', $email)->first();
    $profile = $user?->employerProfile;
    if ($profile === null) {
        echo "{$email}: no profile\n";
        continue;
    }
    $published = Job::query()
        ->where('employer_id', $profile->id)
        ->where('publishing_status', JobPublishingStatus::Published)
        ->count();
    echo "{$email} verified=".($profile->ournearVerified() ? 'yes' : 'no')." published={$published}\n";
}

$job = Job::query()->where('slug', QaHiringFixtures::SLUG_PUBLISHED_A)->first();
if ($job) {
    echo 'fixture_a tier='.$job->posting_tier.' trial_expires='.($job->trial_expires_at?->toIso8601String() ?? 'null')."\n";
    echo 'fixture_a publicly_visible='.($job->isPubliclyVisible() ? 'yes' : 'no')."\n";
}

exit(0);
