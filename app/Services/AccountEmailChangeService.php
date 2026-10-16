<?php

namespace App\Services;

use App\Enums\UserActivityType;
use App\Models\User;
use App\Notifications\AccountEmailChangeVerificationNotification;
use App\Support\ApplicationUrlResolver;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

class AccountEmailChangeService
{
    public function __construct(private UserActivityService $activities) {}

    public function requestChange(User $user, string $newEmail): void
    {
        abort_unless($user->isAccountMember(), 403);

        $newEmail = strtolower(trim($newEmail));
        if ($newEmail === '' || ! filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages([
                'email' => 'Enter a valid email address.',
            ]);
        }

        if ($newEmail === strtolower((string) $user->email)) {
            throw ValidationException::withMessages([
                'email' => 'That is already your account email.',
            ]);
        }

        if (User::query()->where('email', $newEmail)->whereKeyNot($user->id)->exists()) {
            throw ValidationException::withMessages([
                'email' => 'That email address is already in use.',
            ]);
        }

        if (Schema::hasColumn($user->getTable(), 'pending_email')) {
            $pendingTaken = User::query()
                ->where('pending_email', $newEmail)
                ->whereKeyNot($user->id)
                ->exists();
            if ($pendingTaken) {
                throw ValidationException::withMessages([
                    'email' => 'That email address is already in use.',
                ]);
            }
        }

        if ($user->hasVerifiedEmail()) {
            if (! Schema::hasColumn($user->getTable(), 'pending_email')) {
                throw ValidationException::withMessages([
                    'email' => 'Email change is not available yet. Contact support.',
                ]);
            }

            $user->forceFill(['pending_email' => $newEmail])->save();
            $this->sendPendingVerification($user);

            $this->activities->record(
                $user,
                UserActivityType::SecurityEvent,
                'A new email address was requested for this account.',
                $user,
            );

            return;
        }

        $user->forceFill([
            'email' => $newEmail,
            'email_verified_at' => null,
            'pending_email' => null,
        ])->save();

        $user->sendEmailVerificationNotification();

        $this->activities->record(
            $user,
            UserActivityType::SecurityEvent,
            'Account email was updated before verification.',
            $user,
        );
    }

    public function sendPendingVerification(User $user): void
    {
        $pending = strtolower(trim((string) $user->pending_email));
        if ($pending === '') {
            return;
        }

        app(ApplicationUrlResolver::class)->applyForUrlGeneration();

        $url = URL::temporarySignedRoute(
            'account.settings.email.verify-change',
            now()->addHours(24),
            [
                'id' => $user->id,
                'hash' => sha1($pending),
            ],
        );

        Notification::route('mail', $pending)
            ->notify(new AccountEmailChangeVerificationNotification($url));
    }

    public function confirmPendingEmail(User $user, string $hash): void
    {
        if (! Schema::hasColumn($user->getTable(), 'pending_email')) {
            throw ValidationException::withMessages([
                'email' => 'This email change link is not valid.',
            ]);
        }

        $pending = strtolower(trim((string) $user->pending_email));
        if ($pending === '' || ! hash_equals(sha1($pending), $hash)) {
            throw ValidationException::withMessages([
                'email' => 'This email change link is not valid.',
            ]);
        }

        if (User::query()->where('email', $pending)->whereKeyNot($user->id)->exists()) {
            throw ValidationException::withMessages([
                'email' => 'That email address is no longer available.',
            ]);
        }

        $previous = strtolower((string) $user->email);
        $user->forceFill([
            'email' => $pending,
            'pending_email' => null,
            'email_verified_at' => now(),
        ])->save();

        $this->activities->record(
            $user,
            UserActivityType::EmailVerified,
            'Email address was changed and verified.',
            $user,
            ['from' => $previous, 'to' => $pending],
        );
    }

    public function pendingEmail(User $user): ?string
    {
        if (! Schema::hasColumn($user->getTable(), 'pending_email')) {
            return null;
        }

        $pending = strtolower(trim((string) $user->pending_email));

        return $pending !== '' ? $pending : null;
    }
}
