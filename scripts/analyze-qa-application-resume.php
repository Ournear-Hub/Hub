<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Application;
use App\Support\ReadablePdfFixture;
use Illuminate\Support\Facades\Storage;

$id = (int) ($argv[1] ?? 0);
$application = Application::query()->findOrFail($id);
$path = $application->getRawOriginal('resume_path');
echo 'resume_path='.$path.PHP_EOL;
if (! is_string($path) || $path === '') {
    exit(1);
}
$bytes = Storage::disk('resumes')->get($path);
echo 'bytes='.strlen($bytes).PHP_EOL;
echo 'analyze='.json_encode(ReadablePdfFixture::analyze($bytes)).PHP_EOL;
echo 'has_qa_marker='.(str_contains($bytes, 'QA readable resume') ? 'yes' : 'no').PHP_EOL;
