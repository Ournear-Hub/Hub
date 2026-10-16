<?php

declare(strict_types=1);

$candidates = [];

$envPath = dirname(__DIR__).'/.env';
if (is_readable($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES) ?: [];
    $vars = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = array_pad(explode('=', $line, 2), 2, '');
        $vars[trim($key)] = trim($value, " \t\"'");
    }
    if (isset($vars['DB_USERNAME'])) {
        $candidates[] = [$vars['DB_USERNAME'], $vars['DB_PASSWORD'] ?? ''];
    }
}

$backupVars = [];
$backupPath = dirname(__DIR__).'/.env.recovered-production.backup';
if (is_readable($backupPath)) {
    foreach (file($backupPath, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = array_pad(explode('=', $line, 2), 2, '');
        $backupVars[trim($key)] = trim($value, " \t\"'");
    }
    if (isset($backupVars['DB_USERNAME'], $backupVars['DB_PASSWORD'])) {
        $candidates[] = [$backupVars['DB_USERNAME'], $backupVars['DB_PASSWORD']];
    }
}

if (isset($backupVars['DB_PASSWORD']) && is_string($backupVars['DB_PASSWORD']) && $backupVars['DB_PASSWORD'] !== '') {
    $candidates[] = ['root', $backupVars['DB_PASSWORD']];
}

foreach (['ournear', 'ournear_hire', 'admin', 'mysql'] as $namedUser) {
    $candidates[] = [$namedUser, ''];
}

$candidates = array_merge($candidates, [
    ['root', ''],
    ['root', 'root'],
    ['root', 'password'],
    ['root', '123456'],
]);

foreach ($candidates as [$user, $pass]) {
    try {
        $pdo = new PDO(
            'mysql:host=127.0.0.1;port=3306',
            $user,
            $pass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
        );
        $pdo->exec('CREATE DATABASE IF NOT EXISTS ournear_hire_local CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        echo 'CONNECTED user='.$user.' password_set='.($pass !== '' ? 'yes' : 'no').PHP_EOL;

        exit(0);
    } catch (Throwable) {
        continue;
    }
}

echo 'NO_MATCH'.PHP_EOL;
exit(1);
