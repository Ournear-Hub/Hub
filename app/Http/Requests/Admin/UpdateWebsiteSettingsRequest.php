<?php

namespace App\Http\Requests\Admin;

use App\Support\WhatsAppNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateWebsiteSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canManageWebsite() === true;
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        foreach (['office_email', 'support_email', 'recruitment_email', 'reply_to_email'] as $field) {
            if (! $this->exists($field)) {
                continue;
            }

            $value = $this->input($field);
            if (! is_string($value)) {
                continue;
            }

            $trimmed = trim($value);
            if ($trimmed === '' || str_contains(strtolower($trimmed), 'to be published')) {
                $merge[$field] = null;
            } else {
                $merge[$field] = strtolower($trimmed);
            }
        }

        foreach (['primary_whatsapp', 'recruitment_whatsapp'] as $field) {
            if (! $this->exists($field)) {
                continue;
            }

            $value = $this->input($field);
            if (! is_string($value)) {
                continue;
            }

            $merge[$field] = trim($value) === '' ? null : trim($value);
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:100'],
            'office_phone' => ['nullable', 'string', 'max:255'],
            'office_email' => ['nullable', 'email', 'max:255'],
            'support_email' => ['nullable', 'email', 'max:255'],
            'recruitment_email' => ['nullable', 'email', 'max:255'],
            'reply_to_email' => ['nullable', 'email', 'max:255'],
            'primary_whatsapp' => ['nullable', 'string', 'max:32'],
            'recruitment_whatsapp' => ['nullable', 'string', 'max:32'],
            'website_url' => ['nullable', 'string', 'max:500'],
            'office_address' => ['nullable', 'string', 'max:1000'],
            'copyright_text' => ['nullable', 'string', 'max:255'],
            'default_seo_title' => ['nullable', 'string', 'max:255'],
            'default_meta_description' => ['nullable', 'string', 'max:500'],
            'notification_emails' => ['nullable', 'string', 'max:2000'],
            'smtp_password' => ['prohibited'],
            'smtp_username' => ['prohibited'],
            'mail_password' => ['prohibited'],
            'whatsapp_api_secret' => ['prohibited'],
            'meta_access_token' => ['prohibited'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $url = trim((string) $this->input('website_url', ''));
            if ($url === '') {
                return;
            }

            if (filter_var($url, FILTER_VALIDATE_URL) === false) {
                $validator->errors()->add('website_url', 'Enter a valid http or https URL.');

                return;
            }

            $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
            if (! in_array($scheme, ['http', 'https'], true)) {
                $validator->errors()->add('website_url', 'Enter a valid http or https URL.');
            }
        });

        $validator->after(function (Validator $validator): void {
            $raw = (string) $this->input('notification_emails', '');
            if (trim($raw) === '') {
                return;
            }

            $parts = preg_split('/[\s,;]+/', $raw) ?: [];
            $seen = [];
            foreach ($parts as $part) {
                $email = strtolower(trim((string) $part));
                if ($email === '') {
                    continue;
                }
                if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                    $validator->errors()->add('notification_emails', 'Each notification recipient must be a valid email address.');

                    return;
                }
                $seen[$email] = true;
            }

            if (count($seen) > 20) {
                $validator->errors()->add('notification_emails', 'You can add up to 20 notification email addresses.');
            }
        });

        $validator->after(function (Validator $validator): void {
            foreach (['primary_whatsapp' => 'primary WhatsApp number', 'recruitment_whatsapp' => 'recruitment WhatsApp number'] as $field => $label) {
                $raw = $this->input($field);
                if (! is_string($raw) || $raw === '') {
                    continue;
                }

                $normalized = WhatsAppNumber::normalize($raw);
                if ($normalized === false) {
                    $validator->errors()->add(
                        $field,
                        'Enter a valid '.$label.' with country code. India numbers can use +91, 91, or a 10-digit mobile number.',
                    );

                    continue;
                }

                $this->merge([$field => $normalized]);
            }
        });
    }
}
