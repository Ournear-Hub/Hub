<?php

namespace App\Policies;

use App\Models\CandidateProfile;
use App\Models\User;

class CandidateProfilePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManageRecruitment();
    }

    public function view(User $user, CandidateProfile $candidateProfile): bool
    {
        if ($user->canManageRecruitment()) {
            return true;
        }

        return $this->ownedBy($user, $candidateProfile);
    }

    public function update(User $user, CandidateProfile $candidateProfile): bool
    {
        if ($user->canManageRecruitment()) {
            return true;
        }

        return $this->ownedBy($user, $candidateProfile);
    }

    private function ownedBy(User $user, CandidateProfile $candidateProfile): bool
    {
        return $user->isCandidate() && (int) $candidateProfile->user_id === (int) $user->id;
    }
}
