<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\StoreCandidateCertificationRequest;
use App\Http\Requests\Account\UpdateCandidateCertificationRequest;
use App\Models\CandidateCertification;
use App\Models\CandidateProfile;
use App\Services\CandidateTalentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CandidateCertificationController extends Controller
{
    public function store(StoreCandidateCertificationRequest $request, CandidateTalentService $talent): RedirectResponse
    {
        $profile = $this->ownedProfile($request);
        $this->authorize('update', $profile);

        $talent->addCertification($profile, $request->certificationAttributes(), $request->user());

        return back()->with('success', 'Certification added.');
    }

    public function update(
        UpdateCandidateCertificationRequest $request,
        CandidateCertification $certification,
        CandidateTalentService $talent,
    ): RedirectResponse {
        $profile = $this->ownedProfile($request);
        $this->authorize('update', $profile);
        abort_unless((int) $certification->candidate_profile_id === (int) $profile->id, 403);

        $talent->updateCertification($certification, $request->certificationAttributes(), $request->user());

        return back()->with('success', 'Certification updated.');
    }

    public function destroy(
        Request $request,
        CandidateCertification $certification,
        CandidateTalentService $talent,
    ): RedirectResponse {
        $profile = $this->ownedProfile($request);
        $this->authorize('update', $profile);
        abort_unless((int) $certification->candidate_profile_id === (int) $profile->id, 403);

        $talent->deleteCertification($certification, $request->user());

        return back()->with('success', 'Certification removed.');
    }

    private function ownedProfile(Request $request): CandidateProfile
    {
        $profile = $request->user()?->candidateProfile;
        abort_unless($profile instanceof CandidateProfile, 403);

        return $profile;
    }
}
