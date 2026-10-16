<?php

declare(strict_types=1);

/**
 * Local QA only: POST replace through the HTTP kernel (same route/validation as the Candidate UI).
 */

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\CandidateResume;
use App\Models\User;
use App\Support\ReadablePdfFixture;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;

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

Auth::guard('account')->login($candidate);
$kernel = app(Illuminate\Contracts\Http\Kernel::class);
$request = Request::create(
    route('account.resumes.replace', ['resume' => $resume->id]),
    'POST',
    [],
    [],
    ['file' => $upload],
);
$request->setUserResolver(fn () => $candidate);
$response = $kernel->handle($request);
$kernel->terminate($request, $response);
Auth::guard('account')->logout();

$resume->refresh();

echo json_encode([
    'http_status' => $response->getStatusCode(),
    'before' => $before,
    'after' => [
        'id' => $resume->id,
        'storage_disk' => $resume->storage_disk,
        'storage_path' => $resume->storage_path,
        'file_size' => $resume->file_size,
        'original_filename' => $resume->original_filename,
    ],
    'disk_analysis' => ReadablePdfFixture::analyze(
        (string) Illuminate\Support\Facades\Storage::disk((string) $resume->storage_disk)->get((string) $resume->storage_path)
    ),
], JSON_PRETTY_PRINT).PHP_EOL;
