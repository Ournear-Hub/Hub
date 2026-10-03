<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\StoreCandidateLanguageRequest;
use App\Http\Requests\Account\UpdateCandidateLanguageRequest;
use App\Models\CandidateLanguage;
use App\Models\CandidateProfile;
use App\Services\CandidateTalentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CandidateLanguageController extends Controller
{
    public function store(StoreCandidateLanguageRequest $request, CandidateTalentService $talent): RedirectResponse
    {
        $profile = $this->ownedProfile($request);
        $this->authorize('update', $profile);

        $talent->addLanguage($profile, $request->languageAttributes(), $request->user());

        return back()->with('success', 'Language added.');
    }

    public function update(
        UpdateCandidateLanguageRequest $request,
        CandidateLanguage $language,
        CandidateTalentService $talent,
    ): RedirectResponse {
        $profile = $this->ownedProfile($request);
        $this->authorize('update', $profile);
        abort_unless((int) $language->candidate_profile_id === (int) $profile->id, 403);

        $talent->updateLanguage($language, $request->languageAttributes(), $request->user());

        return back()->with('success', 'Language updated.');
    }

    public function destroy(
        Request $request,
        CandidateLanguage $language,
        CandidateTalentService $talent,
    ): RedirectResponse {
        $profile = $this->ownedProfile($request);
        $this->authorize('update', $profile);
        abort_unless((int) $language->candidate_profile_id === (int) $profile->id, 403);

        $talent->deleteLanguage($language, $request->user());

        return back()->with('success', 'Language removed.');
    }

    private function ownedProfile(Request $request): CandidateProfile
    {
        $profile = $request->user()?->candidateProfile;
        abort_unless($profile instanceof CandidateProfile, 403);

        return $profile;
    }
}
