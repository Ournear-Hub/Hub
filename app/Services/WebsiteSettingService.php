<?php

namespace App\Services;

use App\Models\MediaAsset;
use App\Models\WebsiteSetting;
use App\Support\PublicContent;
use Illuminate\Validation\ValidationException;
use App\Support\WhatsAppNumber;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Mime\Address as SymfonyAddress;
use Symfony\Component\Mime\Email;

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
        $attributes = [
            'company_name' => trim((string) $payload['company_name']),
            'office_phone' => $this->nullableString($payload['office_phone'] ?? null),
            'office_email' => $this->normalizedEmail($payload['office_email'] ?? null),
            'website_url' => $this->nullableString($payload['website_url'] ?? null),
            'office_address' => $this->nullableString($payload['office_address'] ?? null),
            'copyright_text' => $this->nullableString($payload['copyright_text'] ?? null),
            'default_seo_title' => $this->nullableString($payload['default_seo_title'] ?? null),
            'default_meta_description' => $this->nullableString($payload['default_meta_description'] ?? null),
            'notification_emails' => $this->parseNotificationEmails($payload['notification_emails'] ?? null),
            'lock_key' => 'default',
        ];

        if (Schema::hasColumn('website_settings', 'support_email')) {
            $attributes['support_email'] = $this->normalizedEmail($payload['support_email'] ?? null);
            $attributes['recruitment_email'] = $this->normalizedEmail($payload['recruitment_email'] ?? null);
            $attributes['reply_to_email'] = $this->normalizedEmail($payload['reply_to_email'] ?? null);
        }

        if (Schema::hasColumn('website_settings', 'primary_whatsapp')) {
            $attributes['primary_whatsapp'] = $this->normalizedWhatsapp($payload['primary_whatsapp'] ?? null);
            $attributes['recruitment_whatsapp'] = $this->normalizedWhatsapp($payload['recruitment_whatsapp'] ?? null);
        }

        $row->update($attributes);
        $fresh = $row->refresh();
        $this->applyRuntimeMailIdentity();

        return $fresh;
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
            'wordmark_path' => Schema::hasColumn('website_settings', 'wordmark_path') ? $row->wordmark_path : null,
            'icon_path' => $row->icon_path,
            'favicon_path' => $row->favicon_path,
        ];
        $storedNew = [];
        $pendingDeletes = [];

        try {
            $assetFields = [
                'logo' => 'logo_path',
                'wordmark' => 'wordmark_path',
                'icon' => 'icon_path',
                'favicon' => 'favicon_path',
            ];

            if (! Schema::hasColumn('website_settings', 'wordmark_path')) {
                unset($assetFields['wordmark']);
            }

            $mediaFieldMap = [
                'logo_media_id' => ['column' => 'logo_path', 'field' => 'logo'],
                'wordmark_media_id' => ['column' => 'wordmark_path', 'field' => 'wordmark'],
            ];

            foreach ($mediaFieldMap as $payloadKey => $meta) {
                if (! Schema::hasColumn('website_settings', $meta['column'])) {
                    continue;
                }

                $mediaId = (int) ($payload[$payloadKey] ?? 0);
                $field = $meta['field'];
                $column = $meta['column'];
                $upload = $payload[$field] ?? null;
                $remove = (bool) ($payload['remove_'.$field] ?? false);

                if ($mediaId <= 0 || $remove || $upload instanceof \Illuminate\Http\UploadedFile) {
                    continue;
                }

                $asset = MediaAsset::query()->find($mediaId);
                if ($asset === null) {
                    throw ValidationException::withMessages([
                        $payloadKey => 'The selected media asset could not be found.',
                    ]);
                }

                $stored = $this->brandingAssets->copyFromMediaAsset($asset, $field);
                $storedNew[] = $stored;
                if (is_string($row->{$column}) && $row->{$column} !== '') {
                    $pendingDeletes[] = $row->{$column};
                }
                $next[$column] = $stored;
            }

            foreach ($assetFields as $field => $column) {
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

            $attributes = [
                'website_name' => $name,
                'logo_path' => $next['logo_path'],
                'icon_path' => $next['icon_path'],
                'favicon_path' => $next['favicon_path'],
            ];

            if (Schema::hasColumn('website_settings', 'wordmark_path')) {
                $attributes['wordmark_path'] = $next['wordmark_path'];
            }

            $row->update($attributes);

            if (($payload['remove_auth_splash'] ?? false) === true) {
                $this->brandingAssets->removeAuthSplashVideo();
            } elseif ($payload['auth_splash'] ?? null instanceof \Illuminate\Http\UploadedFile) {
                $this->brandingAssets->storeAuthSplashVideo($payload['auth_splash']);
            }
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
        $base = $this->current()->toPublicBrandingArray();
        $bundledSplash = public_path('brand/ournear-splash.mp4');
        $managedSplash = $this->brandingAssets->authSplashPublicUrl();

        return [
            ...$base,
            'header_logo_url' => $base['logo_url'],
            'footer_logo_url' => $base['wordmark_url'],
            'hire_wordmark_url' => $base['wordmark_url'] ?? asset('brand/ournear-hire-wordmark.jpeg'),
            'auth_symbol_url' => $base['icon_url'] ?? asset('brand/ournear-symbol.jpeg'),
            'auth_wordmark_url' => $base['wordmark_url'] ?? $base['logo_url'] ?? asset('brand/ournear-hire-wordmark.jpeg'),
            'auth_splash_video_url' => $managedSplash ?? (is_file($bundledSplash) ? asset('brand/ournear-splash.mp4') : null),
            'auth_splash_poster_url' => asset('brand/ournear-symbol.jpeg'),
        ];
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

        $defaults = [
            'company_name' => 'OurNear',
            'website_name' => 'OurNear',
            'office_phone' => $contact['phone'],
            'office_email' => null,
            'website_url' => null,
            'office_address' => $contact['address'],
            'copyright_text' => '© {year} OurNear. All rights reserved.',
            'default_seo_title' => 'OurNear',
            'default_meta_description' => 'OurNear connects job seekers and employers through OurNear Jobs, OurNear Hire, Talent Pool, and Staffing on one platform.',
            'notification_emails' => [],
            'lock_key' => 'default',
        ];

        if (Schema::hasColumn('website_settings', 'support_email')) {
            $defaults['support_email'] = null;
            $defaults['recruitment_email'] = null;
            $defaults['reply_to_email'] = null;
        }

        if (Schema::hasColumn('website_settings', 'primary_whatsapp')) {
            $defaults['primary_whatsapp'] = null;
            $defaults['recruitment_whatsapp'] = null;
        }

        return $defaults;
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

    public function primaryEmail(): ?string
    {
        return $this->usableEmail($this->current()->office_email);
    }

    public function supportEmail(): ?string
    {
        return $this->usableEmail($this->current()->support_email ?? null);
    }

    public function recruitmentEmail(): ?string
    {
        return $this->usableEmail($this->current()->recruitment_email ?? null);
    }

    public function replyToEmail(): ?string
    {
        return $this->usableEmail($this->current()->reply_to_email ?? null);
    }

    public function mailFromAddress(): ?string
    {
        return $this->primaryEmail();
    }

    public function mailFromName(): string
    {
        $name = trim((string) $this->current()->company_name);

        return $name !== '' ? $name : (string) config('mail.from.name', config('app.name'));
    }

    public function mailReplyToAddress(string $purpose = 'system'): ?string
    {
        $purposeReply = match ($purpose) {
            'recruitment' => $this->recruitmentEmail(),
            'support' => $this->supportEmail(),
            default => null,
        };

        return $purposeReply
            ?? $this->replyToEmail()
            ?? $this->primaryEmail();
    }

    public function mailEnvelope(string $subject, string $purpose = 'system'): Envelope
    {
        $from = $this->mailFromAddress();
        $name = $this->mailFromName();
        $reply = $this->mailReplyToAddress($purpose);

        $arguments = ['subject' => $subject];
        if (is_string($from) && $from !== '') {
            $arguments['from'] = new Address($from, $name);
        }
        if (is_string($reply) && $reply !== '') {
            $arguments['replyTo'] = [new Address($reply, $name)];
        }

        return new Envelope(...$arguments);
    }

    public function applyRuntimeMailIdentity(): void
    {
        try {
            if (! Schema::hasTable('website_settings')) {
                return;
            }
        } catch (\Throwable) {
            return;
        }

        $from = $this->mailFromAddress();
        if (! is_string($from) || $from === '') {
            return;
        }

        config([
            'mail.from.address' => $from,
            'mail.from.name' => $this->mailFromName(),
        ]);
    }

    public function applyToSymfonyMessage(Email $message): void
    {
        $from = $this->mailFromAddress();
        $name = $this->mailFromName();
        if (is_string($from) && $from !== '') {
            $message->from(new SymfonyAddress($from, $name));
        }

        if ($message->getReplyTo() === []) {
            $reply = $this->mailReplyToAddress('system');
            if (is_string($reply) && $reply !== '') {
                $message->replyTo(new SymfonyAddress($reply, $name));
            }
        }
    }

    public function primaryWhatsapp(): ?string
    {
        return $this->nullableString($this->current()->primary_whatsapp ?? null);
    }

    public function recruitmentWhatsapp(): ?string
    {
        return $this->nullableString($this->current()->recruitment_whatsapp ?? null);
    }

    public function primaryWhatsappUrl(): ?string
    {
        return WhatsAppNumber::waMeUrl($this->primaryWhatsapp());
    }

    public function recruitmentWhatsappUrl(): ?string
    {
        return WhatsAppNumber::waMeUrl($this->recruitmentWhatsapp());
    }

    public function primaryWhatsappDisplay(): ?string
    {
        return WhatsAppNumber::display($this->primaryWhatsapp());
    }

    public function recruitmentWhatsappDisplay(): ?string
    {
        return WhatsAppNumber::display($this->recruitmentWhatsapp());
    }

    /**
     * @return array<string, string|null>
     */
    public function publicCommunication(): array
    {
        return [
            'email' => $this->primaryEmail() ?? '',
            'support_email' => $this->supportEmail(),
            'recruitment_email' => $this->recruitmentEmail(),
            'phone' => $this->nullableString($this->current()->office_phone) ?? '',
            'whatsapp' => $this->primaryWhatsappDisplay() ?? '',
            'whatsapp_url' => $this->primaryWhatsappUrl(),
            'recruitment_whatsapp' => $this->recruitmentWhatsappDisplay(),
            'recruitment_whatsapp_url' => $this->recruitmentWhatsappUrl(),
        ];
    }

    private function normalizedWhatsapp(mixed $value): ?string
    {
        $normalized = WhatsAppNumber::normalize(is_string($value) ? $value : null);
        if ($normalized === false) {
            return null;
        }

        return $normalized;
    }

    private function usableEmail(mixed $value): ?string
    {
        $email = $this->normalizedEmail($value);
        if ($email === null) {
            return null;
        }

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    private function normalizedEmail(mixed $value): ?string
    {
        $email = $this->nullableString($value);
        if ($email === null) {
            return null;
        }

        $email = strtolower($email);
        if (str_contains($email, 'to be published')) {
            return null;
        }

        return $email;
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
