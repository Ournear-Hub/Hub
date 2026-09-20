<?php

namespace App\Enums;

enum LoginEvent: string
{
    case LoginSuccess = 'login_success';
    case LoginFailure = 'login_failure';
    case Logout = 'logout';
    case PasswordResetRequest = 'password_reset_request';
    case PasswordResetSuccess = 'password_reset_success';
    case AccountDisabled = 'account_disabled';

    public function label(): string
    {
        return match ($this) {
            self::LoginSuccess => 'Login success',
            self::LoginFailure => 'Login failure',
            self::Logout => 'Logout',
            self::PasswordResetRequest => 'Password reset requested',
            self::PasswordResetSuccess => 'Password reset completed',
            self::AccountDisabled => 'Account disabled',
        };
    }
}
