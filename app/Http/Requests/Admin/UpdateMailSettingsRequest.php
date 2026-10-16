<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMailSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canManageWebsite() === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'mail_mailer' => ['required', Rule::in(['smtp', 'log', 'array', 'sendmail'])],
            'mail_enabled' => ['sometimes', 'boolean'],
            'smtp_host' => ['nullable', 'string', 'max:255'],
            'smtp_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'smtp_encryption' => ['nullable', Rule::in(['tls', 'ssl', 'none', ''])],
            'smtp_username' => ['nullable', 'string', 'max:255'],
            'smtp_password' => ['nullable', 'string', 'max:500'],
            'clear_smtp_password' => ['sometimes', 'boolean'],
            'smtp_timeout' => ['nullable', 'integer', 'min:3', 'max:120'],
            'mail_use_queue' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'mail_enabled' => $this->boolean('mail_enabled'),
            'mail_use_queue' => $this->boolean('mail_use_queue'),
            'clear_smtp_password' => $this->boolean('clear_smtp_password'),
        ]);
    }
}
