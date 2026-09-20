<?php

use App\Http\Controllers\Admin\ApplicationsController;
use App\Http\Controllers\Admin\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Admin\Auth\NewPasswordController;
use App\Http\Controllers\Admin\Auth\PasswordResetLinkController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EmployerEnquiriesController;
use App\Http\Controllers\Admin\JobsController;
use App\Enums\MasterEntityType;
use App\Http\Controllers\Admin\MasterDataController;
use App\Http\Controllers\Admin\MediaSocialController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\WebsiteContentController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
        Route::post('login', [AuthenticatedSessionController::class, 'store'])
            ->middleware('throttle:admin-login')
            ->name('login.store');

        Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
        Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
            ->middleware('throttle:admin-password-reset')
            ->name('password.email');

        Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
        Route::post('reset-password', [NewPasswordController::class, 'store'])
            ->middleware('throttle:password-reset-submit')
            ->name('password.store');
    });

    Route::middleware(['auth', 'admin'])->group(function () {
        Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

        Route::get('/', DashboardController::class)->name('dashboard');

        Route::get('jobs', [JobsController::class, 'index'])->name('jobs.index');
        Route::get('jobs/create', [JobsController::class, 'create'])->name('jobs.create');
        Route::post('jobs', [JobsController::class, 'store'])->name('jobs.store');
        Route::get('jobs/{job}', [JobsController::class, 'show'])->name('jobs.show');
        Route::get('jobs/{job}/edit', [JobsController::class, 'edit'])->name('jobs.edit');
        Route::put('jobs/{job}', [JobsController::class, 'update'])->name('jobs.update');
        Route::delete('jobs/{job}', [JobsController::class, 'destroy'])->name('jobs.destroy');
        Route::patch('jobs/{job}/publish', [JobsController::class, 'togglePublish'])->name('jobs.publish');
        Route::patch('jobs/{job}/active', [JobsController::class, 'toggleActive'])->name('jobs.active');
        Route::patch('jobs/{job}/featured', [JobsController::class, 'toggleFeatured'])->name('jobs.featured');

        Route::get('applications', [ApplicationsController::class, 'index'])->name('applications.index');
        Route::patch('applications/{application}/status', [ApplicationsController::class, 'updateStatus'])->name('applications.status');
        Route::post('applications/{application}/notes', [ApplicationsController::class, 'storeNote'])->name('applications.notes');
        Route::put('applications/{application}/interview', [ApplicationsController::class, 'saveInterview'])->name('applications.interview');
        Route::get('applications/{application}/resume', [ApplicationsController::class, 'resume'])->name('applications.resume');
        Route::get('employer-enquiries', [EmployerEnquiriesController::class, 'index'])->name('employer-enquiries.index');
        Route::patch('employer-enquiries/{employerEnquiry}/status', [EmployerEnquiriesController::class, 'updateStatus'])->name('employer-enquiries.status');
        Route::post('employer-enquiries/{employerEnquiry}/notes', [EmployerEnquiriesController::class, 'storeNote'])->name('employer-enquiries.notes');

        Route::get('website-content', [WebsiteContentController::class, 'index'])->name('website-content.index');
        Route::put('website-content/{page}', [WebsiteContentController::class, 'update'])->name('website-content.update');
        Route::post('website-content/{page}/reset', [WebsiteContentController::class, 'reset'])->name('website-content.reset');
        Route::patch('website-content/contact-enquiries/{contactEnquiry}/status', [WebsiteContentController::class, 'updateContactStatus'])->name('website-content.contact-enquiries.status');
        Route::post('website-content/contact-enquiries/{contactEnquiry}/notes', [WebsiteContentController::class, 'storeContactNote'])->name('website-content.contact-enquiries.notes');
        Route::get('media-social', [MediaSocialController::class, 'index'])->name('media-social.index');
        Route::post('media-social/media', [MediaSocialController::class, 'store'])->name('media-social.media.store');
        Route::delete('media-social/media/{media}', [MediaSocialController::class, 'destroy'])->name('media-social.media.destroy');
        Route::put('media-social/social', [MediaSocialController::class, 'updateSocial'])->name('media-social.social.update');

        $masterTypes = implode('|', array_column(MasterEntityType::cases(), 'value'));

        Route::get('master-data', [MasterDataController::class, 'index'])->name('master-data.index');
        Route::post('master-data/{type}', [MasterDataController::class, 'store'])
            ->where('type', $masterTypes)
            ->name('master-data.store');
        Route::put('master-data/{type}/{id}', [MasterDataController::class, 'update'])
            ->where('type', $masterTypes)
            ->name('master-data.update');
        Route::patch('master-data/{type}/{id}/status', [MasterDataController::class, 'toggleStatus'])
            ->where('type', $masterTypes)
            ->name('master-data.status');

        Route::get('settings', [SettingsController::class, 'edit'])->name('settings.edit');
        Route::put('settings/website', [SettingsController::class, 'updateWebsite'])->name('settings.website');
        Route::put('settings/branding', [SettingsController::class, 'updateBranding'])->name('settings.branding');
        Route::put('settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.profile');
        Route::put('settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password');
    });
});
