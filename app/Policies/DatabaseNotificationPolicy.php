<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;

class DatabaseNotificationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isCandidate()
            || $user->canAccessHiringWorkspace()
            || $user->hasPermission(PermissionName::NotificationsView->value);
    }

    public function view(User $user, DatabaseNotification $notification): bool
    {
        if ($this->owns($user, $notification)) {
            return $user->isCandidate()
                || $user->canAccessHiringWorkspace()
                || $user->hasPermission(PermissionName::NotificationsView->value);
        }

        return $user->isSuperAdmin() && $user->hasPermission(PermissionName::NotificationsView->value);
    }

    public function update(User $user, DatabaseNotification $notification): bool
    {
        return $this->owns($user, $notification)
            && ($user->isCandidate()
                || $user->canAccessHiringWorkspace()
                || $user->hasPermission(PermissionName::NotificationsView->value));
    }

    private function owns(User $user, DatabaseNotification $notification): bool
    {
        return (int) $notification->notifiable_id === (int) $user->id
            && $notification->notifiable_type === $user->getMorphClass();
    }
}
