<?php

namespace App\Http\Controllers\Account;

use App\Enums\LanguageProficiency;
use App\Enums\NoticePeriod;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\UpdateAccountPhotoRequest;
use App\Http\Requests\Account\UpdateProfileRequest;
use App\Http\Requests\Account\UpdatePublicCompanyShareRequest;
use App\Http\Requests\Account\UpdatePublicShareRequest;
use App\Models\CandidateProfile;
use App\Models\EmployerProfile;
use App\Services\AccountDashboardService;
use App\Services\AccountProfileService;
use App\Services\CandidateTalentService;
use App\Services\EmployerCompanyService;
use App\Services\MasterData\MasterDataService;
use App\Services\UnifiedProfilePresenter;
use App\Support\CandidatePublicShare;
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

        if ($user->isCandidate()) {
            $profile['experience'] = $user->candidateProfile?->experience;
            $profile['qualification'] = $user->candidateProfile?->qualification;
            $profile['professional'] = $user->candidateProfile?->toProfessionalArray();
        } else {
            $profile = array_merge($profile, $user->employerProfile?->toAccountArray() ?? []);
        }

        $profile = array_merge($profile, $unified->ownerIdentity($user));

        return Inertia::render('Account/Profile', [
            'role' => $user->role->value,
            'profile' => $profile,
            'languages' => $profiles->languageOptions(),
            'lookups' => [
                ...$masterData->formOptions(),
                'notice_periods' => NoticePeriod::options(),
                'language_proficiencies' => LanguageProficiency::options(),
                'public_share_fields' => $user->isEmployer()
                    ? []
                    : CandidatePublicShare::fieldOptions(),
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

        $profiles->updateProfile(
            $user,
            $request->profileAttributes(),
            $request->file('photo'),
            $request->boolean('remove_photo'),
        );

        return back()->with('success', 'Your profile has been updated.');
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
}
