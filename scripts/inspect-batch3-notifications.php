<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Application;
use App\Models\User;
use Illuminate\Support\Facades\DB;

$application = Application::query()->where('business_id', 'ONT-A-000001')->first();
if ($application === null) {
    echo "no fixture application\n";
    exit(1);
}

$emails = [
    'qa-candidate@example.test',
    'qa-employer-a@example.test',
    'admin@ournear.test',
];

foreach ($emails as $email) {
    $user = User::query()->where('email', $email)->first();
    if ($user === null) {
        echo $email." NOT FOUND\n";
        continue;
    }
    $total = $user->notifications()->count();
    $unread = $user->unreadNotifications()->count();
    $recent = $user->notifications()->latest()->limit(5)->get(['id', 'type', 'data', 'read_at', 'created_at']);
    echo "=== {$email} total={$total} unread={$unread} ===\n";
    foreach ($recent as $n) {
        $data = is_array($n->data) ? $n->data : json_decode((string) $n->data, true);
        $title = is_array($data) ? ($data['title'] ?? $data['message'] ?? '') : '';
        echo '  '.$n->created_at.' type='.class_basename($n->type).' read='.($n->read_at ? 'yes' : 'no').' title='.substr((string) $title, 0, 80).PHP_EOL;
    }
}

echo 'employer_submissions_for_app='.$application->submissions()->count().PHP_EOL;
echo 'recruitment_activities_submitted='.DB::table('recruitment_activities')
    ->where('application_id', $application->id)
    ->where('type', 'candidate_submitted')
    ->count().PHP_EOL;
