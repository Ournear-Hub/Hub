<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;

class DatabaseNotificationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManageRecruitment();
    }

    public function view(User $user, DatabaseNotification $notification): bool
    {
        return $user->canManageRecruitment()
            && (int) $notification->notifiable_id === (int) $user->id
            && $notification->notifiable_type === $user->getMorphClass();
    }

    public function update(User $user, DatabaseNotification $notification): bool
    {
        return $this->view($user, $notification);
    }
}
