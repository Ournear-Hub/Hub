<?php

declare(strict_types=1);

/**
 * Sync qa-agent/config/local.env OURNEAR_QA_ADMIN_PASSWORD from Laravel LOCAL_ADMIN_PASSWORD.
 * Does not print secret values.
 */

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$localPass = (string) env('LOCAL_ADMIN_PASSWORD', '');
$localEmail = strtolower(trim((string) env('LOCAL_ADMIN_EMAIL', 'admin@ournear.test')));

if ($localPass === '') {
    fwrite(STDERR, "LOCAL_ADMIN_PASSWORD not configured in .env\n");
    exit(2);
}

$envPath = dirname(__DIR__).'/qa-agent/config/local.env';
if (! is_file($envPath)) {
    fwrite(STDERR, "Missing qa-agent/config/local.env\n");
    exit(2);
}

$lines = file($envPath, FILE_IGNORE_NEW_LINES);
if ($lines === false) {
    exit(2);
}

$changed = false;
foreach ($lines as $i => $line) {
    if (str_starts_with($line, 'OURNEAR_QA_ADMIN_PASSWORD=')) {
        $next = 'OURNEAR_QA_ADMIN_PASSWORD='.$localPass;
        if ($lines[$i] !== $next) {
            $lines[$i] = $next;
            $changed = true;
        }
    }
    if (str_starts_with($line, 'OURNEAR_QA_ADMIN_EMAIL=')) {
        $next = 'OURNEAR_QA_ADMIN_EMAIL='.$localEmail;
        if ($lines[$i] !== $next) {
            $lines[$i] = $next;
            $changed = true;
        }
    }
}

if ($changed) {
    file_put_contents($envPath, implode(PHP_EOL, $lines).PHP_EOL);
}

$user = \App\Models\User::query()->where('email', $localEmail)->first();
$match = $user && \Illuminate\Support\Facades\Hash::check($localPass, $user->password) ? 'yes' : 'no';

echo 'database='.config('database.connections.mysql.database').PHP_EOL;
echo 'local_env_updated='.($changed ? 'yes' : 'no').PHP_EOL;
echo 'admin_user_exists='.($user ? 'yes' : 'no').PHP_EOL;
echo 'password_hash_match='.$match.PHP_EOL;

exit($match === 'yes' ? 0 : 1);
