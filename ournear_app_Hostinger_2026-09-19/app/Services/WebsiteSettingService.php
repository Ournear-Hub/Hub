<?php

namespace App\Services;

use App\Models\WebsiteSetting;
use App\Support\PublicContent;

class WebsiteSettingService
{
    public function __construct(private BrandingAssetService $brandingAssets) {}

    public function current(): WebsiteSetting
    {
        $row = WebsiteSetting::query()->where('lock_key', 'default')->first()
            ?? WebsiteSetting::query()->first();

        if ($row) {
            if ($row->lock_key !== 'default') {
                $row->lock_key = 'default';
                $row->save();
            }

            return $row;
        }

        return WebsiteSetting::query()->create($this->defaults());
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(array $payload): WebsiteSetting
    {
        $row = $this->current();
        $row->update([
            'company_name' => trim((string) $payload['company_name']),
            'office_phone' => $this->nullableString($payload['office_phone'] ?? null),
            'office_email' => $this->nullableString($payload['office_email'] ?? null),
            'website_url' => $this->nullableString($payload['website_url'] ?? null),
            'office_address' => $this->nullableString($payload['office_address'] ?? null),
            'copyright_text' => $this->nullableString($payload['copyright_text'] ?? null),
            'default_seo_title' => $this->nullableString($payload['default_seo_title'] ?? null),
            'default_meta_description' => $this->nullableString($payload['default_meta_description'] ?? null),
            'notification_emails' => $this->parseNotificationEmails($payload['notification_emails'] ?? null),
            'lock_key' => 'default',
        ]);

        return $row->refresh();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function updateBranding(array $payload): WebsiteSetting
    {
        $row = $this->current();
        $name = trim((string) ($payload['website_name'] ?? ''));

        $next = [
            'logo_path' => $row->logo_path,
            'icon_path' => $row->icon_path,
            'favicon_path' => $row->favicon_path,
        ];
        $storedNew = [];
        $pendingDeletes = [];

        try {
            foreach (['logo' => 'logo_path', 'icon' => 'icon_path', 'favicon' => 'favicon_path'] as $field => $column) {
                $upload = $payload[$field] ?? null;
                $remove = (bool) ($payload['remove_'.$field] ?? false);

                if ($upload instanceof \Illuminate\Http\UploadedFile) {
                    $stored = $this->brandingAssets->store($upload, $field);
                    $storedNew[] = $stored;
                    if (is_string($row->{$column}) && $row->{$column} !== '') {
                        $pendingDeletes[] = $row->{$column};
                    }
                    $next[$column] = $stored;
                } elseif ($remove) {
                    if (is_string($row->{$column}) && $row->{$column} !== '') {
                        $pendingDeletes[] = $row->{$column};
                    }
                    $next[$column] = null;
                }
            }

            $row->update([
                'website_name' => $name,
                'logo_path' => $next['logo_path'],
                'icon_path' => $next['icon_path'],
                'favicon_path' => $next['favicon_path'],
            ]);
        } catch (\Throwable $exception) {
            foreach ($storedNew as $path) {
                $this->brandingAssets->delete($path);
            }

            throw $exception;
        }

        foreach ($pendingDeletes as $path) {
            $this->brandingAssets->delete($path);
        }

        return $row->refresh();
    }

    /**
     * @return array<string, string|null>
     */
    public function publicBranding(): array
    {
        return $this->current()->toPublicBrandingArray();
    }

    public function websiteName(): string
    {
        return $this->current()->websiteName();
    }

    /**
     * @return array<string, string>
     */
    public function publicSeo(): array
    {
        $row = $this->current();

        return [
            'site_name' => $row->websiteName(),
            'default_title' => $row->default_seo_title ?: $row->websiteName(),
            'default_description' => (string) ($row->default_meta_description ?? ''),
        ];
    }

    public function copyrightLine(): string
    {
        $row = $this->current();
        $template = $row->copyright_text ?: '© {year} '.$row->company_name.'. All rights reserved.';

        return str_replace('{year}', (string) now()->year, $template);
    }

    /**
     * @return array<string, string>
     */
    public function defaults(): array
    {
        $contact = PublicContent::fallbackContact();

        return [
            'company_name' => 'OurNear Talent',
            'website_name' => 'OurNear Talent',
            'office_phone' => $contact['phone'],
            'office_email' => $contact['email'],
            'website_url' => null,
            'office_address' => $contact['address'],
            'copyright_text' => '© {year} OurNear Talent. All rights reserved.',
            'default_seo_title' => 'OurNear Talent',
            'default_meta_description' => 'OurNear Talent supplies capable people, practical training, and project support so employers can deliver with confidence.',
            'notification_emails' => [],
            'lock_key' => 'default',
        ];
    }

    /**
     * @return list<string>
     */
    public function notificationRecipients(): array
    {
        return $this->parseNotificationEmails($this->current()->notification_emails);
    }

    /**
     * @return list<string>
     */
    public function parseNotificationEmails(mixed $value): array
    {
        if (is_array($value)) {
            $parts = $value;
        } else {
            $parts = preg_split('/[\s,;]+/', (string) $value) ?: [];
        }

        $emails = [];
        foreach ($parts as $part) {
            $email = strtolower(trim((string) $part));
            if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                continue;
            }
            $emails[] = $email;
        }

        return array_values(array_unique($emails));
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
