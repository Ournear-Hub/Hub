<?php

declare(strict_types=1);

/**
 * Local/testing only: adjust master-data import preview rows for QA (expire, wrong user).
 * Usage:
 *   php scripts/qa-master-data-preview-fixture.php expire <token>
 *   php scripts/qa-master-data-preview-fixture.php reassign <token> <user_id>
 */

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\MasterDataImportPreview;

if ($argc < 3) {
    fwrite(STDERR, "Usage: expire <token> | reassign <token> <user_id>\n");
    exit(1);
}

$action = $argv[1];
$token = $argv[2];

$preview = MasterDataImportPreview::query()->where('token', $token)->first();
if ($preview === null) {
    fwrite(STDERR, "Preview not found\n");
    exit(2);
}

if ($action === 'expire') {
    $preview->update(['expires_at' => now()->subMinute()]);
    echo "expired=yes\n";
    exit(0);
}

if ($action === 'reassign-user' && isset($argv[3])) {
    $user = \App\Models\User::query()->where('email', $argv[3])->first();
    if ($user === null) {
        fwrite(STDERR, "User not found\n");
        exit(4);
    }
    $preview->update(['user_id' => $user->id]);
    echo 'user_id='.$preview->user_id."\n";
    exit(0);
}

fwrite(STDERR, "Unknown action\n");
exit(1);
