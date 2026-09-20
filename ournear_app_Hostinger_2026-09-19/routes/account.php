<?php

use App\Http\Controllers\Account\AuthenticatedSessionController;
use App\Http\Controllers\Account\CandidateApplicationsController;
use App\Http\Controllers\Account\EmployerEnquiriesController;
use App\Http\Controllers\Account\NewPasswordController;
use App\Http\Controllers\Account\PasswordResetLinkController;
use App\Http\Controllers\Account\ProfileController;
use App\Http\Controllers\Account\RegisteredUserController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])
        ->middleware('throttle:account-register')
        ->name('register.store');

    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:account-login')
        ->name('login.store');

    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:account-password-reset')
        ->name('password.email');

    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])
        ->middleware('throttle:password-reset-submit')
        ->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});

Route::middleware(['auth', 'account.member'])->prefix('account')->name('account.')->group(function () {
    Route::get('/', [ProfileController::class, 'home'])->name('home');
    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::middleware('account.candidate')->group(function () {
        Route::get('applications', [CandidateApplicationsController::class, 'index'])->name('applications.index');
        Route::patch('applications/{application}/withdraw', [CandidateApplicationsController::class, 'withdraw'])
            ->name('applications.withdraw');
        Route::get('applications/{application}/resume', [CandidateApplicationsController::class, 'resume'])
            ->name('applications.resume');
        Route::post('applications/{application}/resume', [CandidateApplicationsController::class, 'replaceResume'])
            ->name('applications.resume.replace');
    });

    Route::middleware('account.employer')->group(function () {
        Route::get('enquiries', [EmployerEnquiriesController::class, 'index'])->name('enquiries.index');
    });
});
