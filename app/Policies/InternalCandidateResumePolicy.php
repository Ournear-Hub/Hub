<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\InternalCandidateResume;
use App\Models\User;

class InternalCandidateResumePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canView($user);
    }

    public function view(User $user, InternalCandidateResume $internalCandidateResume): bool
    {
        return $this->canView($user);
    }

    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    public function update(User $user, InternalCandidateResume $internalCandidateResume): bool
    {
        return $this->canManage($user);
    }

    public function delete(User $user, InternalCandidateResume $internalCandidateResume): bool
    {
        return $this->canManage($user);
    }

    public function download(User $user, InternalCandidateResume $internalCandidateResume): bool
    {
        return $this->canView($user);
    }

    private function canView(User $user): bool
    {
        return $user->hasPermission(PermissionName::CandidatesView->value)
            || $user->hasPermission(PermissionName::CandidatesManage->value);
    }

    private function canManage(User $user): bool
    {
        return $user->hasPermission(PermissionName::CandidatesManage->value);
    }
}
