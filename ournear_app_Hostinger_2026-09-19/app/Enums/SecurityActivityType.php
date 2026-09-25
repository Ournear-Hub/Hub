<?php

namespace App\Enums;

enum SecurityActivityType: string
{
    case UserCreated = 'user_created';
    case UserUpdated = 'user_updated';
    case UserActivated = 'user_activated';
    case UserDeactivated = 'user_deactivated';
    case RoleAssigned = 'role_assigned';
    case RoleRemoved = 'role_removed';
    case PermissionChanged = 'permission_changed';
    case PasswordResetSent = 'password_reset_sent';
    case UserBlocked = 'user_blocked';
    case UserSuspended = 'user_suspended';
    case InvoiceCancelled = 'invoice_cancelled';

    public function label(): string
    {
        return match ($this) {
            self::UserCreated => 'User created',
            self::UserUpdated => 'User updated',
            self::UserActivated => 'User activated',
            self::UserDeactivated => 'User deactivated',
            self::RoleAssigned => 'Role assigned',
            self::RoleRemoved => 'Role removed',
            self::PermissionChanged => 'Permissions changed',
            self::PasswordResetSent => 'Password reset sent',
            self::UserBlocked => 'User blocked',
            self::UserSuspended => 'User suspended',
            self::InvoiceCancelled => 'Invoice cancelled',
        };
    }
}
