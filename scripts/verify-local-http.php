<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

(Dotenv\Dotenv::createImmutable(dirname(__DIR__)))->safeLoad();

$base = rtrim((string) env('APP_URL', 'http://127.0.0.1:8000'), '/');
$adminEmail = (string) env('LOCAL_ADMIN_EMAIL', 'admin@ournear.test');
$adminPassword = (string) env('LOCAL_ADMIN_PASSWORD', '');

if ($adminPassword === '') {
    echo "FAIL admin credentials not configured in .env\n";
    exit(1);
}

function request(string $url, string $method = 'GET', array $headers = [], ?string $body = null, ?string $cookieFile = null): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 30,
    ]);
    if ($cookieFile !== null) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    }
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }
    $raw = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ['status' => $status, 'raw' => is_string($raw) ? $raw : ''];
}

$publicPaths = [
    '/',
    '/jobs',
    '/register',
    '/login',
    '/about',
    '/services',
    '/for-employers',
    '/contact',
    '/privacy-policy',
    '/terms',
];

$results = [];
foreach ($publicPaths as $path) {
    $res = request($base.$path);
    $ok = $res['status'] >= 200 && $res['status'] < 400;
    $results['public'][$path] = $ok ? 'PASS' : 'FAIL:'.$res['status'];
}

$cookie = tempnam(sys_get_temp_dir(), 'onh_');
$loginGet = request($base.'/admin/login', 'GET', [], null, $cookie);
$xsrf = '';
if (preg_match('/XSRF-TOKEN=([^;]+)/', $loginGet['raw'], $m)) {
    $xsrf = urldecode($m[1]);
}
$loginPost = request(
    $base.'/admin/login',
    'POST',
    [
        'Content-Type: application/json',
        'Accept: text/html, application/xhtml+xml',
        'X-Requested-With: XMLHttpRequest',
        'X-Inertia: true',
        'X-XSRF-TOKEN: '.$xsrf,
    ],
    json_encode([
        'email' => $adminEmail,
        'password' => $adminPassword,
        'remember' => false,
    ], JSON_THROW_ON_ERROR),
    $cookie,
);
$results['admin_login'] = ($loginPost['status'] === 302 || $loginPost['status'] === 409) ? 'PASS' : 'FAIL:'.$loginPost['status'];

$adminPaths = [
    '/admin',
    '/admin/candidates',
    '/admin/employers',
    '/admin/jobs',
    '/admin/applications',
    '/admin/master-data',
    '/admin/settings',
];
foreach ($adminPaths as $path) {
    $res = request($base.$path, 'GET', ['Accept: text/html'], null, $cookie);
    $ok = $res['status'] >= 200 && $res['status'] < 400;
    $results['admin'][$path] = $ok ? 'PASS' : 'FAIL:'.$res['status'];
}

$logout = request($base.'/admin/logout', 'POST', [
    'X-XSRF-TOKEN: '.$xsrf,
    'X-Requested-With: XMLHttpRequest',
], '', $cookie);
$results['admin_logout'] = ($logout['status'] === 302 || $logout['status'] === 419 || $logout['status'] === 405) ? 'PASS' : 'FAIL:'.$logout['status'];

@unlink($cookie);

echo json_encode($results, JSON_PRETTY_PRINT).PHP_EOL;

$failed = false;
foreach ($results as $group) {
    if (! is_array($group)) {
        if (str_starts_with((string) $group, 'FAIL')) {
            $failed = true;
        }
        continue;
    }
    foreach ($group as $v) {
        if (str_starts_with((string) $v, 'FAIL')) {
            $failed = true;
        }
    }
}

exit($failed ? 1 : 0);
