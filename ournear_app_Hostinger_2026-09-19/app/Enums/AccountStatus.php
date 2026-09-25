<?php

namespace App\Enums;

enum AccountStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Blocked = 'blocked';
    case Suspended = 'suspended';
    case PendingVerification = 'pending_verification';
    case Deactivated = 'deactivated';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Inactive => 'Inactive',
            self::Blocked => 'Blocked',
            self::Suspended => 'Suspended',
            self::PendingVerification => 'Pending verification',
            self::Deactivated => 'Deactivated',
        };
    }

    public function allowsSignIn(): bool
    {
        return $this === self::Active;
    }

    public function loginError(): string
    {
        return match ($this) {
            self::Blocked => 'This account is blocked.',
            self::Suspended => 'This account is suspended.',
            self::Deactivated => 'This account has been deactivated.',
            self::PendingVerification => 'This account is pending verification.',
            default => 'This account is inactive.',
        };
    }

    public function isDestructive(): bool
    {
        return in_array($this, [self::Blocked, self::Suspended, self::Deactivated], true);
    }
}
