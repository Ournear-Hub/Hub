<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = App\Models\User::query()->where('email', 'qa-unverified-publish@example.test')->firstOrFail();
$profile = $user->employerProfile;
$draft = App\Models\Job::query()
    ->where('employer_id', $profile->id)
    ->where('slug', 'qa-journey-publish-role-second')
    ->first();

if ($draft === null) {
    echo "no draft job\n";
    exit(1);
}

$lifecycle = app(App\Services\JobLifecycleService::class);
try {
    $lifecycle->publish($draft, $user);
    echo "publish succeeded unexpectedly\n";
} catch (Illuminate\Validation\ValidationException $e) {
    echo 'blocked: '.json_encode($e->errors())."\n";
}

$draft->refresh();
echo 'status='.$draft->publishing_status->value."\n";
