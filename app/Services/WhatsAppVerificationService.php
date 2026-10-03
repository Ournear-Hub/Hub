<?php

namespace App\Services;

use App\Enums\UserActivityType;
use App\Enums\VerificationChannel;
use App\Enums\WhatsAppBusinessIdentity;
use App\Enums\WhatsAppMessagePurpose;
use App\Exceptions\WhatsAppProviderException;
use App\Models\User;
use App\Models\VerificationChallenge;
use App\Support\UserWhatsAppNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WhatsAppVerificationService
{
    public function __construct(
        private WhatsAppMessagingService $whatsapp,
        private UserActivityService $activities,
        private WebsiteSettingService $settings,
    ) {}

    /**
     * @return array{retry_after: int}
     */
    public function send(User $user, Request $request): array
    {
        abort_unless($user->isAccountMember(), 403);

        $destination = $this->requireNormalizedNumber($user);
        $this->assertNotCompanyIdentity($destination);
        $this->assertResendAllowed($user, $destination);

        $templateName = trim((string) config('whatsapp.otp.template_name', ''));
        $language = trim((string) config('whatsapp.otp.template_language', 'en')) ?: 'en';
        if ($templateName === '') {
            throw ValidationException::withMessages([
                'whatsapp_number' => 'WhatsApp verification is temporarily unavailable.',
            ]);
        }

        $code = $this->generateCode();
        $ttl = max(1, (int) config('otp.ttl_minutes', 10));
        $maxAttempts = max(1, (int) config('otp.max_attempts', 5));
        $includeCopyButton = (bool) config('whatsapp.otp.include_copy_button', true);

        $challenge = DB::transaction(function () use ($user, $destination, $code, $ttl, $maxAttempts, $request): VerificationChallenge {
            $this->invalidateActive($user, $destination);

            return VerificationChallenge::query()->create([
                'user_id' => $user->id,
                'channel' => VerificationChannel::Whatsapp,
                'destination' => $destination,
                'code_hash' => $this->hashCode($code),
                'expires_at' => now()->addMinutes($ttl),
                'attempts' => 0,
                'max_attempts' => $maxAttempts,
                'requested_ip' => $request->ip(),
            ]);
        });

        try {
            $this->whatsapp->sendTemplate(
                recipient: $destination,
                templateName: $templateName,
                language: $language,
                bodyParameters: [$code],
                purpose: WhatsAppMessagePurpose::WhatsappVerification,
                identity: WhatsAppBusinessIdentity::Primary,
                urlButtonText: $includeCopyButton ? $code : null,
            );
        } catch (WhatsAppProviderException $exception) {
            $challenge->delete();

            throw ValidationException::withMessages([
                'whatsapp_number' => $exception->error === \App\Enums\WhatsAppProviderError::NotConfigured
                    ? 'WhatsApp verification is temporarily unavailable.'
                    : 'The WhatsApp verification code could not be sent. Please try again later.',
            ]);
        } catch (ValidationException $exception) {
            $challenge->delete();

            throw ValidationException::withMessages([
                'whatsapp_number' => 'WhatsApp verification is temporarily unavailable.',
            ]);
        } catch (\Throwable $exception) {
            $challenge->delete();
            report($exception);

            throw ValidationException::withMessages([
                'whatsapp_number' => 'The WhatsApp verification code could not be sent. Please try again later.',
            ]);
        }

        unset($code);

        $this->activities->record(
            $user,
            UserActivityType::SecurityEvent,
            'WhatsApp verification was requested.',
            $user,
        );

        return ['retry_after' => max(1, (int) config('otp.resend_seconds', 60))];
    }

    public function verify(User $user, string $otp, Request $request): void
    {
        abort_unless($user->isAccountMember(), 403);

        $destination = $this->requireNormalizedNumber($user);
        $otp = trim($otp);

        if (! preg_match('/^\d{6}$/', $otp)) {
            throw ValidationException::withMessages([
                'otp' => 'Enter the 6-digit verification code.',
            ]);
        }

        $challenge = $this->activeChallenge($user, $destination);

        if ($challenge === null || $challenge->isExpired() || $challenge->isUsed() || $challenge->hasExhaustedAttempts()) {
            throw ValidationException::withMessages([
                'otp' => 'That verification code is incorrect or is no longer valid. Request a new code.',
            ]);
        }

        $challenge->increment('attempts');
        $challenge->refresh();

        if (! hash_equals($challenge->code_hash, $this->hashCode($otp))) {
            throw ValidationException::withMessages([
                'otp' => $challenge->attempts >= $challenge->max_attempts
                    ? 'Too many incorrect attempts. Request a new code.'
                    : 'That verification code is incorrect or is no longer valid. Request a new code.',
            ]);
        }

        $challenge->forceFill([
            'used_at' => now(),
            'verified_ip' => $request->ip(),
        ])->save();

        $user->forceFill([
            'whatsapp_number' => $destination,
            'whatsapp_verified_at' => now(),
        ])->save();

        $this->activities->record(
            $user,
            UserActivityType::WhatsAppVerified,
            'WhatsApp number was verified.',
            $user,
        );
    }

    public function retryAfterSeconds(User $user): int
    {
        $destination = UserWhatsAppNumber::normalize($user->whatsapp_number);
        if (! is_string($destination)) {
            return 0;
        }

        $latest = VerificationChallenge::query()
            ->where('user_id', $user->id)
            ->where('channel', VerificationChannel::Whatsapp)
            ->where('destination', $destination)
            ->latest('id')
            ->first();

        if (! $latest) {
            return 0;
        }

        $wait = max(1, (int) config('otp.resend_seconds', 60));
        $availableAt = $latest->created_at?->copy()->addSeconds($wait);
        if ($availableAt === null || $availableAt->isPast()) {
            return 0;
        }

        return max(0, $availableAt->getTimestamp() - now()->getTimestamp());
    }

    private function requireNormalizedNumber(User $user): string
    {
        $normalized = UserWhatsAppNumber::normalize($user->whatsapp_number);
        if (! is_string($normalized)) {
            throw ValidationException::withMessages([
                'whatsapp_number' => filled($user->whatsapp_number)
                    ? 'Enter a valid Indian WhatsApp number before requesting a verification code.'
                    : 'Save a WhatsApp number in Account settings before requesting a verification code.',
            ]);
        }

        return $normalized;
    }

    private function assertNotCompanyIdentity(string $e164): void
    {
        $company = [
            $this->settings->primaryWhatsapp(),
            $this->settings->recruitmentWhatsapp(),
        ];

        foreach ($company as $number) {
            if (is_string($number) && $number === $e164) {
                throw ValidationException::withMessages([
                    'whatsapp_number' => 'That number is a company WhatsApp identity and cannot be verified as a personal account number.',
                ]);
            }
        }
    }

    private function assertResendAllowed(User $user, string $destination): void
    {
        $wait = $this->retryAfterSeconds($user);
        if ($wait > 0) {
            throw ValidationException::withMessages([
                'whatsapp_number' => 'Please wait '.$wait.' seconds before requesting another code.',
            ]);
        }
    }

    private function invalidateActive(User $user, string $destination): void
    {
        VerificationChallenge::query()
            ->where('user_id', $user->id)
            ->where('channel', VerificationChannel::Whatsapp)
            ->where('destination', $destination)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->update(['expires_at' => now()]);
    }

    private function activeChallenge(User $user, string $destination): ?VerificationChallenge
    {
        return VerificationChallenge::query()
            ->where('user_id', $user->id)
            ->where('channel', VerificationChannel::Whatsapp)
            ->where('destination', $destination)
            ->whereNull('used_at')
            ->orderByDesc('id')
            ->first();
    }

    private function generateCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    private function hashCode(string $code): string
    {
        return hash_hmac('sha256', $code, (string) config('app.key'));
    }
}
