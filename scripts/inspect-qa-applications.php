<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$apps = App\Models\Application::query()
    ->whereHas('user', fn ($q) => $q->where('email', 'qa-candidate@example.test'))
    ->with(['job.employer.user', 'user'])
    ->get();

foreach ($apps as $app) {
    echo sprintf(
        "id=%d business_id=%s status=%s job_slug=%s employer=%s\n",
        $app->id,
        $app->business_id ?? 'null',
        $app->status->value,
        $app->job?->slug ?? 'null',
        $app->job?->employer?->user?->email ?? 'null',
    );
}

$fixture = App\Models\Application::query()->where('business_id', 'ONT-A-000001')->first();
echo $fixture ? "fixture ONT-A-000001 found id={$fixture->id}\n" : "fixture ONT-A-000001 NOT FOUND\n";

$submission = App\Models\EmployerSubmission::query()->where('application_id', $fixture?->id)->first();
echo $submission ? "submission {$submission->business_id} status={$submission->status?->value}\n" : "no submission for fixture\n";

echo 'job_id='.$fixture?->job_id.' job_slug='.$fixture?->job?->slug.PHP_EOL;
