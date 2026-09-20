<?php

namespace App\Services;

use App\Enums\LoginEvent;
use App\Models\LoginLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LoginLogService
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function record(
        LoginEvent $event,
        Request $request,
        ?User $user = null,
        ?string $email = null,
        bool $succeeded = false,
        array $metadata = [],
    ): void {
        $attempted = strtolower(trim($email ?? (string) $request->input('email', $user?->email ?? '')));
        if ($attempted === '') {
            $attempted = $user?->email ?? 'unknown';
        }

        $agent = $request->userAgent();

        LoginLog::query()->create([
            'user_id' => $user?->id,
            'email' => $attempted,
            'event' => $event,
            'ip_address' => $request->ip(),
            'user_agent' => $agent === null || $agent === '' ? null : Str::limit($agent, 512, ''),
            'succeeded' => $succeeded,
            'metadata' => $metadata === [] ? null : $metadata,
        ]);
    }

    public function success(Request $request, User $user, string $guard = 'web'): void
    {
        $this->record(LoginEvent::LoginSuccess, $request, $user, $user->email, true, [
            'guard' => $guard,
        ]);
    }

    public function failure(Request $request, ?User $user = null, string $reason = 'invalid_credentials'): void
    {
        $this->record(LoginEvent::LoginFailure, $request, $user, $request->string('email')->toString(), false, [
            'reason' => $reason,
        ]);
    }

    public function logout(Request $request, ?User $user): void
    {
        if (! $user) {
            return;
        }

        $this->record(LoginEvent::Logout, $request, $user, $user->email, true);
    }

    public function passwordResetRequest(Request $request, ?User $user, string $email): void
    {
        $this->record(LoginEvent::PasswordResetRequest, $request, $user, $email, $user !== null);
    }

    public function passwordResetSuccess(Request $request, User $user): void
    {
        $this->record(LoginEvent::PasswordResetSuccess, $request, $user, $user->email, true);
    }

    public function accountDisabled(Request $request, User $user): void
    {
        $this->record(LoginEvent::AccountDisabled, $request, $user, $user->email, true);
    }
}
