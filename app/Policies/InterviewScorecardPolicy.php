<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\InterviewScorecard;
use App\Models\User;

class InterviewScorecardPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canView($user);
    }

    public function view(User $user, InterviewScorecard $scorecard): bool
    {
        return $this->canView($user);
    }

    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    public function update(User $user, InterviewScorecard $scorecard): bool
    {
        return $this->canManage($user);
    }

    private function canView(User $user): bool
    {
        return $user->hasPermission(PermissionName::ApplicationsView->value)
            || $user->hasPermission(PermissionName::ApplicationsManage->value);
    }

    private function canManage(User $user): bool
    {
        return $user->hasPermission(PermissionName::ApplicationsManage->value);
    }
}
