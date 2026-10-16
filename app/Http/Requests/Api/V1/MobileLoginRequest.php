<?php

namespace App\Http\Requests\Api\V1;

use App\Models\User;
use App\Services\AccountCredentialService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class MobileLoginRequest extends FormRequest
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
            'device_name' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @throws ValidationException
     */
    public function validatedUser(): User
    {
        return app(AccountCredentialService::class)->validateAccountLogin(
            $this,
            (string) $this->string('login'),
            (string) $this->string('password'),
        );
    }
}
