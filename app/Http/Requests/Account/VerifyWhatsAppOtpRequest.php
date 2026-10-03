<?php

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;

class VerifyWhatsAppOtpRequest extends FormRequest
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
        return [
            'otp' => ['required', 'string', 'size:6', 'regex:/^[0-9]{6}$/'],
            'user_id' => ['prohibited'],
            'whatsapp_number' => ['prohibited'],
            'mobile' => ['prohibited'],
            'code' => ['prohibited'],
            'code_hash' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'otp.regex' => 'Enter the 6-digit verification code.',
            'otp.size' => 'Enter the 6-digit verification code.',
        ];
    }
}
