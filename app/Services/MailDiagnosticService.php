<?php

namespace App\Services;

use App\Mail\AdminMailDiagnosticTestMail;
use App\Models\User;
use App\Models\WebsiteSetting;
use App\Notifications\VerifyAccountEmailNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Transport;

class MailDiagnosticService
{
    public function __construct(
        private MailConfigurationService $mailConfiguration,
        private WebsiteSettingService $websiteSettings,
    ) {}

    /**
     * @return array{valid: bool, errors: list<string>}
     */
    public function validateConfiguration(): array
    {
        $row = $this->websiteSettings->current();
        $errors = [];

        if (! (bool) $row->mail_enabled) {
            $errors[] = 'Admin mail delivery is disabled. Enable it or rely on environment configuration.';
        }

        $mailer = strtolower(trim((string) ($row->mail_mailer ?? 'smtp')));
        if ($mailer === 'smtp') {
            if (trim((string) ($row->smtp_host ?? '')) === '') {
                $errors[] = 'SMTP host is required.';
            }
            if ((int) ($row->smtp_port ?? 0) <= 0) {
                $errors[] = 'SMTP port is required.';
            }
            if (trim((string) ($row->smtp_username ?? '')) === '') {
                $errors[] = 'SMTP username is required.';
            }
            if (! filled($row->smtp_password) && ! filled(config('mail.mailers.smtp.password'))) {
                $errors[] = 'SMTP password is not configured.';
            }
        }

        if (trim((string) ($row->office_email ?? '')) === '') {
            $errors[] = 'Primary company email (From address) is not configured in Website settings.';
        }

        $appUrl = app(\App\Support\ApplicationUrlResolver::class)->effectiveRootUrl();
        if ($appUrl === null) {
            $errors[] = 'Public application URL is missing. Set Website URL or APP_URL to a valid http(s) address.';
        }

        return [
            'valid' => $errors === [],
            'errors' => $errors,
        ];
    }

    /**
     * @return array{outcome: string, message: string}
     */
    public function testSmtpConnection(): array
    {
        $this->mailConfiguration->applyRuntimeConfiguration();
        $row = $this->websiteSettings->current();

        if (strtolower(trim((string) ($row->mail_mailer ?? 'smtp'))) !== 'smtp') {
            return [
                'outcome' => 'skipped',
                'message' => 'Connection test applies to SMTP mailer only.',
            ];
        }

        if (! $this->mailConfiguration->hasCompleteSmtpConfiguration($row)) {
            return [
                'outcome' => 'configuration_incomplete',
                'message' => 'SMTP settings are incomplete.',
            ];
        }

        $encryption = strtolower(trim((string) ($row->smtp_encryption ?? '')));
        $scheme = in_array($encryption, ['ssl', 'smtps'], true) ? 'smtps' : 'smtp';
        $user = rawurlencode((string) $row->smtp_username);
        $pass = rawurlencode((string) $row->smtp_password);
        $host = trim((string) $row->smtp_host);
        $port = (int) $row->smtp_port;
        $timeout = filled($row->smtp_timeout) ? (int) $row->smtp_timeout : 15;

        $dsn = sprintf('%s://%s:%s@%s:%d', $scheme, $user, $pass, $host, $port);

        try {
            $transport = Transport::fromDsn($dsn);
            if (method_exists($transport, 'start')) {
                $transport->start();
            }
            if (method_exists($transport, 'stop')) {
                $transport->stop();
            }

            $this->mailConfiguration->recordDiagnostic($row, 'connection_test', 'connection_ok', [
                'host' => $host,
                'port' => $port,
            ]);

            return [
                'outcome' => 'connection_ok',
                'message' => 'SMTP transport accepted the connection.',
            ];
        } catch (TransportExceptionInterface $exception) {
            $this->mailConfiguration->recordDiagnostic($row, 'connection_test', 'connection_failed', [
                'host' => $host,
                'port' => $port,
                'error' => $this->sanitizeError($exception->getMessage()),
            ]);

            return [
                'outcome' => 'connection_failed',
                'message' => $this->sanitizeError($exception->getMessage()),
            ];
        } catch (\Throwable $exception) {
            $this->mailConfiguration->recordDiagnostic($row, 'connection_test', 'connection_failed', [
                'error' => $this->sanitizeError($exception->getMessage()),
            ]);

            return [
                'outcome' => 'connection_failed',
                'message' => $this->sanitizeError($exception->getMessage()),
            ];
        }
    }

    /**
     * @return array{outcome: string, message: string}
     */
    public function sendTestEmail(User $admin, string $recipient): array
    {
        $validator = Validator::make(['recipient' => $recipient], [
            'recipient' => ['required', 'email', 'max:255'],
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        $this->mailConfiguration->applyRuntimeConfiguration();
        $row = $this->websiteSettings->current();

        try {
            $mailable = new AdminMailDiagnosticTestMail($admin);
            if ((bool) $row->mail_use_queue) {
                Mail::to($recipient)->queue($mailable);
                $outcome = 'queued';
                $message = 'Diagnostic email queued for delivery.';
            } else {
                Mail::to($recipient)->send($mailable);
                $outcome = 'transport_accepted';
                $message = 'Diagnostic email handed to the mail transport.';
            }

            $this->mailConfiguration->recordDiagnostic($row, 'send_test', $outcome, [
                'recipient' => $recipient,
            ]);

            return ['outcome' => $outcome, 'message' => $message];
        } catch (\Throwable $exception) {
            $this->mailConfiguration->recordDiagnostic($row, 'send_test', 'send_failed', [
                'recipient' => $recipient,
                'error' => $this->sanitizeError($exception->getMessage()),
            ]);

            throw ValidationException::withMessages([
                'recipient' => $this->sanitizeError($exception->getMessage()),
            ]);
        }
    }

    /**
     * @return array{
     *     verify_url: string,
     *     html: string,
     *     text: string,
     *     link_present: bool,
     *     anchor_href_matches: bool
     * }
     */
    public function previewVerificationEmail(User $previewUser): array
    {
        $this->mailConfiguration->applyRuntimeConfiguration();

        $notification = new VerifyAccountEmailNotification;
        $mail = $notification->toMail($previewUser);
        $url = $notification->previewVerificationUrl($previewUser);

        $rendered = $mail->render();
        $html = is_string($rendered) ? $rendered : '';
        $text = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $html));

        $linkPresent = str_contains($html, 'href="'.htmlspecialchars($url, ENT_QUOTES, 'UTF-8').'"')
            || str_contains($html, "href=\"{$url}\"");
        $anchorMatches = str_contains($html, $url);

        return [
            'verify_url' => $url,
            'html' => $html,
            'text' => $text,
            'link_present' => $linkPresent || $anchorMatches,
            'anchor_href_matches' => $anchorMatches,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function externalPrerequisites(): array
    {
        $row = $this->websiteSettings->current();
        $appUrl = app(\App\Support\ApplicationUrlResolver::class)->effectiveRootUrl();

        return [
            'smtp_credentials' => [
                'label' => 'SMTP account credentials',
                'status' => filled($row->smtp_password) || filled(config('mail.mailers.smtp.password'))
                    ? 'configured'
                    : 'missing',
                'instruction' => 'Create SMTP credentials with your provider (for Brevo: smtp-relay.brevo.com, port 587, TLS).',
            ],
            'sender_identity' => [
                'label' => 'Sender / From address',
                'status' => filled($row->office_email) ? 'configured' : 'missing',
                'instruction' => 'Set Primary company email under Website settings and verify the domain with your provider.',
            ],
            'application_url' => [
                'label' => 'Public HTTPS application URL',
                'status' => is_string($appUrl) && str_starts_with($appUrl, 'https://') ? 'configured' : 'action_required',
                'instruction' => 'Set Website URL to the public https address used in verification links.',
            ],
            'dns_spf_dkim' => [
                'label' => 'SPF / DKIM / DMARC',
                'status' => 'external',
                'instruction' => 'Configure DNS records in your domain registrar; the admin panel cannot verify DNS automatically.',
            ],
            'inbox_delivery' => [
                'label' => 'Inbox delivery evidence',
                'status' => 'not_verified',
                'instruction' => 'Use Send test email to a mailbox you control; connection success does not prove inbox placement.',
            ],
        ];
    }

    private function sanitizeError(string $message): string
    {
        $message = preg_replace('/password[=\\s][^\\s]+/i', 'password=[redacted]', $message) ?? $message;

        return mb_substr(trim($message), 0, 500);
    }
}
