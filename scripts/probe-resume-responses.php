<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Application;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

function probePdf(string $bytes): array
{
    $len = strlen($bytes);
    $startsWithPdf = str_starts_with($bytes, '%PDF');
    $pageMarkers = preg_match_all('/\/Type\s*\/Page\b/', $bytes) ?: 0;
    $eof = str_contains($bytes, '%%EOF');

    return [
        'byte_length' => $len,
        'starts_with_%PDF' => $startsWithPdf,
        'page_type_markers' => $pageMarkers,
        'contains_eof' => $eof,
        'readable_pdf' => $startsWithPdf && $len > 100 && ($pageMarkers >= 1 || $eof),
    ];
}

function httpProbe(string $method, string $uri, User $user, ?string $guard = 'account'): array
{
    if ($guard !== null) {
        Auth::guard($guard)->login($user);
    } else {
        Auth::login($user);
    }
    $kernel = app(Illuminate\Contracts\Http\Kernel::class);
    $request = Request::create($uri, $method);
    $request->setUserResolver(fn () => $user);
    $response = $kernel->handle($request);
    $kernel->terminate($request, $response);
    if ($guard !== null) {
        Auth::guard($guard)->logout();
    } else {
        Auth::logout();
    }

    $body = $response->getContent();
    $headers = [];
    foreach ($response->headers->allPreserveCaseWithoutCookies() as $name => $values) {
        $headers[$name] = implode(', ', $values);
    }

    $out = [
        'status' => $response->getStatusCode(),
        'content_type' => $headers['Content-Type'] ?? null,
        'content_disposition' => $headers['Content-Disposition'] ?? null,
        'content_length_header' => $headers['Content-Length'] ?? null,
    ];

    if ($response->getStatusCode() === 200 && is_string($body) && $body !== '') {
        $out['pdf'] = probePdf($body);
        $out['body_byte_length'] = strlen($body);
    }

    return $out;
}

$application = Application::query()->where('business_id', 'ONT-A-000001')->first();
if ($application === null) {
    echo "FAIL fixture application not found\n";
    exit(1);
}

$path = $application->getRawOriginal('resume_path');
$diskPath = is_string($path) ? $path : '';
$onDisk = $diskPath !== '' && Storage::disk('resumes')->exists($diskPath);

echo 'application_id='.$application->id.PHP_EOL;
echo 'resume_path='.$diskPath.PHP_EOL;
echo 'on_disk='.($onDisk ? 'yes' : 'no').PHP_EOL;

if ($onDisk) {
    $raw = Storage::disk('resumes')->get($diskPath);
    echo 'disk_pdf='.json_encode(probePdf($raw), JSON_THROW_ON_ERROR).PHP_EOL;
    echo 'disk_size='.Storage::disk('resumes')->size($diskPath).PHP_EOL;
}

$employerA = User::query()->where('email', 'qa-employer-a@example.test')->firstOrFail();
$employerB = User::query()->where('email', 'qa-employer-b@example.test')->firstOrFail();
$admin = User::query()->where('email', env('LOCAL_ADMIN_EMAIL', 'admin@ournear.test'))->first();

$employerInline = '/account/employer-applications/'.$application->id.'/resume?disposition=inline';
$employerDownload = '/account/employer-applications/'.$application->id.'/resume';
$adminInline = '/admin/applications/'.$application->id.'/resume?disposition=inline';
$adminDownload = '/admin/applications/'.$application->id.'/resume';

echo 'employer_a_inline='.json_encode(httpProbe('GET', $employerInline, $employerA), JSON_THROW_ON_ERROR).PHP_EOL;
echo 'employer_a_download='.json_encode(httpProbe('GET', $employerDownload, $employerA), JSON_THROW_ON_ERROR).PHP_EOL;
echo 'employer_b_download='.json_encode(httpProbe('GET', $employerDownload, $employerB), JSON_THROW_ON_ERROR).PHP_EOL;

if ($admin instanceof User) {
    echo 'admin_inline='.json_encode(httpProbe('GET', $adminInline, $admin, 'admin'), JSON_THROW_ON_ERROR).PHP_EOL;
    echo 'admin_download='.json_encode(httpProbe('GET', $adminDownload, $admin, 'admin'), JSON_THROW_ON_ERROR).PHP_EOL;
} else {
    echo "admin_user=NOT_FOUND\n";
}

$application->loadMissing('user.candidateProfile.resumes');
$libraryPrimary = $application->user?->candidateProfile?->resumes()->where('is_primary', true)->first();
if ($libraryPrimary !== null) {
    echo 'library_primary_path='.($libraryPrimary->getRawOriginal('storage_path') ?? 'null').PHP_EOL;
    echo 'snapshot_is_library_path='.(($libraryPrimary->getRawOriginal('storage_path') ?? '') === $diskPath ? 'yes' : 'no').PHP_EOL;
}

echo 'submission_count='.$application->submissions()->count().PHP_EOL;

$candidate = User::query()->where('email', 'qa-candidate@example.test')->first();
if ($candidate instanceof User) {
    $candidate->loadMissing('candidateProfile.resumes');
    $lib = $candidate->candidateProfile?->resumes()->where('status', 'active')->first();
    if ($lib !== null) {
        echo 'library_download='.json_encode(httpProbe('GET', '/account/resumes/'.$lib->id.'/download', $candidate), JSON_THROW_ON_ERROR).PHP_EOL;
    }
}
