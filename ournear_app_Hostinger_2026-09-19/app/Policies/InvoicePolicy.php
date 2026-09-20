<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManageRecruitment();
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $user->canManageRecruitment();
    }

    public function create(User $user): bool
    {
        return $user->canManageRecruitment();
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $user->canManageRecruitment();
    }
}
