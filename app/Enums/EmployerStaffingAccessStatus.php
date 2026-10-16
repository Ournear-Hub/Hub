<?php

namespace App\Enums;

enum EmployerStaffingAccessStatus: string
{
    case NotRequested = 'not_requested';
    case PendingApproval = 'pending_approval';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Revoked = 'revoked';

    public function label(): string
    {
        return match ($this) {
            self::NotRequested => 'Not requested',
            self::PendingApproval => 'Pending approval',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::Revoked => 'Revoked',
        };
    }

    public function canUseStaffingWorkspace(): bool
    {
        return $this === self::Approved;
    }

    public function canSubmitStaffingRequirement(): bool
    {
        return $this === self::Approved;
    }
}
