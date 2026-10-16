<?php

namespace App\Http\Requests\Account;

use App\Enums\PreferredLanguage;
use App\Http\Requests\Concerns\ValidatesAccountMobile;
use App\Models\User;
use App\Support\AccountPassword;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class RegisterRequest extends FormRequest
{
    use ValidatesAccountMobile;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'username' => User::normalizeUsername($this->input('username')),
        ]);

        $this->merge([
            'email' => is_string($this->input('email'))
                ? strtolower(trim($this->input('email')))
                : $this->input('email'),
        ]);
        $this->mergeNormalizedMobile();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'min:3', 'max:64', 'alpha_dash', Rule::unique('users', 'username')],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'mobile' => ['required', 'string', 'max:32'],
            'password' => ['required', 'confirmed', AccountPassword::rules()],
            'preferred_language' => ['nullable', Rule::enum(PreferredLanguage::class)],
            'role' => ['prohibited'],
            'accept_terms' => ['accepted'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $this->validateNormalizedMobile($validator, null, null);

        $validator->after(function (Validator $validator): void {
            $username = User::normalizeUsername($this->input('username'));
            if ($username !== '' && ! User::usernameIsValid($username)) {
                $validator->errors()->add(
                    'username',
                    'Usernames may use letters, numbers, dashes, and underscores (3–64 characters).',
                );
            }
        });
    }
}
