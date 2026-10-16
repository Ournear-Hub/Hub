<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\Application;
use App\Models\User;
use App\Support\EmployerApplicationAccess;

class ApplicationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(PermissionName::ApplicationsView->value)
            || $user->hasPermission(PermissionName::ApplicationsManage->value);
    }

    public function view(User $user, Application $application): bool
    {
        if ($this->viewAny($user)) {
            return true;
        }

        return $this->ownedByCandidate($user, $application);
    }

    public function update(User $user, Application $application): bool
    {
        return $user->hasPermission(PermissionName::ApplicationsManage->value);
    }

    public function downloadResume(User $user, Application $application): bool
    {
        if ($this->view($user, $application)) {
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

    public function manageAsEmployer(User $user, Application $application): bool
    {
        return EmployerApplicationAccess::managesApplication($user, $application);
    }

    public function respondToInterview(User $user, Application $application): bool
    {
        return $this->ownedByCandidate($user, $application);
    }

    public function submitCorrectionRequest(User $user, Application $application): bool
    {
        return $this->ownedByCandidate($user, $application);
    }

    private function ownedByCandidate(User $user, Application $application): bool
    {
        return $user->isCandidate() && $application->user_id !== null
            && (int) $application->user_id === (int) $user->id;
    }
}
