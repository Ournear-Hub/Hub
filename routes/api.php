<?php

use App\Http\Controllers\Api\V1\MobileApplicationController;
use App\Http\Controllers\Api\V1\MobileAuthController;
use App\Http\Controllers\Api\V1\PublicSiteBrandingController;
use App\Http\Controllers\Api\V1\MobileCandidateMessagingController;
use App\Http\Controllers\Api\V1\MobileCandidateNotificationController;
use App\Http\Controllers\Api\V1\MobileCandidateProfileController;
use App\Http\Controllers\Api\V1\MobileCandidateResumeController;
use App\Http\Controllers\Api\V1\MobileEmailVerificationController;
use App\Http\Controllers\Api\V1\MobileJobApplicationController;
use App\Http\Controllers\Api\V1\MobileSavedJobController;
use App\Http\Controllers\Api\V1\PublicJobController;
use App\Http\Controllers\Api\V1\PublicSiteContactController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/site/contact', [PublicSiteContactController::class, 'show'])->name('api.v1.site.contact');
    Route::get('/site/branding', [PublicSiteBrandingController::class, 'show'])->name('api.v1.site.branding');

    Route::middleware('sanctum.optional')->group(function (): void {
        Route::get('/jobs/filters', [PublicJobController::class, 'filters'])->name('api.v1.jobs.filters');
        Route::get('/jobs', [PublicJobController::class, 'index'])->name('api.v1.jobs.index');
        Route::get('/jobs/{job:slug}', [PublicJobController::class, 'show'])
            ->where('job', '[A-Za-z0-9\-]+')
            ->name('api.v1.jobs.show');
    });

    Route::post('/auth/register', [MobileAuthController::class, 'register'])
        ->middleware('throttle:account-register')
        ->name('api.v1.auth.register');

    Route::post('/auth/forgot-password', [MobileAuthController::class, 'forgotPassword'])
        ->middleware('throttle:account-register')
        ->name('api.v1.auth.forgot-password');

    Route::post('/auth/login', [MobileAuthController::class, 'login'])
        ->middleware('throttle:account-login')
        ->name('api.v1.auth.login');

    Route::middleware(['auth:sanctum', 'api.account'])->group(function (): void {
        Route::get('/account', [MobileAuthController::class, 'show'])->name('api.v1.account.show');
        Route::post('/auth/logout', [MobileAuthController::class, 'logout'])->name('api.v1.auth.logout');

        Route::middleware('api.candidate')->group(function (): void {
            Route::get('/account/profile/personal', [MobileCandidateProfileController::class, 'showPersonal'])
                ->name('api.v1.account.profile.personal.show');
            Route::match(['put', 'patch'], '/account/profile/personal', [MobileCandidateProfileController::class, 'updatePersonal'])
                ->name('api.v1.account.profile.personal.update');

            Route::get('/account/profile/professional', [MobileCandidateProfileController::class, 'showProfessional'])
                ->name('api.v1.account.profile.professional.show');
            Route::match(['put', 'patch'], '/account/profile/professional', [MobileCandidateProfileController::class, 'updateProfessional'])
                ->name('api.v1.account.profile.professional.update');

            Route::post('/account/profile/professional/skills', [MobileCandidateProfileController::class, 'storeSkill'])
                ->name('api.v1.account.profile.professional.skills.store');
            Route::delete('/account/profile/professional/skills/{skill}', [MobileCandidateProfileController::class, 'destroySkill'])
                ->name('api.v1.account.profile.professional.skills.destroy');

            Route::post('/account/profile/professional/employment-histories', [MobileCandidateProfileController::class, 'storeHistory'])
                ->name('api.v1.account.profile.professional.employment-histories.store');
            Route::match(['put', 'patch'], '/account/profile/professional/employment-histories/{history}', [MobileCandidateProfileController::class, 'updateHistory'])
                ->name('api.v1.account.profile.professional.employment-histories.update');
            Route::delete('/account/profile/professional/employment-histories/{history}', [MobileCandidateProfileController::class, 'destroyHistory'])
                ->name('api.v1.account.profile.professional.employment-histories.destroy');

            Route::post('/account/profile/professional/educations', [MobileCandidateProfileController::class, 'storeEducation'])
                ->name('api.v1.account.profile.professional.educations.store');
            Route::match(['put', 'patch'], '/account/profile/professional/educations/{education}', [MobileCandidateProfileController::class, 'updateEducation'])
                ->name('api.v1.account.profile.professional.educations.update');
            Route::delete('/account/profile/professional/educations/{education}', [MobileCandidateProfileController::class, 'destroyEducation'])
                ->name('api.v1.account.profile.professional.educations.destroy');

            Route::post('/account/profile/professional/certifications', [MobileCandidateProfileController::class, 'storeCertification'])
                ->name('api.v1.account.profile.professional.certifications.store');
            Route::match(['put', 'patch'], '/account/profile/professional/certifications/{certification}', [MobileCandidateProfileController::class, 'updateCertification'])
                ->name('api.v1.account.profile.professional.certifications.update');
            Route::delete('/account/profile/professional/certifications/{certification}', [MobileCandidateProfileController::class, 'destroyCertification'])
                ->name('api.v1.account.profile.professional.certifications.destroy');

            Route::get('/account/resumes', [MobileCandidateResumeController::class, 'index'])
                ->name('api.v1.account.resumes.index');
            Route::get('/account/resumes/builder', [MobileCandidateResumeController::class, 'builder'])
                ->name('api.v1.account.resumes.builder');
            Route::post('/account/resumes', [MobileCandidateResumeController::class, 'store'])
                ->middleware('throttle:account-files')
                ->name('api.v1.account.resumes.store');
            Route::match(['put', 'patch'], '/account/resumes/{resume}', [MobileCandidateResumeController::class, 'update'])
                ->name('api.v1.account.resumes.update');
            Route::post('/account/resumes/{resume}/replace', [MobileCandidateResumeController::class, 'replace'])
                ->middleware('throttle:account-files')
                ->name('api.v1.account.resumes.replace');
            Route::post('/account/resumes/{resume}/primary', [MobileCandidateResumeController::class, 'makePrimary'])
                ->name('api.v1.account.resumes.primary');
            Route::delete('/account/resumes/{resume}', [MobileCandidateResumeController::class, 'destroy'])
                ->name('api.v1.account.resumes.destroy');
            Route::get('/account/resumes/{resume}/download', [MobileCandidateResumeController::class, 'download'])
                ->name('api.v1.account.resumes.download');
            Route::get('/account/resumes/{resume}/view', [MobileCandidateResumeController::class, 'view'])
                ->name('api.v1.account.resumes.view');

            Route::get('/account/saved-jobs', [MobileSavedJobController::class, 'index'])
                ->name('api.v1.account.saved-jobs.index');
            Route::post('/account/saved-jobs/{job:slug}', [MobileSavedJobController::class, 'store'])
                ->where('job', '[A-Za-z0-9\-]+')
                ->name('api.v1.account.saved-jobs.store');
            Route::delete('/account/saved-jobs/{job:slug}', [MobileSavedJobController::class, 'destroy'])
                ->where('job', '[A-Za-z0-9\-]+')
                ->name('api.v1.account.saved-jobs.destroy');

            Route::get('/account/notifications', [MobileCandidateNotificationController::class, 'index'])
                ->name('api.v1.account.notifications.index');
            Route::post('/account/notifications/read-all', [MobileCandidateNotificationController::class, 'markAllRead'])
                ->name('api.v1.account.notifications.read-all');
            Route::post('/account/notifications/{notification}', [MobileCandidateNotificationController::class, 'markRead'])
                ->name('api.v1.account.notifications.read');

            Route::get('/account/messages/inbox', [MobileCandidateMessagingController::class, 'inbox'])
                ->name('api.v1.account.messages.inbox');
            Route::get('/account/applications/{application}/messages', [MobileCandidateMessagingController::class, 'show'])
                ->name('api.v1.account.applications.messages.show');
            Route::post('/account/applications/{application}/messages', [MobileCandidateMessagingController::class, 'store'])
                ->middleware('throttle:6,1')
                ->name('api.v1.account.applications.messages.store');

            Route::get('/account/applications', [MobileApplicationController::class, 'index'])
                ->name('api.v1.account.applications.index');
            Route::get('/account/applications/{application}', [MobileApplicationController::class, 'show'])
                ->name('api.v1.account.applications.show');
            Route::get('/account/applications/{application}/resume', [MobileApplicationController::class, 'resume'])
                ->name('api.v1.account.applications.resume');

            Route::get('/account/verification/email', [MobileEmailVerificationController::class, 'show'])
                ->name('api.v1.account.verification.email.show');
            Route::post('/account/verification/email/resend', [MobileEmailVerificationController::class, 'resend'])
                ->middleware('throttle:6,1')
                ->name('api.v1.account.verification.email.resend');

            Route::post('/jobs/{job:slug}/apply', [MobileJobApplicationController::class, 'store'])
                ->middleware('throttle:job-applications')
                ->where('job', '[A-Za-z0-9\-]+')
                ->name('api.v1.jobs.apply');
        });
    });
});
