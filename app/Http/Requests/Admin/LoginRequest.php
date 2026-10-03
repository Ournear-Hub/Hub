<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use App\Services\LoginLogService;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ];
    }

    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::guard('admin')->attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());
            app(LoginLogService::class)->failure($this);

            throw ValidationException::withMessages([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        $user = Auth::guard('admin')->user();

        if (! $user instanceof User || ! $user->isAdmin()) {
            Auth::guard('admin')->logout();
            RateLimiter::hit($this->throttleKey());
            app(LoginLogService::class)->failure($this, $user instanceof User ? $user : null, 'not_admin');

            throw ValidationException::withMessages([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        if (! $user->canSignIn()) {
            Auth::guard('admin')->logout();
            RateLimiter::hit($this->throttleKey());
            app(LoginLogService::class)->failure($this, $user, $user->accountStatus()->value);

            throw ValidationException::withMessages([
                'email' => $user->accountStatus()->loginError(),
            ]);
        }

        app(LoginLogService::class)->success($this, $user, 'admin');
        RateLimiter::clear($this->throttleKey());
    }

    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => 'Too many login attempts. Please try again in '.$seconds.' seconds.',
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
