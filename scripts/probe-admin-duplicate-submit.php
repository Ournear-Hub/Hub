<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Application;
use App\Models\EmployerSubmission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

$application = Application::query()->where('business_id', 'ONT-A-000001')->firstOrFail();
$admin = User::query()->where('email', env('LOCAL_ADMIN_EMAIL', 'admin@ournear.test'))->firstOrFail();

$before = EmployerSubmission::query()->where('application_id', $application->id)->count();
echo 'submissions_before='.$before.PHP_EOL;
echo 'submit_blocked_reason='.$application->submitBlockedReason().PHP_EOL;
echo 'can_submit='.($application->submitBlockedReason() === null ? 'yes' : 'no').PHP_EOL;

Auth::guard('admin')->login($admin);
$kernel = app(Illuminate\Contracts\Http\Kernel::class);
$request = Request::create('/admin/applications/'.$application->id.'/submit', 'POST');
$request->setUserResolver(fn () => $admin);
$response = $kernel->handle($request);
$kernel->terminate($request, $response);
Auth::guard('admin')->logout();

$after = EmployerSubmission::query()->where('application_id', $application->id)->count();
echo 'http_status='.$response->getStatusCode().PHP_EOL;
echo 'submissions_after='.$after.PHP_EOL;
if ($response->getStatusCode() === 302 && $response->isRedirect()) {
    echo 'redirect='.$response->headers->get('Location').PHP_EOL;
}
$session = $response->getSession();
if ($session !== null && $session->has('errors')) {
    echo 'errors='.json_encode($session->get('errors'), JSON_THROW_ON_ERROR).PHP_EOL;
}
