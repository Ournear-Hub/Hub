<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;

class ConversationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManageRecruitment();
    }

    public function view(User $user, Conversation $conversation): bool
    {
        return $user->canManageRecruitment() && $conversation->hasParticipant($user);
    }

    public function create(User $user): bool
    {
        return $user->canManageRecruitment();
    }

    public function update(User $user, Conversation $conversation): bool
    {
        return $this->view($user, $conversation);
    }
}
