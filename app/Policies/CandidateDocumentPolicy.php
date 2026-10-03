<?php

namespace App\Policies;

use App\Models\CandidateDocument;
use App\Models\CandidateProfile;
use App\Models\User;

class CandidateDocumentPolicy
{
    public function view(User $user, CandidateDocument $document): bool
    {
        $profile = $document->candidateProfile ?? $document->candidateProfile()->first();

        return $profile instanceof CandidateProfile
            && $user->can('view', $profile);
    }

    public function update(User $user, CandidateDocument $document): bool
    {
        $profile = $document->candidateProfile ?? $document->candidateProfile()->first();

        return $profile instanceof CandidateProfile
            && $user->can('update', $profile);
    }

    public function delete(User $user, CandidateDocument $document): bool
    {
        return $this->update($user, $document);
    }

    public function download(User $user, CandidateDocument $document): bool
    {
        return $this->view($user, $document);
    }
}
