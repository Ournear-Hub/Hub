<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateWebsiteSettingsRequest extends FormRequest
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
            'company_name' => ['required', 'string', 'max:100'],
            'office_phone' => ['nullable', 'string', 'max:255'],
            'office_email' => ['nullable', 'email', 'max:255'],
            'website_url' => ['nullable', 'string', 'max:500'],
            'office_address' => ['nullable', 'string', 'max:1000'],
            'copyright_text' => ['nullable', 'string', 'max:255'],
            'default_seo_title' => ['nullable', 'string', 'max:255'],
            'default_meta_description' => ['nullable', 'string', 'max:500'],
            'notification_emails' => ['nullable', 'string', 'max:2000'],
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
            foreach ($parts as $index => $part) {
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
    }
}
