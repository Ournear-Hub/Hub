<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\StoreCandidateEducationRequest;
use App\Http\Requests\Account\UpdateCandidateEducationRequest;
use App\Models\CandidateEducation;
use App\Models\CandidateProfile;
use App\Services\CandidateTalentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CandidateEducationController extends Controller
{
    public function store(StoreCandidateEducationRequest $request, CandidateTalentService $talent): RedirectResponse
    {
        $profile = $this->ownedProfile($request);
        $this->authorize('update', $profile);

        $talent->addEducation($profile, $request->educationAttributes(), $request->user());

        return back()->with('success', 'Education added.');
    }

    public function update(
        UpdateCandidateEducationRequest $request,
        CandidateEducation $education,
        CandidateTalentService $talent,
    ): RedirectResponse {
        $profile = $this->ownedProfile($request);
        $this->authorize('update', $profile);
        abort_unless((int) $education->candidate_profile_id === (int) $profile->id, 403);

        $talent->updateEducation($education, $request->educationAttributes(), $request->user());

        return back()->with('success', 'Education updated.');
    }

    public function destroy(Request $request, CandidateEducation $education, CandidateTalentService $talent): RedirectResponse
    {
        $profile = $this->ownedProfile($request);
        $this->authorize('update', $profile);
        abort_unless((int) $education->candidate_profile_id === (int) $profile->id, 403);

        $talent->deleteEducation($education, $request->user());

        return back()->with('success', 'Education removed.');
    }

    private function ownedProfile(Request $request): CandidateProfile
    {
        $profile = $request->user()?->candidateProfile;
        abort_unless($profile instanceof CandidateProfile, 403);

        return $profile;
    }
}
