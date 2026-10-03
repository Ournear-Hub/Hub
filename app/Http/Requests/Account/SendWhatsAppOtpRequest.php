<?php

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;

class SendWhatsAppOtpRequest extends FormRequest
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
            'user_id' => ['prohibited'],
            'whatsapp_number' => ['prohibited'],
            'mobile' => ['prohibited'],
            'otp' => ['prohibited'],
            'code' => ['prohibited'],
            'code_hash' => ['prohibited'],
        ];
    }
}
