<?php

namespace App\Http\Requests\Account;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\LoginLogService;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $login = $this->input('login', $this->input('email'));

        $this->merge([
            'login' => is_string($login) ? trim($login) : $login,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ];
    }

    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $login = (string) $this->string('login');
        $password = (string) $this->string('password');
        $user = $this->findAccountUser($login);

        if ($user !== null && $this->isUnverifiedEmailLogin($login, $user)) {
            RateLimiter::hit($this->throttleKey());
            app(LoginLogService::class)->failure($this, $user, 'unverified_email_login');

            throw ValidationException::withMessages($this->loginErrors(
                'Your email is not verified. Please sign in using your username.'
            ));
        }

        if (! $user instanceof User || ! Hash::check($password, $user->password)) {
            RateLimiter::hit($this->throttleKey());
            app(LoginLogService::class)->failure($this);

            throw ValidationException::withMessages($this->loginErrors('These credentials do not match our records.'));
        }

        if (! in_array($user->role, [UserRole::Candidate, UserRole::Employer], true)) {
            RateLimiter::hit($this->throttleKey());
            app(LoginLogService::class)->failure($this, $user, 'not_account_member');

            throw ValidationException::withMessages($this->loginErrors('These credentials do not match our records.'));
        }

        if (! $user->canSignIn()) {
            RateLimiter::hit($this->throttleKey());
            app(LoginLogService::class)->failure($this, $user, $user->accountStatus()->value);

            throw ValidationException::withMessages($this->loginErrors($user->accountStatus()->loginError()));
        }

        Auth::login($user, $this->boolean('remember'));
        app(LoginLogService::class)->success($this, $user, 'account');
        RateLimiter::clear($this->throttleKey());
    }

    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages($this->loginErrors(
            'Too many login attempts. Please try again in '.$seconds.' seconds.'
        ));
    }

    /**
     * @return array<string, string>
     */
    private function loginErrors(string $message): array
    {
        $errors = ['login' => $message];

        if ($this->filled('email') || filter_var((string) $this->string('login'), FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = $message;
        }

        return $errors;
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower((string) $this->string('login')).'|'.$this->ip());
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

    private function isUnverifiedEmailLogin(string $login, User $user): bool
    {
        return filter_var($login, FILTER_VALIDATE_EMAIL) !== false
            && ! $user->hasVerifiedEmail();
    }
}
