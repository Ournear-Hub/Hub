<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\StoreCandidateSkillRequest;
use App\Http\Requests\Account\StoreEmploymentHistoryRequest;
use App\Http\Requests\Account\UpdateEmploymentHistoryRequest;
use App\Http\Requests\Account\UpdateProfessionalProfileRequest;
use App\Models\CandidateEmploymentHistory;
use App\Models\CandidateProfile;
use App\Models\Skill;
use App\Services\CandidateProfessionalProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CandidateProfessionalProfileController extends Controller
{
    public function update(
        UpdateProfessionalProfileRequest $request,
        CandidateProfessionalProfileService $profiles,
    ): RedirectResponse {
        $profile = $this->ownedProfile($request);
        $this->authorize('update', $profile);

        $profiles->updateProfessional(
            $profile,
            $request->professionalAttributes(),
            $request->user(),
        );

        return back()->with('success', 'Your professional profile has been updated.');
    }

    public function storeSkill(
        StoreCandidateSkillRequest $request,
        CandidateProfessionalProfileService $profiles,
    ): RedirectResponse {
        $profile = $this->ownedProfile($request);
        $this->authorize('update', $profile);

        $profiles->addSkill($profile, $request->skillName(), $request->user());

        return back()->with('success', 'Skill added.');
    }

    public function destroySkill(
        Request $request,
        Skill $skill,
        CandidateProfessionalProfileService $profiles,
    ): RedirectResponse {
        $profile = $this->ownedProfile($request);
        $this->authorize('update', $profile);

        $profiles->removeSkill($profile, $skill, $request->user());

        return back()->with('success', 'Skill removed.');
    }

    public function storeHistory(
        StoreEmploymentHistoryRequest $request,
        CandidateProfessionalProfileService $profiles,
    ): RedirectResponse {
        $profile = $this->ownedProfile($request);
        $this->authorize('update', $profile);

        $profiles->createHistory($profile, $request->historyAttributes(), $request->user());

        return back()->with('success', 'Employment history added.');
    }

    public function updateHistory(
        UpdateEmploymentHistoryRequest $request,
        CandidateEmploymentHistory $history,
        CandidateProfessionalProfileService $profiles,
    ): RedirectResponse {
        $profile = $this->ownedProfile($request);
        $this->authorize('update', $profile);
        $this->authorize('update', $history);
        abort_unless((int) $history->candidate_profile_id === (int) $profile->id, 403);

        $profiles->updateHistory($history, $request->historyAttributes(), $request->user());

        return back()->with('success', 'Employment history updated.');
    }

    public function destroyHistory(
        Request $request,
        CandidateEmploymentHistory $history,
        CandidateProfessionalProfileService $profiles,
    ): RedirectResponse {
        $profile = $this->ownedProfile($request);
        $this->authorize('update', $profile);
        $this->authorize('delete', $history);
        abort_unless((int) $history->candidate_profile_id === (int) $profile->id, 403);

        $profiles->deleteHistory($history, $request->user());

        return back()->with('success', 'Employment history removed.');
    }

    private function ownedProfile(Request $request): CandidateProfile
    {
        $user = $request->user();
        abort_unless($user?->isCandidate() === true, 403);

        $profile = $user->candidateProfile;
        abort_unless($profile instanceof CandidateProfile, 404);
        abort_unless((int) $profile->user_id === (int) $user->id, 403);

        return $profile;
    }
}
