<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ForceUpdatePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'password' => ['required', 'confirmed', Password::defaults()],
        ];

        if (! $this->user()?->must_change_password) {
            $rules['current_password'] = ['required', 'current_password'];
        }

        return $rules;
    }
}
