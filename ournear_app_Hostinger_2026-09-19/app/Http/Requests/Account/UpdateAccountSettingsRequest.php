<?php

namespace App\Http\Requests\Account;

use App\Enums\PreferredLanguage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAccountSettingsRequest extends FormRequest
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
        $userId = $this->user()?->id;

        return [
            'username' => ['required', 'string', 'max:64', 'alpha_dash', Rule::unique('users', 'username')->ignore($userId)],
            'mobile' => ['nullable', 'string', 'max:32'],
            'preferred_language' => ['required', Rule::enum(PreferredLanguage::class)],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function accountAttributes(): array
    {
        $mobile = $this->string('mobile')->toString();

        return [
            'username' => $this->string('username')->toString(),
            'mobile' => $mobile === '' ? null : $mobile,
            'preferred_language' => $this->string('preferred_language')->toString(),
        ];
    }
}
