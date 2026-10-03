<?php

namespace App\Http\Requests\Account;

use App\Models\CandidateLanguage;
use App\Models\CandidateProfile;

class UpdateCandidateLanguageRequest extends StoreCandidateLanguageRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $profile = $user?->candidateProfile;
        $language = $this->route('language');

        return $user?->isCandidate() === true
            && $profile instanceof CandidateProfile
            && $language instanceof CandidateLanguage
            && (int) $language->candidate_profile_id === (int) $profile->id
            && $user->can('update', $profile);
    }
}
