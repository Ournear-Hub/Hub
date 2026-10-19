<?php

use App\Http\Controllers\Account\AuthenticatedSessionController;
use App\Http\Controllers\Account\ContactController;
use App\Http\Controllers\Account\CandidateApplicationsController;
use App\Http\Controllers\Account\CandidateNotificationsController;
use App\Http\Controllers\Account\CandidateCertificationController;
use App\Http\Controllers\Account\CandidateDocumentController;
use App\Http\Controllers\Account\CandidateEducationController;
use App\Http\Controllers\Account\CandidateLanguageController;
use App\Http\Controllers\Account\CandidateProfessionalProfileController;
use App\Http\Controllers\Account\CandidateProjectController;
use App\Http\Controllers\Account\CandidateResumeController;
use App\Http\Controllers\Account\EmailChangeController;
use App\Http\Controllers\Account\EmailVerificationController;
use App\Http\Controllers\Account\CandidateRelationshipReviewController;
use App\Http\Controllers\Account\EmployerDocumentController;
use App\Http\Controllers\Account\EmployerEnquiriesController;
use App\Http\Controllers\Account\EmployerJobMasterDataController;
use App\Http\Controllers\Account\EmployerJobsController;
use App\Http\Controllers\Account\EmployerRelationshipReviewController;
use App\Http\Controllers\Account\EmployerSubmissionsController;
use App\Http\Controllers\Account\RecruitmentSupportController;
use App\Http\Controllers\Account\MobileVerificationController;
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

Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->middleware(['signed', 'throttle:6,1'])
    ->whereNumber('id')
    ->name('verification.verify');

Route::middleware('auth:account')->group(function () {
    Route::post('/account/switch', [AuthenticatedSessionController::class, 'switch'])
        ->name('account.switch');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});

Route::middleware(['auth:account', 'account.member'])->prefix('account')->name('account.')->group(function () {
    Route::post('email-verification-reminder/dismiss', [EmailVerificationController::class, 'dismissReminder'])
        ->name('email-verification-reminder.dismiss');
});

Route::middleware(['auth:account', 'account.member'])->prefix('account')->name('account.')->group(function () {
    Route::get('contextual-search', \App\Http\Controllers\Account\ContextualSearchController::class)
        ->middleware('throttle:60,1')
        ->name('contextual-search');
    Route::get('notifications', [CandidateNotificationsController::class, 'index'])->name('notifications.index');
    Route::post('notifications/read-all', [CandidateNotificationsController::class, 'markAllRead'])
        ->name('notifications.read-all');
    Route::post('notifications/{notification}/read', [CandidateNotificationsController::class, 'markRead'])
        ->whereUuid('notification')
        ->name('notifications.read');

    Route::get('/', [ProfileController::class, 'home'])->name('home');
    Route::get('development-programme', [\App\Http\Controllers\Account\DevelopmentProgrammeApplicationController::class, 'show'])
        ->name('development-programme.show');
    Route::post('development-programme', [\App\Http\Controllers\Account\DevelopmentProgrammeApplicationController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('development-programme.store');
    Route::get('hiring', [\App\Http\Controllers\Account\HiringDashboardController::class, 'show'])->name('hiring');
    Route::post('hiring', [\App\Http\Controllers\Account\HiringDashboardController::class, 'activate'])
        ->middleware('throttle:6,1')
        ->name('hiring.activate');
    Route::post('hiring/email/send', [\App\Http\Controllers\Account\HiringDashboardController::class, 'sendHiringEmail'])
        ->middleware('throttle:6,1')
        ->name('hiring.email.send');
    Route::get('hiring/email/verify/{id}/{hash}', [\App\Http\Controllers\Account\HiringDashboardController::class, 'verifyHiringEmail'])
        ->middleware(['signed', 'throttle:6,1'])
        ->whereNumber('id')
        ->name('hiring.email.verify');
    Route::redirect('profile/professional', '/account/profile#professional')->name('profile.professional');
    Route::get('company', [ProfileController::class, 'company'])->name('company');
    Route::post('company/logo', [ProfileController::class, 'updateCompanyLogo'])
        ->middleware(['throttle:account-photo', 'mobile.profile.block'])
        ->name('company.logo.update');
    Route::delete('company/logo', [ProfileController::class, 'destroyCompanyLogo'])
        ->middleware('mobile.profile.block')
        ->name('company.logo.destroy');
    Route::get('contact', [ContactController::class, 'show'])->name('contact');
    Route::post('contact', [ContactController::class, 'store'])
        ->middleware('throttle:contact-enquiries')
        ->name('contact.store');
    Route::redirect('profile/company', '/account/company')->name('profile.company');
    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::match(['put', 'post'], 'profile', [ProfileController::class, 'update'])
        ->middleware('mobile.profile.block')
        ->name('profile.update');
    Route::post('profile/photo', [ProfileController::class, 'updatePhoto'])
        ->middleware(['throttle:account-photo', 'mobile.profile.block'])
        ->name('profile.photo.update');
    Route::delete('profile/photo', [ProfileController::class, 'destroyPhoto'])
        ->middleware('mobile.profile.block')
        ->name('profile.photo.destroy');

    Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::get('settings/account', [SettingsController::class, 'account'])->name('settings.account');
    Route::put('settings/account', [SettingsController::class, 'updateAccount'])
        ->middleware('mobile.profile.block')
        ->name('settings.account.update');
    Route::redirect('settings/profile', '/account/profile')->name('settings.profile');
    Route::get('settings/security', [SettingsController::class, 'security'])->name('settings.security');
    Route::get('settings/verification', [SettingsController::class, 'verification'])->name('settings.verification');
    Route::get('settings/password', [SettingsController::class, 'password'])->name('settings.password');
    Route::put('settings/password', [SettingsController::class, 'updatePassword'])
        ->middleware('throttle:account-password-change')
        ->name('settings.password.update');
    Route::post('settings/verification', [EmailVerificationController::class, 'send'])
        ->middleware('throttle:6,1')
        ->name('verification.send');
    Route::post('settings/email/change', [EmailChangeController::class, 'requestChange'])
        ->middleware('throttle:6,1')
        ->name('settings.email.change');
    Route::get('settings/email/verify-change/{id}/{hash}', [EmailChangeController::class, 'verifyPendingChange'])
        ->middleware(['signed', 'throttle:6,1'])
        ->whereNumber('id')
        ->name('settings.email.verify-change');
    Route::post('settings/mobile/send-otp', [MobileVerificationController::class, 'send'])
        ->middleware('throttle:account-mobile-otp')
        ->name('settings.mobile.send-otp');
    Route::post('settings/mobile/verify', [MobileVerificationController::class, 'verify'])
        ->middleware('throttle:account-mobile-otp-verify')
        ->name('settings.mobile.verify');
    Route::post('settings/whatsapp/send-otp', [\App\Http\Controllers\Account\WhatsAppVerificationController::class, 'send'])
        ->middleware('throttle:account-whatsapp-otp')
        ->name('settings.whatsapp.send-otp');
    Route::post('settings/whatsapp/verify', [\App\Http\Controllers\Account\WhatsAppVerificationController::class, 'verify'])
        ->middleware('throttle:account-whatsapp-otp-verify')
        ->name('settings.whatsapp.verify');

    Route::get('settings/communication', [SettingsController::class, 'communication'])
        ->name('settings.communication');
    Route::put('settings/communication', [SettingsController::class, 'updateCommunication'])
        ->name('settings.communication.update');

    Route::middleware('account.candidate')->group(function () {
        Route::middleware('mobile.profile.block')->group(function () {
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
            Route::put('profile/privacy', [ProfileController::class, 'updatePrivacy'])
                ->name('profile.privacy.update');
            Route::post('profile/certifications', [CandidateCertificationController::class, 'store'])
                ->name('profile.certifications.store');
            Route::put('profile/certifications/{certification}', [CandidateCertificationController::class, 'update'])
                ->whereNumber('certification')
                ->name('profile.certifications.update');
            Route::delete('profile/certifications/{certification}', [CandidateCertificationController::class, 'destroy'])
                ->whereNumber('certification')
                ->name('profile.certifications.destroy');
            Route::post('profile/languages', [CandidateLanguageController::class, 'store'])
                ->name('profile.languages.store');
            Route::put('profile/languages/{language}', [CandidateLanguageController::class, 'update'])
                ->whereNumber('language')
                ->name('profile.languages.update');
            Route::delete('profile/languages/{language}', [CandidateLanguageController::class, 'destroy'])
                ->whereNumber('language')
                ->name('profile.languages.destroy');
            Route::post('profile/educations', [CandidateEducationController::class, 'store'])
                ->name('profile.educations.store');
            Route::put('profile/educations/{education}', [CandidateEducationController::class, 'update'])
                ->whereNumber('education')
                ->name('profile.educations.update');
            Route::delete('profile/educations/{education}', [CandidateEducationController::class, 'destroy'])
                ->whereNumber('education')
                ->name('profile.educations.destroy');
            Route::post('profile/projects', [CandidateProjectController::class, 'store'])
                ->name('profile.projects.store');
            Route::put('profile/projects/{project}', [CandidateProjectController::class, 'update'])
                ->whereNumber('project')
                ->name('profile.projects.update');
            Route::delete('profile/projects/{project}', [CandidateProjectController::class, 'destroy'])
                ->whereNumber('project')
                ->name('profile.projects.destroy');
        });

        Route::get('saved-jobs', [SavedJobController::class, 'index'])->name('saved-jobs.index');
        Route::post('saved-jobs/{job}', [SavedJobController::class, 'store'])
            ->middleware('throttle:account-saved-jobs')
            ->name('saved-jobs.store');
        Route::delete('saved-jobs/{job}', [SavedJobController::class, 'destroy'])
            ->name('saved-jobs.destroy');

        Route::get('reviews', [CandidateRelationshipReviewController::class, 'index'])->name('reviews.index');
        Route::get('reviews/{review}', [CandidateRelationshipReviewController::class, 'show'])
            ->whereNumber('review')
            ->name('reviews.show');
        Route::post('reviews/{employee}', [CandidateRelationshipReviewController::class, 'store'])
            ->whereNumber('employee')
            ->name('reviews.store');

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
        Route::post('applications/{application}/interviews/{interview}/response', [CandidateApplicationsController::class, 'respondInterview'])
            ->whereNumber(['application', 'interview'])
            ->name('applications.interviews.response');
        Route::post('applications/{application}/correction-requests', [CandidateApplicationsController::class, 'storeCorrectionRequest'])
            ->whereNumber('application')
            ->name('applications.correction-requests.store');
        Route::post('applications/{application}/messages', [CandidateApplicationsController::class, 'sendMessage'])
            ->whereNumber('application')
            ->name('applications.messages.store');

        Route::get('resumes', [CandidateResumeController::class, 'index'])->name('resumes.index');
        Route::get('resumes/builder', [CandidateResumeController::class, 'builder'])->name('resumes.builder');
        Route::get('resumes/builder/download', [CandidateResumeController::class, 'builderDownload'])->name('resumes.builder.download');
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
        Route::get('hiring/automation', [\App\Http\Controllers\Account\EmployerHiringWorkspaceController::class, 'automation'])
            ->name('hiring.automation');
        Route::get('hiring/applications', [\App\Http\Controllers\Account\EmployerHiringWorkspaceController::class, 'applications'])
            ->name('hiring.applications');
        Route::get('hiring/candidates', [\App\Http\Controllers\Account\EmployerHiringWorkspaceController::class, 'candidates'])
            ->name('hiring.candidates');
        Route::get('recruitment-support', [RecruitmentSupportController::class, 'create'])
            ->name('recruitment-support.create');
        Route::post('recruitment-support', [RecruitmentSupportController::class, 'store'])
            ->middleware('throttle:contact-enquiries')
            ->name('recruitment-support.store');
        Route::redirect('staffing/request', '/account/enquiries')->name('staffing.request.create');
        Route::get('talent-pool', [\App\Http\Controllers\Account\RecruiterTalentPoolController::class, 'index'])
            ->name('talent-pool.index');
        Route::get('talent-pool/{assignment}', [\App\Http\Controllers\Account\RecruiterTalentPoolController::class, 'show'])
            ->whereNumber('assignment')
            ->name('talent-pool.show');
        Route::put('talent-pool/{assignment}/status', [\App\Http\Controllers\Account\RecruiterTalentPoolController::class, 'updateStatus'])
            ->whereNumber('assignment')
            ->name('talent-pool.status.update');
        Route::post('talent-pool/{assignment}/archive', [\App\Http\Controllers\Account\RecruiterTalentPoolController::class, 'archive'])
            ->whereNumber('assignment')
            ->name('talent-pool.archive');
        Route::get('talent-pool/{assignment}/resume', [\App\Http\Controllers\Account\RecruiterTalentPoolController::class, 'resume'])
            ->whereNumber('assignment')
            ->name('talent-pool.resume');
        Route::post('talent-pool/{assignment}/submit-to-employer', [\App\Http\Controllers\Account\RecruiterTalentPoolController::class, 'submitToEmployer'])
            ->whereNumber('assignment')
            ->name('talent-pool.submit-to-employer');
        Route::redirect('talent-pool/jobs', '/account/hiring')->name('talent-pool.jobs.index');
        Route::redirect('talent-pool/jobs/create', '/account/hiring');
        Route::post('talent-pool/jobs', fn () => abort(404))
            ->name('talent-pool.jobs.store');
        Route::post('company/staffing-access/request', [\App\Http\Controllers\Account\CompanyStaffingAccessController::class, 'requestAccess'])
            ->name('company.staffing-access.request');
        Route::put('profile/company-privacy', [ProfileController::class, 'updateCompanyPrivacy'])
            ->middleware('mobile.profile.block')
            ->name('profile.company-privacy.update');
        Route::get('enquiries', [EmployerEnquiriesController::class, 'index'])->name('enquiries.index');
        Route::post('enquiries', [EmployerEnquiriesController::class, 'store'])
            ->middleware('throttle:contact-enquiries')
            ->name('enquiries.store');
        Route::get('enquiries/{employerEnquiry}', [EmployerEnquiriesController::class, 'show'])
            ->whereNumber('employerEnquiry')
            ->name('enquiries.show');
        Route::get('staffing-submissions/{staffingSubmission}', [\App\Http\Controllers\Account\EmployerStaffingSubmissionsController::class, 'show'])
            ->whereNumber('staffingSubmission')
            ->name('staffing-submissions.show');
        Route::patch('staffing-submissions/{staffingSubmission}', [\App\Http\Controllers\Account\EmployerStaffingSubmissionsController::class, 'update'])
            ->whereNumber('staffingSubmission')
            ->name('staffing-submissions.update');
        Route::post('staffing-submissions/{staffingSubmission}/messages', [\App\Http\Controllers\Account\EmployerStaffingSubmissionsController::class, 'storeMessage'])
            ->whereNumber('staffingSubmission')
            ->name('staffing-submissions.messages.store');
        Route::get('staffing-submissions/{staffingSubmission}/resume', [\App\Http\Controllers\Account\EmployerStaffingSubmissionsController::class, 'resume'])
            ->whereNumber('staffingSubmission')
            ->name('staffing-submissions.resume');
        Route::post('jobs/master-data/qualifications', [EmployerJobMasterDataController::class, 'storeQualification'])
            ->name('employer.jobs.master-data.qualifications.store');
        Route::post('jobs/master-data/skills', [EmployerJobMasterDataController::class, 'storeSkill'])
            ->name('employer.jobs.master-data.skills.store');
        Route::get('jobs', [EmployerJobsController::class, 'index'])->name('employer.jobs.index');
        Route::get('jobs/create', [EmployerJobsController::class, 'create'])->name('employer.jobs.create');
        Route::post('jobs', [EmployerJobsController::class, 'store'])->name('employer.jobs.store');
        Route::get('jobs/{job}/edit', [EmployerJobsController::class, 'edit'])->name('employer.jobs.edit');
        Route::put('jobs/{job}', [EmployerJobsController::class, 'update'])->name('employer.jobs.update');
        Route::patch('jobs/{job}/lifecycle', [EmployerJobsController::class, 'lifecycle'])
            ->name('employer.jobs.lifecycle');
        Route::delete('jobs/{job}', [EmployerJobsController::class, 'destroy'])->name('employer.jobs.destroy');
        Route::get('jobs/{job}/applicants', [EmployerJobsController::class, 'applicants'])
            ->name('employer.jobs.applicants');
        Route::get('jobs/{job}/applications/{application}', [EmployerJobsController::class, 'showApplication'])
            ->whereNumber('application')
            ->name('employer.jobs.application');
        Route::post('jobs/{job}/applications/{application}/shortlist', [\App\Http\Controllers\Account\EmployerApplicationWorkflowController::class, 'shortlist'])
            ->whereNumber('application')
            ->name('employer.jobs.application.shortlist');
        Route::post('jobs/{job}/applications/{application}/reject', [\App\Http\Controllers\Account\EmployerApplicationWorkflowController::class, 'reject'])
            ->whereNumber('application')
            ->name('employer.jobs.application.reject');
        Route::post('jobs/{job}/applications/{application}/interview', [\App\Http\Controllers\Account\EmployerApplicationWorkflowController::class, 'scheduleInterview'])
            ->whereNumber('application')
            ->name('employer.jobs.application.interview');
        Route::post('jobs/{job}/applications/{application}/messages', [\App\Http\Controllers\Account\EmployerApplicationWorkflowController::class, 'sendMessage'])
            ->whereNumber('application')
            ->name('employer.jobs.application.messages');
        Route::post('jobs/{job}/applications/{application}/select', [\App\Http\Controllers\Account\EmployerApplicationWorkflowController::class, 'select'])
            ->whereNumber('application')
            ->name('employer.jobs.application.select');
        Route::post('jobs/{job}/applications/{application}/placement', [\App\Http\Controllers\Account\EmployerApplicationWorkflowController::class, 'openPlacement'])
            ->whereNumber('application')
            ->name('employer.jobs.application.placement');
        Route::post('jobs/{job}/applications/{application}/offer', [\App\Http\Controllers\Account\EmployerApplicationWorkflowController::class, 'offer'])
            ->whereNumber('application')
            ->name('employer.jobs.application.offer');
        Route::post('jobs/{job}/applications/{application}/accept-offer', [\App\Http\Controllers\Account\EmployerApplicationWorkflowController::class, 'acceptOffer'])
            ->whereNumber('application')
            ->name('employer.jobs.application.accept-offer');
        Route::post('jobs/{job}/applications/{application}/confirm-joining', [\App\Http\Controllers\Account\EmployerApplicationWorkflowController::class, 'confirmJoining'])
            ->whereNumber('application')
            ->name('employer.jobs.application.confirm-joining');
        Route::post('jobs/{job}/applications/{application}/join', [\App\Http\Controllers\Account\EmployerApplicationWorkflowController::class, 'recordJoining'])
            ->whereNumber('application')
            ->name('employer.jobs.application.join');
        Route::patch('jobs/{job}/applications/{application}/correction-requests/{correctionRequest}', [\App\Http\Controllers\Account\EmployerApplicationWorkflowController::class, 'updateCorrectionRequest'])
            ->whereNumber(['application', 'correctionRequest'])
            ->name('employer.jobs.application.correction-requests.update');
        Route::get('coordinated-talent-pool', [\App\Http\Controllers\Account\EmployerCoordinatedTalentPoolController::class, 'index'])
            ->name('coordinated-talent-pool.index');
        Route::get('coordinated-talent-pool/{submission}', [\App\Http\Controllers\Account\EmployerCoordinatedTalentPoolController::class, 'show'])
            ->whereNumber('submission')
            ->name('coordinated-talent-pool.show');
        Route::patch('coordinated-talent-pool/{submission}/status', [\App\Http\Controllers\Account\EmployerCoordinatedTalentPoolController::class, 'updateStatus'])
            ->whereNumber('submission')
            ->name('coordinated-talent-pool.status.update');
        Route::post('coordinated-talent-pool/{submission}/messages', [\App\Http\Controllers\Account\EmployerCoordinatedTalentPoolController::class, 'storeMessage'])
            ->whereNumber('submission')
            ->name('coordinated-talent-pool.messages.store');
        Route::get('coordinated-talent-pool/{submission}/resume', [\App\Http\Controllers\Account\EmployerCoordinatedTalentPoolController::class, 'resume'])
            ->whereNumber('submission')
            ->name('coordinated-talent-pool.resume');

        Route::get('submissions', [EmployerSubmissionsController::class, 'index'])
            ->name('employer.submissions.index');
        Route::get('submissions/requirements/{job}', [EmployerSubmissionsController::class, 'showRequirement'])
            ->name('employer.submissions.requirement');
        Route::get('submissions/{submission}', [EmployerSubmissionsController::class, 'show'])
            ->whereNumber('submission')
            ->name('employer.submissions.show');
        Route::get('employee-reviews', [EmployerRelationshipReviewController::class, 'index'])
            ->name('employer.reviews.index');
        Route::get('employee-reviews/{review}', [EmployerRelationshipReviewController::class, 'show'])
            ->whereNumber('review')
            ->name('employer.reviews.show');
        Route::post('employee-reviews/{employee}', [EmployerRelationshipReviewController::class, 'store'])
            ->whereNumber('employee')
            ->name('employer.reviews.store');
        Route::get('employer-applications/{application}/resume', [EmployerJobsController::class, 'resume'])
            ->whereNumber('application')
            ->name('employer.applications.resume');

        Route::get('company-documents', [EmployerDocumentController::class, 'index'])
            ->name('employer.documents.index');
        Route::post('company-documents', [EmployerDocumentController::class, 'store'])
            ->middleware('throttle:account-files')
            ->name('employer.documents.store');
        Route::put('company-documents/{document}', [EmployerDocumentController::class, 'update'])
            ->whereNumber('document')
            ->name('employer.documents.update');
        Route::post('company-documents/{document}/replace', [EmployerDocumentController::class, 'replace'])
            ->middleware('throttle:account-files')
            ->whereNumber('document')
            ->name('employer.documents.replace');
        Route::delete('company-documents/{document}', [EmployerDocumentController::class, 'destroy'])
            ->whereNumber('document')
            ->name('employer.documents.destroy');
        Route::get('company-documents/{document}/download', [EmployerDocumentController::class, 'download'])
            ->whereNumber('document')
            ->name('employer.documents.download');
        Route::get('company-documents/{document}/view', [EmployerDocumentController::class, 'view'])
            ->whereNumber('document')
            ->name('employer.documents.view');
    });
});
