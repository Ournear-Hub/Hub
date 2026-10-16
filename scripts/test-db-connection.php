<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

(Dotenv\Dotenv::createImmutable(dirname(__DIR__)))->safeLoad();

$host = (string) env('DB_HOST', '127.0.0.1');
$port = (string) env('DB_PORT', '3306');
$db = (string) env('DB_DATABASE', '');
$user = (string) env('DB_USERNAME', 'root');
$pass = (string) env('DB_PASSWORD', '');

try {
    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$db}",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
    );
    $name = $pdo->query('SELECT DATABASE()')->fetchColumn();
    echo 'OK connected database='.(string) $name.PHP_EOL;
    exit(0);
} catch (Throwable $e) {
    echo 'FAIL '.$e->getMessage().PHP_EOL;
    exit(1);
}
