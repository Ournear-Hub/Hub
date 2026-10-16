<?php

namespace App\Services;

/**
 * Public Contact Us fields shared by web pages and /api/v1/site/contact.
 */
class SiteContactService
{
    public function __construct(private WebsiteSettingService $websiteSettings) {}

    /**
     * @return array{
     *     office_address: string|null,
     *     enquiry_email: string|null,
     *     contact_phone: string|null,
     *     whatsapp_number: string|null,
     *     whatsapp_url: string|null
     * }
     */
    public function publicContact(): array
    {
        $settings = $this->websiteSettings->current();

        $enquiry = $this->websiteSettings->supportEmail()
            ?? $this->websiteSettings->primaryEmail();

        $address = $this->trimOrNull($settings->office_address);
        $phone = $this->trimOrNull($settings->office_phone);

        return [
            'office_address' => $address,
            'enquiry_email' => $enquiry,
            'contact_phone' => $phone,
            'whatsapp_number' => $this->websiteSettings->primaryWhatsappDisplay(),
            'whatsapp_url' => $this->websiteSettings->primaryWhatsappUrl(),
        ];
    }

    /**
     * JSON payload for mobile clients (no admin-only fields).
     *
     * @return array<string, string|null>
     */
    public function apiPayload(): array
    {
        $contact = $this->publicContact();

        return [
            'office_address' => $contact['office_address'],
            'enquiry_email' => $contact['enquiry_email'],
            'contact_phone' => $contact['contact_phone'],
            'whatsapp_number' => $contact['whatsapp_number'],
            'terms_url' => route('terms', absolute: true),
            'privacy_url' => route('privacy-policy', absolute: true),
            'about_url' => route('home', absolute: true),
        ];
    }

    /**
     * Shape used in Inertia public content (extends API fields for link helpers).
     *
     * @return array<string, mixed>
     */
    public function inertiaContact(): array
    {
        $contact = $this->publicContact();
        $communication = $this->websiteSettings->publicCommunication();

        return [
            'email' => $communication['email'] !== '' ? $communication['email'] : null,
            'support_email' => $contact['enquiry_email'],
            'recruitment_email' => $communication['recruitment_email'],
            'phone' => $contact['contact_phone'],
            'address' => $contact['office_address'],
            'whatsapp' => $contact['whatsapp_number'],
            'whatsapp_url' => $contact['whatsapp_url'],
            'recruitment_whatsapp' => $communication['recruitment_whatsapp'],
            'recruitment_whatsapp_url' => $communication['recruitment_whatsapp_url'],
            'hours' => null,
            'help' => null,
        ];
    }

    private function trimOrNull(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);
        if ($trimmed === '' || preg_match('/to be published/i', $trimmed) === 1) {
            return null;
        }

        return $trimmed;
    }
}
