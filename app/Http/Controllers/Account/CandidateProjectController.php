<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\StoreCandidateProjectRequest;
use App\Http\Requests\Account\UpdateCandidateProjectRequest;
use App\Models\CandidateProfile;
use App\Models\CandidateProject;
use App\Services\CandidateTalentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CandidateProjectController extends Controller
{
    public function store(StoreCandidateProjectRequest $request, CandidateTalentService $talent): RedirectResponse
    {
        $profile = $this->ownedProfile($request);
        $this->authorize('update', $profile);

        $talent->addProject($profile, $request->projectAttributes(), $request->user());

        return back()->with('success', 'Project added.');
    }

    public function update(
        UpdateCandidateProjectRequest $request,
        CandidateProject $project,
        CandidateTalentService $talent,
    ): RedirectResponse {
        $profile = $this->ownedProfile($request);
        $this->authorize('update', $profile);
        abort_unless((int) $project->candidate_profile_id === (int) $profile->id, 403);

        $talent->updateProject($project, $request->projectAttributes(), $request->user());

        return back()->with('success', 'Project updated.');
    }

    public function destroy(Request $request, CandidateProject $project, CandidateTalentService $talent): RedirectResponse
    {
        $profile = $this->ownedProfile($request);
        $this->authorize('update', $profile);
        abort_unless((int) $project->candidate_profile_id === (int) $profile->id, 403);

        $talent->deleteProject($project, $request->user());

        return back()->with('success', 'Project removed.');
    }

    private function ownedProfile(Request $request): CandidateProfile
    {
        $profile = $request->user()?->candidateProfile;
        abort_unless($profile instanceof CandidateProfile, 403);

        return $profile;
    }
}
