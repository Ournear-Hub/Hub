<?php

use App\Http\Controllers\Account\AuthenticatedSessionController;
use App\Http\Controllers\Account\CandidateApplicationsController;
use App\Http\Controllers\Account\CandidateNotificationsController;
use App\Http\Controllers\Account\CandidateDocumentController;
use App\Http\Controllers\Account\CandidateProfessionalProfileController;
use App\Http\Controllers\Account\CandidateResumeController;
use App\Http\Controllers\Account\EmailVerificationController;
use App\Http\Controllers\Account\EmployerEnquiriesController;
use App\Http\Controllers\Account\EmployerJobsController;
use App\Http\Controllers\Account\NewPasswordController;
use App\Http\Controllers\Account\PasswordResetLinkController;
use App\Http\Controllers\Account\ProfileController;
use App\Http\Controllers\Account\RegisteredUserController;
use App\Http\Controllers\Account\SavedJobController;
use App\Http\Controllers\Account\SettingsController;
use App\Http\Controllers\Account\UsernameAvailabilityController;
use Illuminate\Support\Facades\Route;

Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');

Route::middleware('guest:account')->group(function () {
    Route::get('/register/check-username', UsernameAvailabilityController::class)
        ->middleware('throttle:60,1')
        ->name('register.check-username');
    Route::post('/register', [RegisteredUserController::class, 'store'])
        ->middleware('throttle:account-register')
        ->name('register.store');

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

Route::middleware('auth:account')->group(function () {
    Route::post('/account/switch', [AuthenticatedSessionController::class, 'switch'])
        ->name('account.switch');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->whereNumber('id')
        ->name('verification.verify');
});

Route::middleware(['auth:account', 'account.member'])->prefix('account')->name('account.')->group(function () {
    Route::get('/', [ProfileController::class, 'home'])->name('home');
    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('profile/photo', [ProfileController::class, 'updatePhoto'])
        ->middleware('throttle:account-photo')
        ->name('profile.photo.update');
    Route::delete('profile/photo', [ProfileController::class, 'destroyPhoto'])->name('profile.photo.destroy');

    Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::get('settings/account', [SettingsController::class, 'account'])->name('settings.account');
    Route::put('settings/account', [SettingsController::class, 'updateAccount'])->name('settings.account.update');
    Route::redirect('settings/profile', '/account/profile')->name('settings.profile');
    Route::get('settings/security', [SettingsController::class, 'security'])->name('settings.security');
    Route::put('settings/password', [SettingsController::class, 'updatePassword'])
        ->middleware('throttle:account-password-change')
        ->name('settings.password.update');
    Route::post('settings/verification', [EmailVerificationController::class, 'send'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::middleware('account.candidate')->group(function () {
        Route::put('profile/professional', [CandidateProfessionalProfileController::class, 'update'])
            ->name('profile.professional.update');
        Route::post('profile/skills', [CandidateProfessionalProfileController::class, 'storeSkill'])
            ->name('profile.skills.store');
        Route::delete('profile/skills/{skill}', [CandidateProfessionalProfileController::class, 'destroySkill'])
            ->whereNumber('skill')
            ->name('profile.skills.destroy');
        Route::post('profile/employment-history', [CandidateProfessionalProfileController::class, 'storeHistory'])
            ->name('profile.employment-history.store');
        Route::put('profile/employment-history/{history}', [CandidateProfessionalProfileController::class, 'updateHistory'])
            ->whereNumber('history')
            ->name('profile.employment-history.update');
        Route::delete('profile/employment-history/{history}', [CandidateProfessionalProfileController::class, 'destroyHistory'])
            ->whereNumber('history')
            ->name('profile.employment-history.destroy');

        Route::get('saved-jobs', [SavedJobController::class, 'index'])->name('saved-jobs.index');
        Route::post('saved-jobs/{job}', [SavedJobController::class, 'store'])
            ->middleware('throttle:account-saved-jobs')
            ->name('saved-jobs.store');
        Route::delete('saved-jobs/{job}', [SavedJobController::class, 'destroy'])
            ->name('saved-jobs.destroy');

        Route::get('notifications', [CandidateNotificationsController::class, 'index'])->name('notifications.index');
        Route::post('notifications/read-all', [CandidateNotificationsController::class, 'markAllRead'])
            ->name('notifications.read-all');
        Route::post('notifications/{notification}/read', [CandidateNotificationsController::class, 'markRead'])
            ->whereUuid('notification')
            ->name('notifications.read');

        Route::get('settings/communication', [SettingsController::class, 'communication'])
            ->name('settings.communication');
        Route::put('settings/communication', [SettingsController::class, 'updateCommunication'])
            ->name('settings.communication.update');

        Route::get('applications', [CandidateApplicationsController::class, 'index'])->name('applications.index');
        Route::get('applications/{application}', [CandidateApplicationsController::class, 'show'])
            ->whereNumber('application')
            ->name('applications.show');
        Route::patch('applications/{application}/withdraw', [CandidateApplicationsController::class, 'withdraw'])
            ->whereNumber('application')
            ->name('applications.withdraw');
        Route::get('applications/{application}/resume', [CandidateApplicationsController::class, 'resume'])
            ->whereNumber('application')
            ->name('applications.resume');
        Route::post('applications/{application}/resume', [CandidateApplicationsController::class, 'replaceResume'])
            ->whereNumber('application')
            ->name('applications.resume.replace');

        Route::get('resumes', [CandidateResumeController::class, 'index'])->name('resumes.index');
        Route::get('resumes/builder', [CandidateResumeController::class, 'builder'])->name('resumes.builder');
        Route::post('resumes', [CandidateResumeController::class, 'store'])
            ->middleware('throttle:account-files')
            ->name('resumes.store');
        Route::put('resumes/{resume}', [CandidateResumeController::class, 'update'])
            ->whereNumber('resume')
            ->name('resumes.update');
        Route::post('resumes/{resume}/replace', [CandidateResumeController::class, 'replace'])
            ->middleware('throttle:account-files')
            ->whereNumber('resume')
            ->name('resumes.replace');
        Route::patch('resumes/{resume}/primary', [CandidateResumeController::class, 'primary'])
            ->whereNumber('resume')
            ->name('resumes.primary');
        Route::patch('resumes/{resume}/archive', [CandidateResumeController::class, 'archive'])
            ->whereNumber('resume')
            ->name('resumes.archive');
        Route::delete('resumes/{resume}', [CandidateResumeController::class, 'destroy'])
            ->whereNumber('resume')
            ->name('resumes.destroy');
        Route::get('resumes/{resume}/download', [CandidateResumeController::class, 'download'])
            ->whereNumber('resume')
            ->name('resumes.download');
        Route::get('resumes/{resume}/view', [CandidateResumeController::class, 'view'])
            ->whereNumber('resume')
            ->name('resumes.view');

        Route::get('documents', [CandidateDocumentController::class, 'index'])->name('documents.index');
        Route::post('documents', [CandidateDocumentController::class, 'store'])
            ->middleware('throttle:account-files')
            ->name('documents.store');
        Route::put('documents/{document}', [CandidateDocumentController::class, 'update'])
            ->whereNumber('document')
            ->name('documents.update');
        Route::post('documents/{document}/replace', [CandidateDocumentController::class, 'replace'])
            ->middleware('throttle:account-files')
            ->whereNumber('document')
            ->name('documents.replace');
        Route::delete('documents/{document}', [CandidateDocumentController::class, 'destroy'])
            ->whereNumber('document')
            ->name('documents.destroy');
        Route::get('documents/{document}/download', [CandidateDocumentController::class, 'download'])
            ->whereNumber('document')
            ->name('documents.download');
        Route::get('documents/{document}/view', [CandidateDocumentController::class, 'view'])
            ->whereNumber('document')
            ->name('documents.view');
    });

    Route::middleware('account.employer')->group(function () {
        Route::get('enquiries', [EmployerEnquiriesController::class, 'index'])->name('enquiries.index');
        Route::get('jobs', [EmployerJobsController::class, 'index'])->name('employer.jobs.index');
        Route::get('jobs/create', [EmployerJobsController::class, 'create'])->name('employer.jobs.create');
        Route::post('jobs', [EmployerJobsController::class, 'store'])->name('employer.jobs.store');
        Route::get('jobs/{job}/edit', [EmployerJobsController::class, 'edit'])->name('employer.jobs.edit');
        Route::put('jobs/{job}', [EmployerJobsController::class, 'update'])->name('employer.jobs.update');
        Route::delete('jobs/{job}', [EmployerJobsController::class, 'destroy'])->name('employer.jobs.destroy');
        Route::get('jobs/{job}/applicants', [EmployerJobsController::class, 'applicants'])
            ->name('employer.jobs.applicants');
        Route::get('employer-applications/{application}/resume', [EmployerJobsController::class, 'resume'])
            ->whereNumber('application')
            ->name('employer.applications.resume');
    });
});
