<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\MessageTemplate;
use App\Models\User;

class MessageTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canView($user);
    }

    public function view(User $user, MessageTemplate $template): bool
    {
        return $this->canView($user);
    }

    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    public function update(User $user, MessageTemplate $template): bool
    {
        return $this->canManage($user);
    }

    public function preview(User $user): bool
    {
        return $this->canView($user);
    }

    private function canView(User $user): bool
    {
        return $user->hasPermission(PermissionName::NotificationsView->value)
            || $user->hasPermission(PermissionName::CampaignsView->value)
            || $user->hasPermission(PermissionName::CampaignsManage->value);
    }

    private function canManage(User $user): bool
    {
        return $user->hasPermission(PermissionName::CampaignsManage->value);
    }
}
