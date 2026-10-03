<?php

namespace App\Http\Requests\Account;

use App\Models\CandidateCertification;
use App\Models\CandidateProfile;

class UpdateCandidateCertificationRequest extends StoreCandidateCertificationRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $profile = $user?->candidateProfile;
        $certification = $this->route('certification');

        return $user?->isCandidate() === true
            && $profile instanceof CandidateProfile
            && $certification instanceof CandidateCertification
            && (int) $certification->candidate_profile_id === (int) $profile->id
            && $user->can('update', $profile);
    }
}
