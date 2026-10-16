<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Application;
use App\Models\Job;
use App\Models\User;
use App\Support\QaHiringFixtures;

$slug = $argv[1] ?? '';
$job = Job::query()->where('slug', $slug)->first();
$candidate = User::query()->where('email', QaHiringFixtures::CANDIDATE_EMAIL)->first();
if ($job === null || $candidate === null) {
    exit(1);
}
$app = Application::query()->where('user_id', $candidate->id)->where('job_id', $job->id)->first();
echo $app ? 'applied=yes id='.$app->id : 'applied=no';
