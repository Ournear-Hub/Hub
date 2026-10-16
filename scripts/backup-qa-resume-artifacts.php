<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Application;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

$dir = storage_path('app/qa-backups/batch6');
if (! is_dir($dir)) {
    mkdir($dir, 0775, true);
}

$application = Application::query()->where('business_id', 'ONT-A-000001')->firstOrFail();
$snapshotPath = (string) $application->getRawOriginal('resume_path');
if ($snapshotPath !== '' && Storage::disk('resumes')->exists($snapshotPath)) {
    $bytes = Storage::disk('resumes')->get($snapshotPath);
    file_put_contents($dir.'/ONT-A-000001-snapshot.pdf', $bytes);
    file_put_contents($dir.'/ONT-A-000001-snapshot.meta.json', json_encode([
        'application_id' => $application->id,
        'resume_path' => $snapshotPath,
        'byte_length' => strlen($bytes),
    ], JSON_PRETTY_PRINT));
}

$candidate = User::query()->where('email', 'qa-candidate@example.test')->firstOrFail();
$candidate->loadMissing('candidateProfile.resumes');
$library = $candidate->candidateProfile?->resumes()->where('status', 'active')->first();
if ($library !== null) {
    $disk = (string) $library->storage_disk;
    $path = (string) $library->storage_path;
    if ($path !== '' && Storage::disk($disk)->exists($path)) {
        $bytes = Storage::disk($disk)->get($path);
        file_put_contents($dir.'/qa-candidate-library.pdf', $bytes);
        file_put_contents($dir.'/qa-candidate-library.meta.json', json_encode([
            'candidate_resume_id' => $library->id,
            'storage_disk' => $disk,
            'storage_path' => $path,
            'title' => $library->title,
            'byte_length' => strlen($bytes),
        ], JSON_PRETTY_PRINT));
    }
}

echo "backed up to {$dir}\n";
