<?php

namespace App\Services;

use App\Models\WebsiteSetting;
use App\Support\ApplicationUrlResolver;
use Illuminate\Support\Facades\Schema;

class MailConfigurationService
{
    private static ?bool $mailColumnsAvailable = null;

    public function __construct(
        private WebsiteSettingService $websiteSettings,
        private ApplicationUrlResolver $applicationUrl,
    ) {}

    public function applyRuntimeConfiguration(): void
    {
        $this->applicationUrl->applyAtBoot();
        $this->websiteSettings->applyRuntimeMailIdentity();

        if (! $this->mailColumnsAvailable()) {
            return;
        }

        $row = $this->websiteSettings->current();

        if (! (bool) $row->mail_enabled) {
            return;
        }

        $mailer = $this->normalizedMailer($row->mail_mailer);
        if ($mailer !== null) {
            config(['mail.default' => $mailer]);
        }

        if ($mailer !== 'smtp') {
            return;
        }

        if (! $this->hasCompleteSmtpConfiguration($row)) {
            return;
        }

        $encryption = $this->normalizedEncryption($row->smtp_encryption);
        $scheme = match ($encryption) {
            'ssl', 'smtps' => 'smtps',
            default => null,
        };

        $password = $this->decryptedSmtpPassword($row);

        config([
            'mail.mailers.smtp.host' => trim((string) $row->smtp_host),
            'mail.mailers.smtp.port' => (int) $row->smtp_port,
            'mail.mailers.smtp.scheme' => $scheme,
            'mail.mailers.smtp.username' => $this->nullableTrim($row->smtp_username),
            'mail.mailers.smtp.password' => $password,
            'mail.mailers.smtp.timeout' => filled($row->smtp_timeout) ? (int) $row->smtp_timeout : 10,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function effectiveConfigurationSummary(): array
    {
        $row = $this->mailColumnsAvailable() ? $this->websiteSettings->current() : null;

        return [
            'mail_enabled' => (bool) ($row?->mail_enabled ?? false),
            'mail_mailer' => $this->normalizedMailer($row?->mail_mailer) ?? (string) config('mail.default'),
            'smtp_configured' => $row instanceof WebsiteSetting && $this->hasCompleteSmtpConfiguration($row),
            'smtp_password_configured' => $row instanceof WebsiteSetting && filled($row->smtp_password),
            'application_url' => $this->applicationUrl->effectiveRootUrl() ?? (string) config('app.url'),
            'from_address' => config('mail.from.address'),
            'from_name' => config('mail.from.name'),
            'uses_queue' => $row instanceof WebsiteSetting ? (bool) $row->mail_use_queue : true,
        ];
    }

    public function hasCompleteSmtpConfiguration(?WebsiteSetting $row): bool
    {
        if (! $row instanceof WebsiteSetting) {
            return false;
        }

        $host = trim((string) ($row->smtp_host ?? ''));
        $port = (int) ($row->smtp_port ?? 0);
        $username = trim((string) ($row->smtp_username ?? ''));

        if ($host === '' || $port <= 0 || $username === '') {
            return false;
        }

        if (! filled($row->smtp_password) && ! filled(config('mail.mailers.smtp.password'))) {
            return false;
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function updateFromAdmin(array $payload, WebsiteSetting $row): WebsiteSetting
    {
        abort_unless($this->mailColumnsAvailable(), 503);

        $attributes = [
            'mail_mailer' => $this->normalizedMailer($payload['mail_mailer'] ?? null) ?? 'smtp',
            'mail_enabled' => (bool) ($payload['mail_enabled'] ?? false),
            'smtp_host' => $this->nullableTrim($payload['smtp_host'] ?? null),
            'smtp_port' => isset($payload['smtp_port']) && $payload['smtp_port'] !== '' ? (int) $payload['smtp_port'] : null,
            'smtp_encryption' => $this->normalizedEncryption($payload['smtp_encryption'] ?? null),
            'smtp_username' => $this->nullableTrim($payload['smtp_username'] ?? null),
            'smtp_timeout' => isset($payload['smtp_timeout']) && $payload['smtp_timeout'] !== ''
                ? max(3, (int) $payload['smtp_timeout'])
                : null,
            'mail_use_queue' => (bool) ($payload['mail_use_queue'] ?? true),
        ];

        if ((bool) ($payload['clear_smtp_password'] ?? false)) {
            $attributes['smtp_password'] = null;
        } elseif (array_key_exists('smtp_password', $payload) && is_string($payload['smtp_password'])) {
            $password = trim($payload['smtp_password']);
            if ($password !== '') {
                $attributes['smtp_password'] = $password;
            }
        }

        $row->update($attributes);
        $fresh = $row->refresh();
        $this->applyRuntimeConfiguration();

        return $fresh;
    }

    public function recordDiagnostic(WebsiteSetting $row, string $operation, string $outcome, array $details = []): void
    {
        if (! Schema::hasColumn('website_settings', 'mail_last_diagnostic')) {
            return;
        }

        $sanitized = [];
        foreach ($details as $key => $value) {
            if (is_string($value) && $this->looksLikeSecret($key, $value)) {
                continue;
            }
            $sanitized[$key] = $value;
        }

        $row->forceFill([
            'mail_last_diagnostic' => [
                'operation' => $operation,
                'outcome' => $outcome,
                'at' => now()->toIso8601String(),
                'details' => $sanitized,
            ],
        ])->save();
    }

    private function mailColumnsAvailable(): bool
    {
        if (self::$mailColumnsAvailable !== null) {
            return self::$mailColumnsAvailable;
        }

        try {
            self::$mailColumnsAvailable = Schema::hasTable('website_settings')
                && Schema::hasColumn('website_settings', 'mail_enabled');
        } catch (\Throwable) {
            self::$mailColumnsAvailable = false;
        }

        return self::$mailColumnsAvailable;
    }

    private function normalizedMailer(mixed $value): ?string
    {
        $mailer = strtolower(trim((string) $value));
        if ($mailer === '') {
            return null;
        }

        $allowed = ['smtp', 'log', 'array', 'sendmail'];

        return in_array($mailer, $allowed, true) ? $mailer : null;
    }

    private function normalizedEncryption(mixed $value): ?string
    {
        $encryption = strtolower(trim((string) $value));
        if ($encryption === '' || $encryption === 'none') {
            return null;
        }

        return in_array($encryption, ['tls', 'ssl'], true) ? $encryption : null;
    }

    private function nullableTrim(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function decryptedSmtpPassword(WebsiteSetting $row): ?string
    {
        if (! filled($row->smtp_password)) {
            return null;
        }

        try {
            return $row->smtp_password;
        } catch (\Throwable) {
            return null;
        }
    }

    private function looksLikeSecret(string $key, string $value): bool
    {
        $key = strtolower($key);
        if (str_contains($key, 'password') || str_contains($key, 'secret') || str_contains($key, 'token')) {
            return true;
        }

        return strlen($value) > 40 && preg_match('/^[A-Za-z0-9+\/=_-]+$/', $value) === 1;
    }
}
