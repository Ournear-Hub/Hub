<?php

namespace App\Policies;

use App\Models\Application;
use App\Models\User;

class ApplicationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManageRecruitment();
    }

    public function view(User $user, Application $application): bool
    {
        if ($user->canManageRecruitment()) {
            return true;
        }

        return $this->ownedByCandidate($user, $application);
    }

    public function update(User $user, Application $application): bool
    {
        return $user->canManageRecruitment();
    }

    public function downloadResume(User $user, Application $application): bool
    {
        if ($user->canManageRecruitment()) {
            return true;
        }

        return $this->ownedByCandidate($user, $application);
    }

    public function replaceResume(User $user, Application $application): bool
    {
        return $this->ownedByCandidate($user, $application);
    }

    public function withdraw(User $user, Application $application): bool
    {
        return $this->ownedByCandidate($user, $application);
    }

    private function ownedByCandidate(User $user, Application $application): bool
    {
        return $user->isCandidate() && $application->user_id !== null
            && (int) $application->user_id === (int) $user->id;
    }
}
