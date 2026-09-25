<?php

namespace App\Policies;

use App\Models\CandidateEmploymentHistory;
use App\Models\CandidateProfile;
use App\Models\User;

class CandidateEmploymentHistoryPolicy
{
    public function view(User $user, CandidateEmploymentHistory $history): bool
    {
        $profile = $history->candidateProfile ?? $history->candidateProfile()->first();

        return $profile instanceof CandidateProfile
            && $user->can('view', $profile);
    }

    public function update(User $user, CandidateEmploymentHistory $history): bool
    {
        $profile = $history->candidateProfile ?? $history->candidateProfile()->first();

        return $profile instanceof CandidateProfile
            && $user->can('update', $profile);
    }

    public function delete(User $user, CandidateEmploymentHistory $history): bool
    {
        return $this->update($user, $history);
    }
}
