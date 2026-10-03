<?php

namespace App\Http\Requests\Admin;

use App\Models\CandidateCertification;
use App\Models\CandidateProfile;

class UpdateCandidateCertificationRequest extends StoreCandidateCertificationRequest
{
    public function authorize(): bool
    {
        $profile = $this->route('candidate');
        $certification = $this->route('certification');

        return $profile instanceof CandidateProfile
            && $certification instanceof CandidateCertification
            && (int) $certification->candidate_profile_id === (int) $profile->id
            && $this->user()?->can('update', $profile) === true;
    }
}
