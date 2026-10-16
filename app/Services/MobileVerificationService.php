<?php

namespace App\Services;

use App\Contracts\SmsOtpGateway;
use App\Enums\UserActivityType;
use App\Enums\VerificationChannel;
use App\Exceptions\SmsOtpNotConfiguredException;
use App\Models\User;
use App\Models\VerificationChallenge;
use App\Support\MobileNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MobileVerificationService
{
    public function __construct(
        private SmsOtpGateway $sms,
        private UserActivityService $activities,
    ) {}

    /**
     * @return array{retry_after: int}
     */
    public function send(User $user, Request $request): array
    {
        abort_unless($user->isAccountMember(), 403);

        $destination = $this->requireNormalizedMobile($user);
        $this->assertResendAllowed($user, $destination);

        $code = $this->generateCode();
        $ttl = max(1, (int) config('otp.ttl_minutes', 10));
        $maxAttempts = max(1, (int) config('otp.max_attempts', 5));

        $challenge = DB::transaction(function () use ($user, $destination, $code, $ttl, $maxAttempts, $request): VerificationChallenge {
            $this->invalidateActive($user, $destination);

            return VerificationChallenge::query()->create([
                'user_id' => $user->id,
                'channel' => VerificationChannel::Mobile,
                'destination' => $destination,
                'code_hash' => $this->hashCode($code),
                'expires_at' => now()->addMinutes($ttl),
                'attempts' => 0,
                'max_attempts' => $maxAttempts,
                'requested_ip' => $request->ip(),
            ]);
        });

        try {
            $this->sms->send($destination, $code);
        } catch (SmsOtpNotConfiguredException $exception) {
            $challenge->delete();

            throw ValidationException::withMessages([
                'mobile' => $exception->getMessage(),
            ]);
        } catch (\Throwable $exception) {
            $challenge->delete();

            report($exception);

            throw ValidationException::withMessages([
                'mobile' => 'The verification code could not be sent. Please try again later.',
            ]);
        }

        unset($code);

        $this->activities->record(
            $user,
            UserActivityType::SecurityEvent,
            'Mobile verification was requested.',
            $user,
        );

        return ['retry_after' => max(1, (int) config('otp.resend_seconds', 60))];
    }

    public function verify(User $user, string $otp, Request $request): void
    {
        abort_unless($user->isAccountMember(), 403);

        $destination = $this->requireNormalizedMobile($user);
        $otp = trim($otp);

        if (! preg_match('/^\d{6}$/', $otp)) {
            throw ValidationException::withMessages([
                'otp' => 'Enter the 6-digit verification code.',
            ]);
        }

        $challenge = $this->activeChallenge($user, $destination);

        if ($challenge === null) {
            throw ValidationException::withMessages([
                'otp' => 'No active mobile verification code was found. Request a new code.',
            ]);
        }

        if ($challenge->isExpired() || $challenge->isUsed()) {
            throw ValidationException::withMessages([
                'otp' => 'That verification code is no longer valid. Request a new code.',
            ]);
        }

        if ($challenge->hasExhaustedAttempts()) {
            throw ValidationException::withMessages([
                'otp' => 'Too many incorrect attempts. Request a new code.',
            ]);
        }

        $challenge->increment('attempts');
        $challenge->refresh();

        if (! hash_equals($challenge->code_hash, $this->hashCode($otp))) {
            $remaining = max(0, $challenge->max_attempts - $challenge->attempts);

            throw ValidationException::withMessages([
                'otp' => $remaining === 0
                    ? 'Too many incorrect attempts. Request a new code.'
                    : 'That verification code is incorrect.',
            ]);
        }

        $challenge->forceFill([
            'used_at' => now(),
            'verified_ip' => $request->ip(),
        ])->save();

        $user->forceFill([
            'mobile' => $destination,
            'mobile_verified_at' => now(),
        ])->save();

        $this->activities->record(
            $user,
            UserActivityType::MobileVerified,
            'Mobile number was verified.',
            $user,
        );

        app(EmployerPostingEligibilityService::class)->syncPublishingBenefits($user->fresh());
    }

    public function retryAfterSeconds(User $user): int
    {
        $destination = MobileNumber::normalize($user->mobile);
        if (! is_string($destination)) {
            return 0;
        }

        $latest = VerificationChallenge::query()
            ->where('user_id', $user->id)
            ->where('channel', VerificationChannel::Mobile)
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

    private function requireNormalizedMobile(User $user): string
    {
        $normalized = MobileNumber::normalize($user->mobile);
        if (! is_string($normalized)) {
            throw ValidationException::withMessages([
                'mobile' => filled($user->mobile)
                    ? 'Enter a valid mobile number with country code before requesting a verification code. India numbers can use +91, 91, or a 10-digit mobile number.'
                    : 'Add a mobile number in Account settings before requesting a verification code.',
            ]);
        }

        return $normalized;
    }

    private function assertResendAllowed(User $user, string $destination): void
    {
        $wait = $this->retryAfterSeconds($user);
        if ($wait > 0) {
            throw ValidationException::withMessages([
                'mobile' => 'Please wait '.$wait.' seconds before requesting another code.',
            ]);
        }
    }

    private function invalidateActive(User $user, string $destination): void
    {
        VerificationChallenge::query()
            ->where('user_id', $user->id)
            ->where('channel', VerificationChannel::Mobile)
            ->where('destination', $destination)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->update(['expires_at' => now()]);
    }

    private function activeChallenge(User $user, string $destination): ?VerificationChallenge
    {
        return VerificationChallenge::query()
            ->where('user_id', $user->id)
            ->where('channel', VerificationChannel::Mobile)
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
