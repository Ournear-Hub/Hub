<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\Freelancer;
use App\Models\User;

class FreelancerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(PermissionName::FreelancersView->value)
            || $user->hasPermission(PermissionName::FreelancersManage->value);
    }

    public function view(User $user, Freelancer $freelancer): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Freelancer $freelancer): bool
    {
        return $user->hasPermission(PermissionName::FreelancersManage->value);
    }

    public function download(User $user, Freelancer $freelancer): bool
    {
        return $this->view($user, $freelancer);
    }
}
