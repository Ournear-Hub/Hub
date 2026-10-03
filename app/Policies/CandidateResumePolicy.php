<?php

namespace App\Policies;

use App\Models\CandidateProfile;
use App\Models\CandidateResume;
use App\Models\User;

class CandidateResumePolicy
{
    public function view(User $user, CandidateResume $resume): bool
    {
        $profile = $resume->candidateProfile ?? $resume->candidateProfile()->first();

        return $profile instanceof CandidateProfile
            && $user->can('view', $profile);
    }

    public function update(User $user, CandidateResume $resume): bool
    {
        $profile = $resume->candidateProfile ?? $resume->candidateProfile()->first();

        return $profile instanceof CandidateProfile
            && $user->can('update', $profile);
    }

    public function delete(User $user, CandidateResume $resume): bool
    {
        return $this->update($user, $resume);
    }

    public function download(User $user, CandidateResume $resume): bool
    {
        return $this->view($user, $resume);
    }
}
