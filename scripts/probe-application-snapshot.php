<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Application;
use App\Models\CandidateResume;
use App\Support\ReadablePdfFixture;
use Illuminate\Support\Facades\Storage;

$businessId = $argv[1] ?? null;
if ($businessId === null) {
    fwrite(STDERR, "usage: probe-application-snapshot.php ONT-A-XXXXXX\n");
    exit(1);
}

$application = Application::query()->where('business_id', $businessId)->firstOrFail();
$path = (string) $application->getRawOriginal('resume_path');
$library = CandidateResume::query()->find(
    Application::query()->where('id', $application->id)->value('user_id')
);

$candidate = $application->user;
$libraryResume = $candidate?->candidateProfile?->resumes()->where('status', 'active')->first();

$diskBytes = $path !== '' && Storage::disk('resumes')->exists($path)
    ? Storage::disk('resumes')->get($path)
    : '';

echo json_encode([
    'application_id' => $application->id,
    'business_id' => $application->business_id,
    'job_id' => $application->job_id,
    'job_slug' => $application->job?->slug,
    'status' => $application->status->value,
    'resume_path' => $path,
    'snapshot_on_disk' => $path !== '' && Storage::disk('resumes')->exists($path),
    'snapshot_analysis' => $diskBytes !== '' ? ReadablePdfFixture::analyze($diskBytes) : null,
    'library_path' => $libraryResume?->storage_path,
    'snapshot_same_as_library_path' => $libraryResume && $path === $libraryResume->storage_path,
    'fixture_ont_a_000001_resume_path' => Application::query()->where('business_id', 'ONT-A-000001')->value('resume_path'),
], JSON_PRETTY_PRINT).PHP_EOL;
