<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class WebsiteSetting extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_name',
        'website_name',
        'logo_path',
        'wordmark_path',
        'brand_mark',
        'icon_path',
        'favicon_path',
        'office_phone',
        'primary_whatsapp',
        'recruitment_whatsapp',
        'office_email',
        'support_email',
        'recruitment_email',
        'reply_to_email',
        'website_url',
        'office_address',
        'copyright_text',
        'default_seo_title',
        'default_meta_description',
        'notification_emails',
        'mail_mailer',
        'mail_enabled',
        'smtp_host',
        'smtp_port',
        'smtp_encryption',
        'smtp_username',
        'smtp_password',
        'smtp_timeout',
        'mail_use_queue',
        'mail_last_diagnostic',
        'lock_key',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'notification_emails' => 'array',
            'mail_enabled' => 'boolean',
            'mail_use_queue' => 'boolean',
            'smtp_password' => 'encrypted',
            'mail_last_diagnostic' => 'array',
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
            'primary_whatsapp' => $this->primary_whatsapp ?? '',
            'recruitment_whatsapp' => $this->recruitment_whatsapp ?? '',
            'office_email' => $this->office_email ?? '',
            'support_email' => $this->support_email ?? '',
            'recruitment_email' => $this->recruitment_email ?? '',
            'reply_to_email' => $this->reply_to_email ?? '',
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
    /**
     * @return array<string, mixed>
     */
    public function toAdminMailArray(): array
    {
        $diagnostic = is_array($this->mail_last_diagnostic) ? $this->mail_last_diagnostic : null;

        return [
            'mail_mailer' => $this->mail_mailer ?: 'smtp',
            'mail_enabled' => (bool) ($this->mail_enabled ?? false),
            'smtp_host' => $this->smtp_host ?? '',
            'smtp_port' => $this->smtp_port ?? '',
            'smtp_encryption' => $this->smtp_encryption ?? 'tls',
            'smtp_username' => $this->smtp_username ?? '',
            'smtp_password_configured' => filled($this->smtp_password),
            'smtp_timeout' => $this->smtp_timeout ?? '',
            'mail_use_queue' => (bool) ($this->mail_use_queue ?? true),
            'last_diagnostic' => $diagnostic,
            'sender_from_website_settings' => true,
        ];
    }

    public function toAdminBrandingArray(): array
    {
        $assets = app(\App\Services\BrandingAssetService::class);

        $mark = Schema::hasColumn('website_settings', 'brand_mark')
            ? trim((string) $this->brand_mark)
            : '';

        if ($mark === '^') {
            $mark = '';
        }

        $bundledSplash = public_path('brand/ournear-splash.mp4');

        return [
            'website_name' => $this->websiteName(),
            'brand_mark' => $mark,
            'logo_url' => $assets->publicUrl($this->logo_path),
            'logo_filename' => $this->storedBrandingFilename($this->logo_path),
            'wordmark_url' => Schema::hasColumn('website_settings', 'wordmark_path')
                ? $assets->publicUrl($this->wordmark_path)
                : null,
            'wordmark_filename' => Schema::hasColumn('website_settings', 'wordmark_path')
                ? $this->storedBrandingFilename($this->wordmark_path)
                : null,
            'icon_url' => $assets->publicUrl($this->icon_path),
            'icon_filename' => $this->storedBrandingFilename($this->icon_path),
            'favicon_url' => $assets->publicUrl($this->favicon_path),
            'favicon_filename' => $this->storedBrandingFilename($this->favicon_path),
            'auth_splash_video_url' => $assets->authSplashPublicUrl()
                ?? (is_file($bundledSplash) ? asset('brand/ournear-splash.mp4') : null),
        ];
    }

    private function storedBrandingFilename(?string $path): ?string
    {
        if (! is_string($path) || $path === '') {
            return null;
        }

        $name = basename($path);

        return $name !== '' ? $name : null;
    }

    /**
     * @return array{website_name: string, logo_url: string|null, icon_url: string|null, favicon_url: string|null}
     */
    public function toPublicBrandingArray(): array
    {
        return $this->toAdminBrandingArray();
    }
}
