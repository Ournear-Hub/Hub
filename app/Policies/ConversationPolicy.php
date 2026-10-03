<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\Conversation;
use App\Models\User;

class ConversationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(PermissionName::MessagesManage->value);
    }

    public function view(User $user, Conversation $conversation): bool
    {
        if (! $user->hasPermission(PermissionName::MessagesManage->value)) {
            return false;
        }

        return $user->isSuperAdmin() || $conversation->hasParticipant($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(PermissionName::MessagesManage->value);
    }

    public function update(User $user, Conversation $conversation): bool
    {
        return $user->hasPermission(PermissionName::MessagesManage->value)
            && $conversation->hasParticipant($user);
    }
}
