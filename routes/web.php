<?php

use App\Http\Controllers\FreelancerIntakeController;
use App\Http\Controllers\PublicDiscoveryController;
use App\Http\Controllers\PublicProfileController;
use App\Http\Controllers\PublicPlatformController;
use App\Http\Controllers\PublicSiteController;
use App\Http\Controllers\PublicSocialSearchController;
use App\Http\Controllers\UserPhotoController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::get('/robots.txt', [PublicDiscoveryController::class, 'robots'])->name('robots');
Route::get('/sitemap.xml', [PublicDiscoveryController::class, 'sitemap'])->name('sitemap');

Route::get('/', [PublicSiteController::class, 'home'])->name('home');
Route::get('/search', [PublicSocialSearchController::class, 'index'])->name('search');
Route::get('/search/suggest', [PublicSocialSearchController::class, 'suggest'])->name('search.suggest');
Route::get('/jobs', [PublicSiteController::class, 'jobs'])->name('jobs.index');
Route::get('/freelancers', [FreelancerIntakeController::class, 'create'])->name('freelancers');
Route::post('/freelancers', [FreelancerIntakeController::class, 'store'])
    ->middleware('throttle:freelancer-enquiries')
    ->name('freelancers.store');
Route::get('/freelancers/received', [FreelancerIntakeController::class, 'received'])->name('freelancers.received');
Route::get('/profiles/{user:username}', [PublicProfileController::class, 'candidate'])->name('profiles.show');
Route::get('/profile/{user:username}', function (User $user) {
    return redirect()->route('profiles.show', $user);
})->name('profile.show');
Route::get('/companies', [PublicPlatformController::class, 'companies'])->name('companies.index');
Route::get('/hire', [PublicPlatformController::class, 'hire'])->name('hire');
Route::get('/talent-pool', [PublicPlatformController::class, 'talentPool'])->name('talent-pool');
Route::get('/staffing', [PublicPlatformController::class, 'staffing'])->name('staffing');
Route::get('/development-programme', [PublicPlatformController::class, 'developmentProgramme'])->name('development-programme');
Route::get('/companies/{employer}', [PublicProfileController::class, 'company'])
    ->whereNumber('employer')
    ->name('companies.show');
Route::get('/jobs/{job}/apply', [PublicSiteController::class, 'jobApply'])
    ->where('job', '[A-Za-z0-9\-]+')
    ->name('jobs.apply');
Route::post('/jobs/{job}/apply', [PublicSiteController::class, 'jobApplyStore'])
    ->middleware(['auth:account', 'account.member', 'account.verified', 'throttle:job-applications'])
    ->where('job', '[A-Za-z0-9\-]+')
    ->name('jobs.apply.store');
Route::post('/jobs/{job}/apply/resume', [PublicSiteController::class, 'jobApplyResume'])
    ->middleware(['auth:account', 'account.member', 'account.candidate', 'throttle:account-files'])
    ->where('job', '[A-Za-z0-9\-]+')
    ->name('jobs.apply.resume');
Route::get('/users/{user}/photo', [UserPhotoController::class, 'show'])
    ->middleware('auth.either')
    ->whereNumber('user')
    ->name('users.photo.show');
Route::get('/employers/{employer}/logo', [\App\Http\Controllers\EmployerLogoController::class, 'show'])
    ->middleware('auth.either')
    ->whereNumber('employer')
    ->name('employers.logo.show');
Route::post('/jobs/{job}/share', [PublicSiteController::class, 'jobShare'])
    ->middleware('throttle:60,1')
    ->where('job', '[A-Za-z0-9\-]+')
    ->name('jobs.share');
Route::get('/jobs/{job}', [PublicSiteController::class, 'jobShow'])
    ->where('job', '[A-Za-z0-9\-]+')
    ->name('jobs.show');
Route::get('/about', [PublicSiteController::class, 'about'])->name('about');
Route::get('/services', [PublicSiteController::class, 'services'])->name('services');
Route::get('/for-employers', [PublicSiteController::class, 'forEmployers'])->name('for-employers');
Route::post('/for-employers', [PublicSiteController::class, 'storeEmployerEnquiry'])
    ->middleware('throttle:employer-enquiries')
    ->name('for-employers.store');
Route::get('/contact', [PublicSiteController::class, 'contact'])->name('contact');
Route::post('/contact', [PublicSiteController::class, 'storeContactEnquiry'])
    ->middleware('throttle:contact-enquiries')
    ->name('contact.store');
Route::get('/privacy-policy', [PublicSiteController::class, 'privacy'])->name('privacy-policy');
Route::redirect('/terms', '/terms-and-conditions');
Route::get('/terms-and-conditions', [PublicSiteController::class, 'terms'])->name('terms');

Route::get('/webhooks/whatsapp', [\App\Http\Controllers\WhatsAppWebhookController::class, 'verify'])
    ->middleware('throttle:whatsapp-webhook')
    ->name('webhooks.whatsapp.verify');
Route::post('/webhooks/whatsapp', [\App\Http\Controllers\WhatsAppWebhookController::class, 'callback'])
    ->middleware('throttle:whatsapp-webhook')
    ->name('webhooks.whatsapp.callback');

if (app()->environment('local')) {
    Route::post('/__local/upload-env-probe', function (\Illuminate\Http\Request $request) {
        $uploadTmp = ini_get('upload_tmp_dir');
        $uploadTmp = is_string($uploadTmp) && $uploadTmp !== '' ? $uploadTmp : sys_get_temp_dir();
        $file = $request->file('file');

        return response()->json([
            'sapi' => PHP_SAPI,
            'upload_tmp_dir' => $uploadTmp,
            'upload_tmp_writable' => is_dir($uploadTmp) && is_writable($uploadTmp),
            'file_uploads' => filter_var(ini_get('file_uploads'), FILTER_VALIDATE_BOOL),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'has_file' => $request->hasFile('file'),
            'file_error' => $file?->getError(),
            'file_size' => $file?->getSize(),
            'original_name' => $file?->getClientOriginalName(),
        ]);
    })
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class])
        ->name('local.upload-env-probe');
}

require __DIR__.'/account.php';
require __DIR__.'/admin.php';
