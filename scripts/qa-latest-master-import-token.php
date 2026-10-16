<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\MasterDataImportPreview;
use App\Models\User;

if ($argc < 3) {
    fwrite(STDERR, "Usage: php qa-latest-master-import-token.php <entity_type> <user_email>\n");
    exit(1);
}

$user = User::query()->where('email', $argv[2])->first();
if ($user === null) {
    exit(2);
}

$preview = MasterDataImportPreview::query()
    ->where('user_id', $user->id)
    ->where('entity_type', $argv[1])
    ->orderByDesc('id')
    ->first();

if ($preview === null) {
    exit(3);
}

echo $preview->token.PHP_EOL;
