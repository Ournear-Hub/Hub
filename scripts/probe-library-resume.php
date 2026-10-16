<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Storage;

$candidate = User::query()->where('email', 'qa-candidate@example.test')->firstOrFail();
$candidate->loadMissing('candidateProfile.resumes');
$resume = $candidate->candidateProfile?->resumes()->where('status', 'active')->first()
    ?? $candidate->candidateProfile?->resumes()->first();

if ($resume === null) {
    echo "no library resume\n";
    exit(0);
}

$path = $resume->getRawOriginal('storage_path') ?? $resume->storage_path;
$diskName = (string) ($resume->storage_disk ?: 'candidate_files');
echo 'library_resume_id='.$resume->id.PHP_EOL;
echo 'storage_disk='.$diskName.PHP_EOL;
echo 'storage_path='.$path.PHP_EOL;

$disk = Storage::disk($diskName);
if (! is_string($path) || ! $disk->exists($path)) {
    echo "file missing on {$diskName} disk\n";
    exit(0);
}

$bytes = $disk->get($path);
echo 'size='.$disk->size($path).PHP_EOL;
echo 'starts_pdf='.(str_starts_with($bytes, '%PDF') ? 'yes' : 'no').PHP_EOL;
echo 'page_markers='.(preg_match_all('/\/Type\s*\/Page\b/', $bytes) ?: 0).PHP_EOL;
echo 'analysis='.json_encode(\App\Support\ReadablePdfFixture::analyze($bytes)).PHP_EOL;
