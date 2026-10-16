<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Application;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

function probePdf(string $bytes): array
{
    return \App\Support\ReadablePdfFixture::analyze($bytes);
}

function httpProbe(string $method, string $uri, User $user, ?string $guard = 'account'): array
{
    if ($guard !== null) {
        Auth::guard($guard)->login($user);
    }
    $kernel = app(Illuminate\Contracts\Http\Kernel::class);
    $request = Request::create($uri, $method);
    $request->setUserResolver(fn () => $user);
    $response = $kernel->handle($request);
    $kernel->terminate($request, $response);
    Auth::guard($guard ?? 'account')->logout();

    $body = $response->getContent();
    $out = [
        'status' => $response->getStatusCode(),
        'content_type' => $response->headers->get('Content-Type'),
        'content_length_header' => $response->headers->get('Content-Length'),
    ];
    if ($response->getStatusCode() === 200 && is_string($body) && $body !== '') {
        $out['pdf'] = probePdf($body);
        $out['body_byte_length'] = strlen($body);
    }

    return $out;
}

$id = (int) ($argv[1] ?? 0);
$application = Application::query()->findOrFail($id);

$employerA = User::query()->where('email', 'qa-employer-a@example.test')->firstOrFail();
$employerB = User::query()->where('email', 'qa-employer-b@example.test')->firstOrFail();
$admin = User::query()->where('email', env('LOCAL_ADMIN_EMAIL', 'admin@ournear.test'))->first();

$uri = '/account/employer-applications/'.$application->id.'/resume';
echo 'application='.$application->business_id.PHP_EOL;
echo 'employer_a='.json_encode(httpProbe('GET', $uri, $employerA)).PHP_EOL;
echo 'employer_b='.json_encode(httpProbe('GET', $uri, $employerB)).PHP_EOL;
if ($admin) {
    echo 'admin='.json_encode(httpProbe('GET', '/admin/applications/'.$application->id.'/resume', $admin, 'admin')).PHP_EOL;
}
