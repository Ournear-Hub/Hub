<?php

namespace App\Http\Requests\Account;

use App\Services\AccountCredentialService;
use App\Services\LoginLogService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
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
        $login = (string) $this->string('login');
        $password = (string) $this->string('password');

        try {
            $user = app(AccountCredentialService::class)->validateAccountLogin($this, $login, $password);
        } catch (ValidationException $exception) {
            throw ValidationException::withMessages($this->loginErrors(
                (string) ($exception->errors()['login'][0] ?? 'These credentials do not match our records.')
            ));
        }

        Auth::guard('account')->login($user, $this->boolean('remember'));
        app(LoginLogService::class)->success($this, $user, 'account');
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

}
