<?php

namespace App\Http\Controllers\Account;

use App\Enums\LanguageProficiency;
use App\Enums\NoticePeriod;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\UpdateAccountCompanyLogoRequest;
use App\Http\Requests\Account\UpdateAccountPhotoRequest;
use App\Http\Requests\Account\UpdateProfileRequest;
use App\Http\Requests\Account\UpdatePublicCompanyShareRequest;
use App\Http\Requests\Account\UpdatePublicShareRequest;
use App\Enums\FileLifecycleStatus;
use App\Models\CandidateProfile;
use App\Models\CandidateResume;
use App\Models\EmployerProfile;
use App\Services\AccountDashboardService;
use App\Services\AccountProfileService;
use App\Services\CandidateTalentService;
use App\Services\EmployerCompanyService;
use App\Services\MasterData\MasterDataService;
use App\Services\UnifiedProfilePresenter;
use App\Support\CandidatePublicShare;
use App\Support\EmployerPublicShare;
use App\Support\EmployerSocials;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function home(Request $request, AccountDashboardService $dashboard): Response
    {
        $user = $request->user();
        $this->authorize('update', $user);

        if ($user->isCandidate()) {
            app(\App\Services\CandidateNotificationService::class)->notifyIncompleteProfile($user);
        }

        return Inertia::render('Account/Home', $dashboard->home($user));
    }

    public function edit(
        Request $request,
        AccountProfileService $profiles,
        MasterDataService $masterData,
        UnifiedProfilePresenter $unified,
    ): Response {
        return $this->renderProfilePage($request, $profiles, $masterData, $unified, companyWorkspace: false);
    }

    public function company(
        Request $request,
        AccountProfileService $profiles,
        MasterDataService $masterData,
        UnifiedProfilePresenter $unified,
    ): Response|RedirectResponse {
        $user = $request->user();
        $this->authorize('update', $user);

        if (! $user->canAccessHiringWorkspace()) {
            if ($user->isCandidate()) {
                return redirect()
                    ->route('account.hiring')
                    ->with('warning', 'Activate hiring services before managing a company profile.');
            }

            abort(403);
        }

        return $this->renderProfilePage($request, $profiles, $masterData, $unified, companyWorkspace: true);
    }

    private function renderProfilePage(
        Request $request,
        AccountProfileService $profiles,
        MasterDataService $masterData,
        UnifiedProfilePresenter $unified,
        bool $companyWorkspace,
    ): Response {
        $user = $request->user();
        $this->authorize('update', $user);
        $user->load([
            'candidateProfile.industry',
            'candidateProfile.jobCategory',
            'candidateProfile.skills',
            'candidateProfile.employmentHistories.industry',
            'candidateProfile.preferredLocations',
            'candidateProfile.preferredEmploymentTypes',
            'candidateProfile.certifications',
            'candidateProfile.languages',
            'candidateProfile.educations',
            'candidateProfile.projects',
            'employerProfile.locations',
        ]);

        $profile = $user->toAccountProfileArray();

        if ($companyWorkspace || $user->isEmployer()) {
            $profile = array_merge($profile, $user->employerProfile?->toAccountArray() ?? []);
        } elseif ($user->isCandidate()) {
            $profile['experience'] = $user->candidateProfile?->experience;
            $profile['qualification'] = $user->candidateProfile?->qualification;
            $profile['professional'] = $user->candidateProfile?->toProfessionalArray();
            $libraryResume = $user->candidateProfile?->resumes()
                ->where('status', FileLifecycleStatus::Active)
                ->orderByDesc('is_primary')
                ->orderByDesc('id')
                ->first();
            $profile['library_resume'] = [
                'has_upload' => $libraryResume instanceof CandidateResume,
                'title' => $libraryResume instanceof CandidateResume ? $libraryResume->title : null,
            ];
        }

        $profile = array_merge($profile, $unified->ownerIdentity($user));

        $showCompanyUi = $companyWorkspace || $user->isEmployer();

        return Inertia::render('Account/Profile', [
            'role' => $showCompanyUi ? 'employer' : $user->role->value,
            'companyWorkspace' => $companyWorkspace,
            'profile' => $profile,
            'languages' => $profiles->languageOptions(),
            'lookups' => [
                ...$masterData->formOptions(),
                'notice_periods' => NoticePeriod::options(),
                'language_proficiencies' => LanguageProficiency::options(),
                'public_share_fields' => $showCompanyUi
                    ? []
                    : CandidatePublicShare::fieldOptions(),
                'company_public_share_fields' => $showCompanyUi
                    ? EmployerPublicShare::fieldOptions()
                    : [],
                'social_fields' => EmployerSocials::fieldOptions(),
            ],
        ]);
    }

    public function updatePrivacy(UpdatePublicShareRequest $request, CandidateTalentService $talent): RedirectResponse
    {
        $profile = $request->user()?->candidateProfile;
        abort_unless($profile instanceof CandidateProfile, 403);
        $this->authorize('update', $profile);

        $talent->updatePublicShare($profile, $request->shareAttributes(), $request->user());

        return back()->with('success', 'Public profile sharing has been updated.');
    }

    public function updateCompanyPrivacy(UpdatePublicCompanyShareRequest $request, EmployerCompanyService $employers): RedirectResponse
    {
        $profile = $request->user()?->employerProfile;
        abort_unless($profile instanceof EmployerProfile, 403);

        $employers->updatePublicShare($profile, $request->shareAttributes(), $request->user());

        return back()->with('success', 'Public company sharing has been updated.');
    }

    public function update(UpdateProfileRequest $request, AccountProfileService $profiles): RedirectResponse
    {
        $user = $request->user();
        $this->authorize('update', $user);

        $companyOnly = $request->isCompanyProfileUpdate();

        $profiles->updateProfile(
            $user,
            $request->profileAttributes(),
            $companyOnly ? null : $request->file('photo'),
            $companyOnly ? false : $request->boolean('remove_photo'),
            $companyOnly,
        );

        return back()->with(
            'success',
            $companyOnly ? 'Your company profile has been updated.' : 'Your profile has been updated.',
        );
    }

    public function updatePhoto(UpdateAccountPhotoRequest $request, AccountProfileService $profiles): RedirectResponse
    {
        $user = $request->user();
        $this->authorize('update', $user);

        $profiles->updatePhoto($user, $request->file('photo'));

        return back()->with('success', 'Your profile photo has been updated.');
    }

    public function destroyPhoto(Request $request, AccountProfileService $profiles): RedirectResponse
    {
        $user = $request->user();
        $this->authorize('update', $user);

        $profiles->removePhoto($user);

        return back()->with('success', 'Your profile photo has been removed.');
    }

    public function updateCompanyLogo(
        UpdateAccountCompanyLogoRequest $request,
        AccountProfileService $profiles,
    ): RedirectResponse {
        $user = $request->user();
        $user->loadMissing('employerProfile');
        $profile = $user->employerProfile;
        abort_unless($profile instanceof EmployerProfile, 403);
        $this->authorize('updateLogo', $profile);

        $profiles->updateCompanyLogo($user, $request->file('logo'));

        return back()->with('success', 'Your company logo has been updated.');
    }

    public function destroyCompanyLogo(Request $request, AccountProfileService $profiles): RedirectResponse
    {
        $user = $request->user();
        $user->loadMissing('employerProfile');
        $profile = $user->employerProfile;
        abort_unless($profile instanceof EmployerProfile, 403);
        $this->authorize('updateLogo', $profile);

        $profiles->removeCompanyLogo($user);

        return back()->with('success', 'Your company logo has been removed.');
    }
}
