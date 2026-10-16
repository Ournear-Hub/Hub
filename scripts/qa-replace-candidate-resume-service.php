<?php

declare(strict_types=1);

/**
 * Local QA: replace library resume through CandidateResumeLibraryService (same persistence as UI POST).
 */

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\CandidateResume;
use App\Models\User;
use App\Services\CandidateResumeLibraryService;
use App\Support\ReadablePdfFixture;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

$pdfPath = storage_path('app/qa-documents/readable-test-resume.pdf');
if (! is_file($pdfPath)) {
    if (! is_dir(dirname($pdfPath))) {
        mkdir(dirname($pdfPath), 0775, true);
    }
    file_put_contents($pdfPath, ReadablePdfFixture::minimalOnePage());
}

$candidate = User::query()->where('email', 'qa-candidate@example.test')->firstOrFail();
$resume = CandidateResume::query()
    ->where('candidate_profile_id', $candidate->candidateProfile?->id)
    ->where('status', 'active')
    ->orderByDesc('is_primary')
    ->firstOrFail();

$before = [
    'id' => $resume->id,
    'storage_disk' => $resume->storage_disk,
    'storage_path' => $resume->storage_path,
    'file_size' => $resume->file_size,
];

$upload = new UploadedFile($pdfPath, 'readable-test-resume.pdf', 'application/pdf', null, true);
$library = app(CandidateResumeLibraryService::class);
$library->replace($resume, $upload, $candidate);
$resume->refresh();

$bytes = Storage::disk((string) $resume->storage_disk)->get((string) $resume->storage_path);

echo json_encode([
    'before' => $before,
    'after' => [
        'id' => $resume->id,
        'storage_disk' => $resume->storage_disk,
        'storage_path' => $resume->storage_path,
        'file_size' => $resume->file_size,
        'original_filename' => $resume->original_filename,
    ],
    'disk_analysis' => ReadablePdfFixture::analyze($bytes),
], JSON_PRETTY_PRINT).PHP_EOL;
