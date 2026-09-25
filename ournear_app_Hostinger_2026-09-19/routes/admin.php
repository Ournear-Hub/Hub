<?php

use App\Http\Controllers\Admin\ActivityLogsController;
use App\Http\Controllers\Admin\AdminProfileController;
use App\Http\Controllers\Admin\ApplicationsController;
use App\Http\Controllers\Admin\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Admin\Auth\NewPasswordController;
use App\Http\Controllers\Admin\Auth\PasswordResetLinkController;
use App\Http\Controllers\Admin\CandidatesController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EmployeesController;
use App\Http\Controllers\Admin\FinanceController;
use App\Http\Controllers\Admin\FreelancersController;
use App\Http\Controllers\Admin\LoginLogsController;
use App\Http\Controllers\Admin\MessagesController;
use App\Http\Controllers\Admin\NotificationsController;
use App\Http\Controllers\Admin\EmployerEnquiriesController;
use App\Http\Controllers\Admin\EmployersController;
use App\Http\Controllers\Admin\EmployerSubmissionsController;
use App\Http\Controllers\Admin\JobsController;
use App\Http\Controllers\Admin\PlacementsController;
use App\Http\Controllers\Admin\ReplacementsController;
use App\Http\Controllers\Admin\RolesController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\UsersController;
use App\Enums\MasterEntityType;
use App\Http\Controllers\Admin\MasterDataController;
use App\Http\Controllers\Admin\MediaSocialController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\WebsiteContentController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:admin-login')
        ->name('login.store');

    Route::middleware('guest')->group(function () {
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
        Route::get('profile/password', [AdminProfileController::class, 'editPassword'])->name('profile.password.edit');
        Route::put('profile/password', [AdminProfileController::class, 'updatePassword'])->name('profile.password.update');

        Route::middleware('password.change')->group(function () {
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

        Route::get('candidates', [CandidatesController::class, 'index'])->name('candidates.index');
        Route::put('candidates/{candidate}', [CandidatesController::class, 'update'])
            ->whereNumber('candidate')
            ->name('candidates.update');
        Route::get('candidates/resumes/{resume}', [CandidatesController::class, 'showResume'])
            ->whereNumber('resume')
            ->name('candidates.resumes.show');
        Route::get('candidates/documents/{document}', [CandidatesController::class, 'showDocument'])
            ->whereNumber('document')
            ->name('candidates.documents.show');

        Route::get('applications', [ApplicationsController::class, 'index'])->name('applications.index');
        Route::patch('applications/{application}/status', [ApplicationsController::class, 'updateStatus'])->name('applications.status');
        Route::post('applications/{application}/notes', [ApplicationsController::class, 'storeNote'])->name('applications.notes');
        Route::put('applications/{application}/interview', [ApplicationsController::class, 'saveInterview'])->name('applications.interview');
        Route::put('applications/{application}/screening', [ApplicationsController::class, 'saveScreening'])->name('applications.screening');
        Route::post('applications/{application}/screening/actions', [ApplicationsController::class, 'recordScreeningAction'])->name('applications.screening.actions');
        Route::post('applications/{application}/shortlist', [ApplicationsController::class, 'shortlist'])->name('applications.shortlist');
        Route::post('applications/{application}/submit', [ApplicationsController::class, 'submitToEmployer'])->name('applications.submit');
        Route::get('applications/{application}/resume', [ApplicationsController::class, 'resume'])->name('applications.resume');
        Route::get('screening', [ApplicationsController::class, 'screening'])->name('screening.index');
        Route::get('shortlisted', [ApplicationsController::class, 'shortlisted'])->name('shortlisted.index');
        Route::get('employers', [EmployersController::class, 'index'])->name('employers.index');
        Route::patch('employers/{employer}/posting-approval', [EmployersController::class, 'postingApproval'])
            ->name('employers.posting-approval');
        Route::get('submissions', [EmployerSubmissionsController::class, 'index'])->name('submissions.index');
        Route::post('submissions/{submission}/select', [EmployerSubmissionsController::class, 'select'])->name('submissions.select');
        Route::post('submissions/{submission}/reject', [EmployerSubmissionsController::class, 'reject'])->name('submissions.reject');
        Route::post('submissions/{submission}/hold', [EmployerSubmissionsController::class, 'hold'])->name('submissions.hold');
        Route::post('submissions/{submission}/review', [EmployerSubmissionsController::class, 'review'])->name('submissions.review');
        Route::post('submissions/{submission}/place', [PlacementsController::class, 'store'])->name('submissions.place');
        Route::get('placements', [PlacementsController::class, 'index'])->name('placements.index');
        Route::post('placements/{placement}/offer', [PlacementsController::class, 'offer'])->name('placements.offer');
        Route::post('placements/{placement}/accept', [PlacementsController::class, 'accept'])->name('placements.accept');
        Route::post('placements/{placement}/decline', [PlacementsController::class, 'decline'])->name('placements.decline');
        Route::post('placements/{placement}/confirm-joining', [PlacementsController::class, 'confirmJoining'])->name('placements.confirm-joining');
        Route::post('placements/{placement}/join', [PlacementsController::class, 'join'])->name('placements.join');
        Route::post('placements/{placement}/withdraw', [PlacementsController::class, 'withdraw'])->name('placements.withdraw');
        Route::get('employees', [EmployeesController::class, 'index'])->name('employees.index');
        Route::post('employees/{employee}/replacements', [ReplacementsController::class, 'store'])->name('employees.replacements.store');
        Route::post('employees/{employee}/invoices', [FinanceController::class, 'store'])->name('employees.invoices.store');
        Route::get('replacements', [ReplacementsController::class, 'index'])->name('replacements.index');
        Route::post('replacements/{replacement}/identify', [ReplacementsController::class, 'identify'])->name('replacements.identify');
        Route::post('replacements/{replacement}/cancel', [ReplacementsController::class, 'cancel'])->name('replacements.cancel');
        Route::get('finance', [FinanceController::class, 'index'])->name('finance.index');
        Route::post('invoices/{invoice}/issue', [FinanceController::class, 'issue'])->name('invoices.issue');
        Route::post('invoices/{invoice}/cancel', [FinanceController::class, 'cancel'])->name('invoices.cancel');
        Route::post('invoices/{invoice}/payments', [FinanceController::class, 'recordPayment'])->name('invoices.payments.store');
        Route::get('notifications', [NotificationsController::class, 'index'])->name('notifications.index');
        Route::post('notifications/read-all', [NotificationsController::class, 'markAllRead'])->name('notifications.read-all');
        Route::post('notifications/{notification}/read', [NotificationsController::class, 'markRead'])->name('notifications.read');
        Route::post('notifications/{notification}/unread', [NotificationsController::class, 'markUnread'])->name('notifications.unread');
        Route::get('messages', [MessagesController::class, 'index'])->name('messages.index');
        Route::post('messages', [MessagesController::class, 'store'])->name('messages.store');
        Route::post('messages/{conversation}', [MessagesController::class, 'reply'])->name('messages.reply');
        Route::get('freelancers', [FreelancersController::class, 'index'])->name('freelancers.index');
        Route::get('freelancers/{freelancer}', [FreelancersController::class, 'show'])
            ->whereNumber('freelancer')
            ->name('freelancers.show');
        Route::patch('freelancers/{freelancer}/status', [FreelancersController::class, 'updateStatus'])
            ->whereNumber('freelancer')
            ->name('freelancers.status');
        Route::get('freelancers/{freelancer}/resume', [FreelancersController::class, 'resume'])
            ->whereNumber('freelancer')
            ->name('freelancers.resume');
        Route::get('freelancers/{freelancer}/certificate', [FreelancersController::class, 'certificate'])
            ->whereNumber('freelancer')
            ->name('freelancers.certificate');

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

        Route::get('profile', [AdminProfileController::class, 'show'])->name('profile.show');
        Route::put('profile', [AdminProfileController::class, 'update'])->name('profile.update');

        Route::get('users', [UsersController::class, 'index'])->name('users.index');
        Route::get('users/activity', [UsersController::class, 'activity'])->name('users.activity');
        Route::post('users', [UsersController::class, 'store'])->name('users.store');
        Route::get('users/{user}', [UsersController::class, 'show'])->whereNumber('user')->name('users.show');
        Route::put('users/{user}', [UsersController::class, 'update'])->whereNumber('user')->name('users.update');
        Route::put('users/{user}/profile', [UsersController::class, 'updateProfile'])->whereNumber('user')->name('users.profile');
        Route::put('users/{user}/preferences', [UsersController::class, 'updatePreferences'])->whereNumber('user')->name('users.preferences');
        Route::put('users/{user}/roles', [UsersController::class, 'syncRoles'])->whereNumber('user')->name('users.roles');
        Route::patch('users/{user}/status', [UsersController::class, 'updateStatus'])->whereNumber('user')->name('users.status');
        Route::post('users/{user}/password-reset', [UsersController::class, 'sendPasswordReset'])->whereNumber('user')->name('users.password-reset');

        Route::get('staff', [StaffController::class, 'index'])->name('staff.index');
        Route::post('staff', [UsersController::class, 'store'])->name('staff.store');

        Route::get('roles', [RolesController::class, 'index'])->name('roles.index');
        Route::put('roles/{role}/permissions', [RolesController::class, 'syncPermissions'])->whereNumber('role')->name('roles.permissions');

        Route::get('activity-logs', [ActivityLogsController::class, 'index'])->name('activity-logs.index');
        Route::get('login-logs', [LoginLogsController::class, 'index'])->name('login-logs.index');
        });
    });
});
