<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Application;
use App\Models\Job;
use App\Models\User;
use Illuminate\Support\Facades\DB;

$app3 = Application::query()->where('business_id', 'ONT-A-000003')->first();
$job29 = Job::query()->where('slug', 'qa-fixture-draft-a')->first();

$employerNotifs = DB::table('notifications')
    ->where('notifiable_type', User::class)
    ->whereIn('notifiable_id', function ($q) {
        $q->select('id')->from('users')->where('email', 'qa-employer-a@example.test');
    })
    ->orderByDesc('id')
    ->limit(3)
    ->get(['id', 'type', 'created_at']);

echo json_encode([
    'new_application' => $app3 ? [
        'id' => $app3->id,
        'business_id' => $app3->business_id,
        'job_id' => $app3->job_id,
        'resume_path' => $app3->getRawOriginal('resume_path'),
        'status' => $app3->status->value,
    ] : null,
    'job_qa_fixture_draft_a' => $job29 ? [
        'id' => $job29->id,
        'publishing_status' => $job29->publishing_status?->value,
        'active' => $job29->active,
    ] : null,
    'fixture_ont_a_000001' => Application::query()->where('business_id', 'ONT-A-000001')->first(['id', 'status', 'resume_path'])?->toArray(),
    'recent_employer_a_notifications' => $employerNotifs,
], JSON_PRETTY_PRINT).PHP_EOL;
