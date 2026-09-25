<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateAdminProfileRequest extends FormRequest
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
        $userId = $this->user()?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:64', 'alpha_dash', Rule::unique('users', 'username')->ignore($userId)],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'mobile' => ['nullable', 'string', 'max:32'],
            'location' => ['nullable', 'string', 'max:255'],
            'preferred_language' => ['required', 'string', Rule::in(['en', 'te', 'hi'])],
            'department' => ['nullable', 'string', 'max:255'],
            'designation' => ['nullable', 'string', 'max:255'],
            'joined_at' => ['nullable', 'date'],
            'professional_details' => ['nullable', 'string'],
            'qualifications' => ['nullable', 'string'],
            'skills' => ['nullable', 'string'],
            'employment_information' => ['nullable', 'string'],
            'photo' => ['nullable', 'image', 'max:5120'],
            'remove_photo' => ['sometimes', 'boolean'],
        ];
    }
}
