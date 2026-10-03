<?php

namespace App\Http\Requests\Account;

use App\Enums\PreferredLanguage;
use App\Http\Requests\Concerns\ValidatesAccountMobile;
use App\Http\Requests\Concerns\ValidatesAccountWhatsApp;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateAccountSettingsRequest extends FormRequest
{
    use ValidatesAccountMobile;
    use ValidatesAccountWhatsApp;

    public function authorize(): bool
    {
        return $this->user()?->isAccountMember() === true;
    }

    protected function prepareForValidation(): void
    {
        $this->mergeNormalizedMobile();
        $this->mergeNormalizedWhatsApp();
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
            'whatsapp_number' => ['nullable', 'string', 'max:32'],
            'preferred_language' => ['required', Rule::enum(PreferredLanguage::class)],
            'email' => ['prohibited'],
            'pending_email' => ['prohibited'],
            'mobile_verified_at' => ['prohibited'],
            'whatsapp_verified_at' => ['prohibited'],
            'email_verified_at' => ['prohibited'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $this->validateNormalizedMobile($validator, $this->user()?->mobile, $this->user()?->id);
        $this->validateNormalizedWhatsApp($validator, $this->user()?->whatsapp_number, $this->user()?->id);
    }

    /**
     * @return array<string, mixed>
     */
    public function accountAttributes(): array
    {
        $validated = $this->validated();
        $mobile = $validated['mobile'] ?? null;
        $whatsapp = $validated['whatsapp_number'] ?? ($this->user()?->whatsapp_number);

        return [
            'username' => $this->string('username')->toString(),
            'mobile' => is_string($mobile) && $mobile !== '' ? $mobile : (array_key_exists('mobile', $validated) ? null : $this->user()?->mobile),
            'whatsapp_number' => is_string($whatsapp) && $whatsapp !== '' ? $whatsapp : (array_key_exists('whatsapp_number', $validated) ? null : $this->user()?->whatsapp_number),
            'preferred_language' => $this->string('preferred_language')->toString(),
        ];
    }
}
