<?php

namespace App\Http\Controllers\Account;

use App\Enums\NoticePeriod;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\UpdateAccountPhotoRequest;
use App\Http\Requests\Account\UpdateProfileRequest;
use App\Services\AccountDashboardService;
use App\Services\AccountProfileService;
use App\Services\MasterData\MasterDataService;
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

        return Inertia::render('Account/Home', $dashboard->home($user));
    }

    public function edit(Request $request, AccountProfileService $profiles, MasterDataService $masterData): Response
    {
        $user = $request->user();
        $this->authorize('update', $user);
        $user->load([
            'candidateProfile.industry',
            'candidateProfile.jobCategory',
            'candidateProfile.skills',
            'candidateProfile.employmentHistories.industry',
            'candidateProfile.preferredLocations',
            'candidateProfile.preferredEmploymentTypes',
            'employerProfile',
        ]);

        $profile = $user->toAccountProfileArray();

        if ($user->isCandidate()) {
            $profile['experience'] = $user->candidateProfile?->experience;
            $profile['qualification'] = $user->candidateProfile?->qualification;
            $profile['professional'] = $user->candidateProfile?->toProfessionalArray();
        } else {
            $profile['company_name'] = $user->employerProfile?->company_name;
            $profile['contact_person'] = $user->employerProfile?->contact_person;
            $profile['mobile_number'] = $user->employerProfile?->mobile_number;
            $profile['industry'] = $user->employerProfile?->industry;
            $profile['about_company'] = $user->employerProfile?->about_company;
            $profile['company_size'] = $user->employerProfile?->company_size;
            $profile['company_website'] = $user->employerProfile?->company_website;
            $profile['company_details'] = $user->employerProfile?->company_details;
        }

        return Inertia::render('Account/Profile', [
            'role' => $user->role->value,
            'profile' => $profile,
            'languages' => $profiles->languageOptions(),
            'lookups' => [
                ...$masterData->formOptions(),
                'notice_periods' => NoticePeriod::options(),
            ],
        ]);
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
