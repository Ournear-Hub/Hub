<?php

declare(strict_types=1);

/**
 * Fire two overlapping POST /jobs/{slug}/apply requests with one authenticated session.
 * Requires local server at OURNEAR_CONCURRENT_APPLY_BASE_URL (default http://127.0.0.1:8000).
 */

if ($argc < 2) {
    fwrite(STDERR, "Usage: php qa-concurrent-apply-local.php <job-slug>\n");
    exit(1);
}

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Application;
use App\Models\Job;
use App\Models\User;
use App\Support\QaHiringFixtures;

$slug = $argv[1];
$base = rtrim(getenv('OURNEAR_CONCURRENT_APPLY_BASE_URL') ?: 'http://127.0.0.1:8000', '/');

$job = Job::query()->where('slug', $slug)->first();
$candidate = User::query()->where('email', QaHiringFixtures::CANDIDATE_EMAIL)->first();
if ($job === null || $candidate === null) {
    fwrite(STDERR, "Missing job or QA candidate fixture.\n");
    exit(2);
}

$cookieFile = tempnam(sys_get_temp_dir(), 'ournear_apply_cookies_');
if ($cookieFile === false) {
    exit(3);
}

/**
 * @param  list<string>  $headers
 */
function curl_once(string $url, string $method, string $cookieFile, array $headers = [], string $body = ''): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_COOKIEJAR => $cookieFile,
        CURLOPT_COOKIEFILE => $cookieFile,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 60,
    ]);
    $raw = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    return ['status' => $status, 'raw' => $raw === false ? '' : $raw, 'error' => $err];
}

function xsrf_from_cookie_file(string $cookieFile): ?string
{
    $lines = @file($cookieFile, FILE_IGNORE_NEW_LINES);
    if (! is_array($lines)) {
        return null;
    }
    foreach ($lines as $line) {
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        $parts = explode("\t", $line);
        if (count($parts) >= 7 && $parts[5] === 'XSRF-TOKEN') {
            return urldecode($parts[6]);
        }
    }

    return null;
}

$applyUrl = $base.'/jobs/'.$slug.'/apply';

$loginGet = curl_once($base.'/login', 'GET', $cookieFile);
$xsrf = xsrf_from_cookie_file($cookieFile);
if ($xsrf === null) {
    fwrite(STDERR, "Missing XSRF-TOKEN cookie after login page.\n");
    exit(5);
}

$loginBody = http_build_query([
    'login' => QaHiringFixtures::CANDIDATE_EMAIL,
    'password' => QaHiringFixtures::PASSWORD,
    'remember' => '0',
]);
$loginPost = curl_once(
    $base.'/login',
    'POST',
    $cookieFile,
    [
        'Content-Type: application/x-www-form-urlencoded',
        'Accept: text/html,application/xhtml+xml',
        'X-XSRF-TOKEN: '.$xsrf,
        'X-Requested-With: XMLHttpRequest',
    ],
    $loginBody,
);
if ($loginPost['status'] >= 400) {
    fwrite(STDERR, "Login POST failed HTTP {$loginPost['status']}\n");
    exit(6);
}

$xsrf = xsrf_from_cookie_file($cookieFile);
if ($xsrf === null) {
    fwrite(STDERR, "Missing XSRF-TOKEN after login.\n");
    exit(7);
}

$headers = [
    'Content-Type: application/x-www-form-urlencoded',
    'Accept: text/html, application/xhtml+xml',
    'X-XSRF-TOKEN: '.$xsrf,
    'X-Requested-With: XMLHttpRequest',
    'X-Inertia: true',
];

$mh = curl_multi_init();
$handles = [];
for ($i = 0; $i < 2; $i++) {
    $ch = curl_init($applyUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => '',
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_COOKIEJAR => $cookieFile,
        CURLOPT_COOKIEFILE => $cookieFile,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 60,
    ]);
    curl_multi_add_handle($mh, $ch);
    $handles[] = $ch;
}

$running = null;
do {
    curl_multi_exec($mh, $running);
    if ($running > 0) {
        curl_multi_select($mh, 0.05);
    }
} while ($running > 0);

$statuses = [];
foreach ($handles as $ch) {
    $statuses[] = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_multi_remove_handle($mh, $ch);
    curl_close($ch);
}
curl_multi_close($mh);

@unlink($cookieFile);

$application = Application::query()
    ->where('job_id', $job->id)
    ->where('user_id', $candidate->id)
    ->first();

$count = $application instanceof Application ? 1 : 0;

echo 'parallel_statuses='.json_encode($statuses)."\n";
echo 'application_count='.$count."\n";

$successResponses = array_filter($statuses, fn (int $s): bool => $s >= 200 && $s < 400);
if ($count !== 1 || ! $application instanceof Application) {
    fwrite(STDERR, "Expected exactly 1 application row; got {$count}\n");
    exit(8);
}
if (count($successResponses) < 1) {
    fwrite(STDERR, "Expected at least one successful apply response.\n");
    exit(9);
}

$job->loadMissing('employer.user');
$employerUser = $job->employer?->user;
$employerFingerprint = 'employer_application_received:application_id='.$application->id.'|job_id='.$job->id;
$employerNotifCount = 0;
$candidateNotifCount = 0;
$activityCount = 0;

if ($employerUser instanceof User) {
    $employerNotifCount = $employerUser->notifications()
        ->where('data->fingerprint', $employerFingerprint)
        ->count();
}
$candidateNotifCount = $candidate->notifications()
    ->where('data->application_id', $application->id)
    ->count();
$activityCount = $candidate->activities()
    ->where('type', 'applied_for_job')
    ->where('metadata->application_id', $application->id)
    ->count();

echo 'employer_notification_count='.$employerNotifCount."\n";
echo 'candidate_notification_count='.$candidateNotifCount."\n";
echo 'apply_activity_count='.$activityCount."\n";
echo 'application_id='.$application->id."\n";

if ($employerNotifCount > 1) {
    fwrite(STDERR, "Duplicate employer in-app notifications for one application.\n");
    exit(10);
}
if ($candidateNotifCount > 1) {
    fwrite(STDERR, "Duplicate candidate in-app notifications for one application.\n");
    exit(11);
}
if ($activityCount > 1) {
    fwrite(STDERR, "Duplicate apply activity rows for one application.\n");
    exit(12);
}

echo "ok=yes\n";
exit(0);
