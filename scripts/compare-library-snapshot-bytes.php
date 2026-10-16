<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Application;
use Illuminate\Support\Facades\Storage;

$businessId = $argv[1] ?? 'ONT-A-000003';
$application = Application::query()->where('business_id', $businessId)->firstOrFail();
$snapPath = (string) $application->getRawOriginal('resume_path');
$library = $application->user?->candidateProfile?->resumes()->where('status', 'active')->first();
$libPath = (string) ($library?->storage_path ?? '');
$libDisk = (string) ($library?->storage_disk ?? 'candidate_files');

$lib = Storage::disk($libDisk)->get($libPath);
$snap = Storage::disk('resumes')->get($snapPath);

echo json_encode([
    'library_path' => $libPath,
    'snapshot_path' => $snapPath,
    'byte_length_library' => strlen($lib),
    'byte_length_snapshot' => strlen($snap),
    'bytes_identical_at_apply_copy' => hash('sha256', $lib) === hash('sha256', $snap),
], JSON_PRETTY_PRINT).PHP_EOL;
