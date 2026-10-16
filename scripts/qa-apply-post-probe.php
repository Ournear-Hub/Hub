<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Job;
use App\Models\User;
use App\Support\QaHiringFixtures;
use Illuminate\Support\Facades\Auth;

$slug = $argv[1] ?? '';
$job = Job::query()->where('slug', $slug)->first();
$candidate = User::query()->where('email', QaHiringFixtures::CANDIDATE_EMAIL)->first();

if ($job === null || $candidate === null) {
    fwrite(STDERR, "missing job or candidate\n");
    exit(1);
}

Auth::guard('account')->login($candidate);

/** @var \Illuminate\Contracts\Http\Kernel $http */
$http = $app->make(\Illuminate\Contracts\Http\Kernel::class);

$request = \Illuminate\Http\Request::create(
    '/jobs/'.$job->slug.'/apply',
    'POST',
    [],
    [],
    [],
    ['HTTP_ACCEPT' => 'text/html'],
);

$request->setLaravelSession($app->make('session')->driver());
$request->setUserResolver(fn () => $candidate);

try {
    $response = $http->handle($request);
    echo 'status='.$response->getStatusCode()."\n";
    if ($response->getStatusCode() >= 400) {
        echo substr((string) $response->getContent(), 0, 500)."\n";
    }
} catch (\Throwable $e) {
    echo 'exception='.$e::class.' msg='.$e->getMessage()."\n";
    echo $e->getFile().':'.$e->getLine()."\n";
    exit(2);
}
