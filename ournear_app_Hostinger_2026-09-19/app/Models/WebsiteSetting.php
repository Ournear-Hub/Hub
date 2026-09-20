<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebsiteSetting extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_name',
        'website_name',
        'logo_path',
        'icon_path',
        'favicon_path',
        'office_phone',
        'office_email',
        'website_url',
        'office_address',
        'copyright_text',
        'default_seo_title',
        'default_meta_description',
        'notification_emails',
        'lock_key',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'notification_emails' => 'array',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminArray(): array
    {
        $emails = is_array($this->notification_emails) ? $this->notification_emails : [];

        return [
            'company_name' => $this->company_name,
            'office_phone' => $this->office_phone ?? '',
            'office_email' => $this->office_email ?? '',
            'website_url' => $this->website_url ?? '',
            'office_address' => $this->office_address ?? '',
            'copyright_text' => $this->copyright_text ?? '',
            'default_seo_title' => $this->default_seo_title ?? '',
            'default_meta_description' => $this->default_meta_description ?? '',
            'notification_emails' => implode("\n", $emails),
        ];
    }

    public function websiteName(): string
{
    return trim((string) ($this->website_name ?? ''));
}

    /**
     * @return array<string, mixed>
     */
    public function toAdminBrandingArray(): array
    {
        $assets = app(\App\Services\BrandingAssetService::class);

        return [
            'website_name' => $this->websiteName(),
            'logo_url' => $assets->publicUrl($this->logo_path),
            'icon_url' => $assets->publicUrl($this->icon_path),
            'favicon_url' => $assets->publicUrl($this->favicon_path),
        ];
    }

    /**
     * @return array{website_name: string, logo_url: string|null, icon_url: string|null, favicon_url: string|null}
     */
    public function toPublicBrandingArray(): array
    {
        return $this->toAdminBrandingArray();
    }
}
