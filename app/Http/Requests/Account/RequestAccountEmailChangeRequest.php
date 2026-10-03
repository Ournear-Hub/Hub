<?php

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;

class RequestAccountEmailChangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAccountMember() === true
            && $this->user()?->can('update', $this->user()) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'user_id' => ['prohibited'],
            'email_verified_at' => ['prohibited'],
            'pending_email' => ['prohibited'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $email = $this->input('email');
        if (is_string($email)) {
            $this->merge(['email' => strtolower(trim($email))]);
        }
    }
}
