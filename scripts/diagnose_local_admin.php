<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

$db = (string) config('database.connections.mysql.database');
$email = strtolower(trim((string) env('LOCAL_ADMIN_EMAIL', 'admin@ournear.test')));
$localPass = (string) env('LOCAL_ADMIN_PASSWORD', '');

echo 'database='.$db.PHP_EOL;
echo 'app_env='.config('app.env').PHP_EOL;

$user = User::query()->where('email', $email)->first();
if (! $user) {
    echo 'user_exists=NO'.PHP_EOL;
    echo 'duplicate_emails='.User::query()->where('email', 'like', '%admin@ournear%')->count().PHP_EOL;
    exit(1);
}

echo 'user_exists=YES'.PHP_EOL;
echo 'user_id='.$user->id.PHP_EOL;
echo 'is_active='.($user->is_active ? 'YES' : 'NO').PHP_EOL;
echo 'is_admin='.($user->isAdmin() ? 'YES' : 'NO').PHP_EOL;
echo 'can_sign_in='.($user->canSignIn() ? 'YES' : 'NO').PHP_EOL;
echo 'must_change_password='.($user->must_change_password ? 'YES' : 'NO').PHP_EOL;
echo 'roles_count='.$user->roles()->count().PHP_EOL;
echo 'local_password_configured='.($localPass !== '' ? 'YES' : 'NO').PHP_EOL;

if ($localPass === '') {
    echo 'password_match=UNKNOWN'.PHP_EOL;
} else {
    echo 'password_match='.(Hash::check($localPass, $user->password) ? 'YES' : 'NO').PHP_EOL;
}

$attempt = Auth::guard('admin')->attempt(['email' => $email, 'password' => $localPass], false);
echo 'guard_attempt='.($attempt ? 'YES' : 'NO').PHP_EOL;

exit(0);
