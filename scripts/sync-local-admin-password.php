<?php

declare(strict_types=1);

/**
 * LOCAL ONLY: Re-hash admin password from LOCAL_ADMIN_PASSWORD in .env.
 * Usage: php scripts/sync-local-admin-password.php
 */

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

if (! app()->environment('local')) {
    fwrite(STDERR, "Refused: APP_ENV is not local.\n");
    exit(1);
}

$email = strtolower(trim((string) env('LOCAL_ADMIN_EMAIL', 'admin@ournear.test')));
$password = env('LOCAL_ADMIN_PASSWORD');

if (! is_string($password) || trim($password) === '') {
    fwrite(STDERR, "Set LOCAL_ADMIN_PASSWORD in .env first.\n");
    exit(1);
}

$user = App\Models\User::query()->where('email', $email)->first();
if (! $user) {
    fwrite(STDERR, "Admin user not found: {$email}\n");
    exit(1);
}

$user->forceFill([
    'password' => Illuminate\Support\Facades\Hash::make($password),
    'must_change_password' => false,
    'is_active' => true,
])->save();

echo "OK synced password for {$email}\n";
