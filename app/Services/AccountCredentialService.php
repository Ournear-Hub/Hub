<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AccountCredentialService
{
    public function __construct(private LoginLogService $loginLogs) {}

    /**
     * Validate account member credentials (candidate/employer). Does not start a session.
     *
     * @throws ValidationException
     */
    public function validateAccountLogin(Request $request, string $login, string $password): User
    {
        $this->ensureIsNotRateLimited($request, $login);

        $user = $this->findAccountUser($login);

        if (! $user instanceof User || ! Hash::check($password, $user->password)) {
            RateLimiter::hit($this->throttleKey($request, $login));
            $this->loginLogs->failure($request);

            throw ValidationException::withMessages([
                'login' => 'These credentials do not match our records.',
            ]);
        }

        if (! in_array($user->role, [UserRole::Candidate, UserRole::Employer], true)) {
            RateLimiter::hit($this->throttleKey($request, $login));
            $this->loginLogs->failure($request, $user, 'not_account_member');

            throw ValidationException::withMessages([
                'login' => 'These credentials do not match our records.',
            ]);
        }

        if (! $user->canSignIn()) {
            RateLimiter::hit($this->throttleKey($request, $login));
            $this->loginLogs->failure($request, $user, $user->accountStatus()->value);

            throw ValidationException::withMessages([
                'login' => $user->accountStatus()->loginError(),
            ]);
        }

        RateLimiter::clear($this->throttleKey($request, $login));

        return $user;
    }

    private function ensureIsNotRateLimited(Request $request, string $login): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($request, $login), 5)) {
            return;
        }

        event(new Lockout($request));

        $seconds = RateLimiter::availableIn($this->throttleKey($request, $login));

        throw ValidationException::withMessages([
            'login' => 'Too many login attempts. Please try again in '.$seconds.' seconds.',
        ]);
    }

    private function throttleKey(Request $request, string $login): string
    {
        return Str::transliterate(Str::lower($login).'|'.$request->ip());
    }

    private function findAccountUser(string $login): ?User
    {
        if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            return User::query()->whereRaw('LOWER(email) = ?', [Str::lower($login)])->first();
        }

        $username = User::normalizeUsername($login);
        if ($username === '') {
            return null;
        }

        return User::query()->where('username', $username)->first();
    }

}
