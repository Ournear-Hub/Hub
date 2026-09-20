<?php

use App\Http\Controllers\PublicDiscoveryController;
use App\Http\Controllers\PublicSiteController;
use Illuminate\Support\Facades\Route;

Route::get('/robots.txt', [PublicDiscoveryController::class, 'robots'])->name('robots');
Route::get('/sitemap.xml', [PublicDiscoveryController::class, 'sitemap'])->name('sitemap');

Route::get('/', [PublicSiteController::class, 'home'])->name('home');
Route::get('/jobs', [PublicSiteController::class, 'jobs'])->name('jobs.index');
Route::get('/jobs/{job}/apply', [PublicSiteController::class, 'jobApply'])
    ->where('job', '[A-Za-z0-9\-]+')
    ->name('jobs.apply');
Route::post('/jobs/{job}/apply', [PublicSiteController::class, 'jobApplyStore'])
    ->middleware('throttle:job-applications')
    ->where('job', '[A-Za-z0-9\-]+')
    ->name('jobs.apply.store');
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
Route::get('/terms-and-conditions', [PublicSiteController::class, 'terms'])->name('terms');

require __DIR__.'/account.php';
require __DIR__.'/admin.php';
