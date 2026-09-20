<?php

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAccountMember() === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        if ($this->user()?->isCandidate()) {
            return [
                'name' => ['required', 'string', 'max:255'],
                'mobile' => ['nullable', 'string', 'max:40'],
                'location' => ['nullable', 'string', 'max:255'],
                'experience' => ['nullable', 'string', 'max:255'],
                'qualification' => ['nullable', 'string', 'max:255'],
            ];
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'mobile_number' => ['nullable', 'string', 'max:40'],
            'industry' => ['nullable', 'string', 'max:255'],
        ];
    }
}
