<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\CandidateProfile;
use App\Models\User;

class CandidateProfilePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(PermissionName::CandidatesView->value)
            || $user->hasPermission(PermissionName::CandidatesManage->value);
    }

    public function view(User $user, CandidateProfile $candidateProfile): bool
    {
        if ($this->viewAny($user)) {
            return true;
        }

        return $this->ownedBy($user, $candidateProfile);
    }

    public function update(User $user, CandidateProfile $candidateProfile): bool
    {
        if ($user->hasPermission(PermissionName::CandidatesManage->value)) {
            return true;
        }

        return $this->ownedBy($user, $candidateProfile);
    }

    private function ownedBy(User $user, CandidateProfile $candidateProfile): bool
    {
        return $user->isCandidate() && (int) $candidateProfile->user_id === (int) $user->id;
    }
}
