<?php

declare(strict_types=1);

/**
 * Verify Laravel can connect to the configured local MySQL database (read-only).
 */

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$database = (string) config('database.connections.mysql.database');
$host = (string) config('database.connections.mysql.host');
$port = (string) config('database.connections.mysql.port');

$t = microtime(true);

try {
    Illuminate\Support\Facades\DB::connection()->getPdo();
    Illuminate\Support\Facades\DB::select('SELECT 1 AS ok');
} catch (Throwable $e) {
    fwrite(STDERR, 'MYSQL_FAIL host='.$host.' port='.$port.' database='.$database.PHP_EOL);
    fwrite(STDERR, get_class($e).': '.$e->getMessage().PHP_EOL);
    exit(1);
}

$ms = (int) round((microtime(true) - $t) * 1000);
echo 'MYSQL_OK host='.$host.' port='.$port.' database='.$database.' ms='.$ms.PHP_EOL;
