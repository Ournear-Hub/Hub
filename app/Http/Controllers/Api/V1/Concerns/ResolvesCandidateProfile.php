<?php

namespace App\Http\Controllers\Api\V1\Concerns;

use App\Models\CandidateProfile;
use App\Models\User;
use Illuminate\Http\Request;

trait ResolvesCandidateProfile
{
    protected function ownedProfile(Request $request): CandidateProfile
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->isCandidate(), 403);

        $profile = $user->candidateProfile;
        abort_unless($profile instanceof CandidateProfile, 404);
        abort_unless((int) $profile->user_id === (int) $user->id, 403);

        return $profile;
    }

    protected function loadProfessionalRelations(CandidateProfile $profile): void
    {
        $profile->loadMissing([
            'industry',
            'jobCategory',
            'skills',
            'employmentHistories.industry',
            'preferredLocations',
            'preferredEmploymentTypes',
            'certifications',
            'languages',
            'educations',
            'projects',
            'classifiedExperienceRange',
            'classifiedQualification',
        ]);
    }
}
