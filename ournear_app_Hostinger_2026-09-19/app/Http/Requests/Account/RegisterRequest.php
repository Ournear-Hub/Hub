<?php

namespace App\Http\Requests\Account;

use App\Enums\PreferredLanguage;
use App\Enums\UserRole;
use App\Models\User;
use App\Support\AccountPassword;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'username' => User::normalizeUsername($this->input('username')),
            'email' => is_string($this->input('email'))
                ? strtolower(trim($this->input('email')))
                : $this->input('email'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => User::usernameRules(),
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'mobile' => ['nullable', 'string', 'max:32'],
            'password' => ['required', 'confirmed', AccountPassword::rules()],
            'preferred_language' => ['nullable', Rule::enum(PreferredLanguage::class)],
            'role' => ['required', Rule::in([UserRole::Candidate->value, UserRole::Employer->value])],
        ];
    }
}
