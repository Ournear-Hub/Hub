<?php

namespace App\Http\Requests\Concerns;

use App\Support\UserWhatsAppNumber;
use Illuminate\Validation\Validator;

trait ValidatesAccountWhatsApp
{
    protected function mergeNormalizedWhatsApp(): void
    {
        if (! $this->exists('whatsapp_number')) {
            return;
        }

        $value = $this->input('whatsapp_number');
        if (! is_string($value) || trim($value) === '') {
            $this->merge(['whatsapp_number' => null]);

            return;
        }

        $this->merge(['whatsapp_number' => trim($value)]);
    }

    protected function validateNormalizedWhatsApp(Validator $validator, ?string $current, ?int $ignoreUserId): void
    {
        $validator->after(function (Validator $validator) use ($current, $ignoreUserId): void {
            $submitted = $this->input('whatsapp_number');
            if (! is_string($submitted) || $submitted === '') {
                return;
            }

            if (is_string($current) && trim($submitted) === trim($current)) {
                $normalizedCurrent = UserWhatsAppNumber::normalize($current);
                if (is_string($normalizedCurrent)) {
                    $this->merge(['whatsapp_number' => $normalizedCurrent]);
                }

                return;
            }

            $normalized = UserWhatsAppNumber::normalize($submitted);
            if (! is_string($normalized)) {
                $validator->errors()->add(
                    'whatsapp_number',
                    'Enter a valid Indian WhatsApp number. Use +91, 91, or a 10-digit mobile number starting with 6–9.',
                );

                return;
            }

            $this->merge(['whatsapp_number' => $normalized]);

            if (UserWhatsAppNumber::same($normalized, $current)) {
                return;
            }

            if (UserWhatsAppNumber::takenByAnotherUser($normalized, $ignoreUserId)) {
                $validator->errors()->add('whatsapp_number', 'That WhatsApp number is already in use.');

                return;
            }

            $settings = app(\App\Services\WebsiteSettingService::class);
            foreach ([$settings->primaryWhatsapp(), $settings->recruitmentWhatsapp()] as $companyNumber) {
                if (is_string($companyNumber) && $companyNumber === $normalized) {
                    $validator->errors()->add(
                        'whatsapp_number',
                        'That number is a company WhatsApp identity and cannot be used as a personal account number.',
                    );

                    return;
                }
            }
        });
    }
}
