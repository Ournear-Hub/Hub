<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$manifestPath = base_path('public/build/manifest.json');

$uploadTmpDir = ini_get('upload_tmp_dir');
$uploadTmpDir = is_string($uploadTmpDir) && $uploadTmpDir !== '' ? $uploadTmpDir : sys_get_temp_dir();

$checks = [
    'APP_ENV' => env('APP_ENV') === 'local',
    'APP_DEBUG' => env('APP_DEBUG') === true,
    'APP_URL' => env('APP_URL') === 'http://127.0.0.1:8000',
    'DB_DATABASE' => env('DB_DATABASE') === 'ournear_hire_local',
    'MAIL_MAILER' => env('MAIL_MAILER') === 'log',
    'DB_NOT_HOSTINGER' => ! str_contains((string) env('DB_DATABASE'), 'u134418311'),
    'APP_KEY_SET' => is_string(env('APP_KEY')) && env('APP_KEY') !== '',
    'VITE_MANIFEST' => is_file($manifestPath) && is_readable($manifestPath),
    'UPLOAD_TMP_WRITABLE' => is_dir($uploadTmpDir) && is_writable($uploadTmpDir),
    'FILE_UPLOADS' => filter_var(ini_get('file_uploads'), FILTER_VALIDATE_BOOL),
];

foreach ($checks as $label => $ok) {
    echo ($ok ? 'OK' : 'FAIL').' '.$label.PHP_EOL;
}

echo 'NOTE WEB_UPLOAD CLI-only check; for browser multipart use scripts/probe-web-php-upload.php and scripts/start-local-web-server.ps1 on Windows.'.PHP_EOL;

exit(in_array(false, $checks, true) ? 1 : 0);
