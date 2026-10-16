<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Application;
use App\Models\CandidateResume;
use Illuminate\Support\Facades\Storage;

$dir = storage_path('app/qa-backups/batch6');

$snapshotMeta = $dir.'/ONT-A-000001-snapshot.meta.json';
$snapshotFile = $dir.'/ONT-A-000001-snapshot.pdf';
if (is_file($snapshotMeta) && is_file($snapshotFile)) {
    $meta = json_decode((string) file_get_contents($snapshotMeta), true, 512, JSON_THROW_ON_ERROR);
    $path = (string) ($meta['resume_path'] ?? '');
    if ($path !== '') {
        Storage::disk('resumes')->put($path, (string) file_get_contents($snapshotFile));
        echo "restored application snapshot {$path}\n";
    }
}

$libraryMeta = $dir.'/qa-candidate-library.meta.json';
$libraryFile = $dir.'/qa-candidate-library.pdf';
if (is_file($libraryMeta) && is_file($libraryFile)) {
    $meta = json_decode((string) file_get_contents($libraryMeta), true, 512, JSON_THROW_ON_ERROR);
    $resume = CandidateResume::query()->find((int) ($meta['candidate_resume_id'] ?? 0));
    if ($resume instanceof CandidateResume) {
        $disk = (string) ($meta['storage_disk'] ?? $resume->storage_disk);
        $path = (string) ($meta['storage_path'] ?? $resume->storage_path);
        if ($path !== '') {
            Storage::disk($disk)->put($path, (string) file_get_contents($libraryFile));
            echo "restored library file {$disk}:{$path}\n";
        }
    }
}

echo "restore complete\n";
