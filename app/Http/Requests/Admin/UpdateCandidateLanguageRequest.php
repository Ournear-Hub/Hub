<?php

namespace App\Http\Requests\Admin;

use App\Models\CandidateLanguage;
use App\Models\CandidateProfile;

class UpdateCandidateLanguageRequest extends StoreCandidateLanguageRequest
{
    public function authorize(): bool
    {
        $profile = $this->route('candidate');
        $language = $this->route('language');

        return $profile instanceof CandidateProfile
            && $language instanceof CandidateLanguage
            && (int) $language->candidate_profile_id === (int) $profile->id
            && $this->user()?->can('update', $profile) === true;
    }
}
