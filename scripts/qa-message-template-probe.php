<?php

declare(strict_types=1);

/**
 * Read message template row + recent audits for QA browser closure.
 * Usage: php scripts/qa-message-template-probe.php <template_key>
 */

if ($argc < 2) {
    fwrite(STDERR, "Usage: php qa-message-template-probe.php <template_key>\n");
    exit(1);
}

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\MessageTemplate;
use App\Models\MessageTemplateAudit;

$key = trim($argv[1]);
$template = MessageTemplate::query()->where('template_key', $key)->first();

if ($template === null) {
    echo "found=0\n";
    exit(0);
}

echo "found=1\n";
echo 'id='.$template->id."\n";
echo 'template_key='.$template->template_key."\n";
echo 'channel='.$template->channel->value."\n";
echo 'audience='.$template->audience->value."\n";
echo 'is_active='.($template->is_active ? '1' : '0')."\n";
echo 'is_system_required='.($template->is_system_required ? '1' : '0')."\n";
echo 'body_hash='.md5((string) $template->body)."\n";
echo 'subject='.(string) ($template->subject ?? '')."\n";

$audits = MessageTemplateAudit::query()
    ->where('message_template_id', $template->id)
    ->orderByDesc('id')
    ->limit(10)
    ->get(['action', 'created_at']);

foreach ($audits as $index => $audit) {
    echo 'audit_'.$index.'='.$audit->action.'@'.$audit->created_at?->toIso8601String()."\n";
}

exit(0);
