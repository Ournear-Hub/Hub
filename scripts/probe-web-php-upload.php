<?php

declare(strict_types=1);

/**
 * Compare CLI vs HTTP PHP upload settings. Local only.
 * Usage: php scripts/probe-web-php-upload.php [base_url]
 */
$baseUrl = rtrim($argv[1] ?? 'http://127.0.0.1:8000', '/');
$uploadTmp = ini_get('upload_tmp_dir');
$uploadTmpCli = is_string($uploadTmp) && $uploadTmp !== '' ? $uploadTmp : sys_get_temp_dir();

$cli = [
    'context' => 'cli',
    'sapi' => PHP_SAPI,
    'upload_tmp_dir' => $uploadTmpCli,
    'upload_tmp_writable' => is_dir($uploadTmpCli) && is_writable($uploadTmpCli),
    'file_uploads' => filter_var(ini_get('file_uploads'), FILTER_VALIDATE_BOOL),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
];

$png = hex2bin(
    '89504e470d0a1a0a0000000d49484452000000010000000108060000001f15c489'
    .'0000000a49444154789c6300010000050001000d0a2db40000000049454e44ae426082'
);
$tmpFile = tempnam(sys_get_temp_dir(), 'ournear-probe-');
file_put_contents($tmpFile, $png);

$probeUrl = $baseUrl.'/__local/upload-env-probe';
$ch = curl_init($probeUrl);
$post = ['file' => new CURLFile($tmpFile, 'image/png', 'probe.png')];
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $post,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
]);
$body = curl_exec($ch);
$http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
@unlink($tmpFile);

$web = null;
if ($http === 200 && is_string($body)) {
    $decoded = json_decode($body, true);
    $web = is_array($decoded) ? $decoded : ['raw' => $body];
} else {
    $web = ['http_status' => $http, 'body' => is_string($body) ? substr($body, 0, 500) : null];
}

echo json_encode(['cli' => $cli, 'web' => $web], JSON_PRETTY_PRINT).PHP_EOL;

$ok = is_array($web)
    && ($web['has_file'] ?? false) === true
    && (int) ($web['file_error'] ?? 1) === UPLOAD_ERR_OK;

exit($ok ? 0 : 1);
