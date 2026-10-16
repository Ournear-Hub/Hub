<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\RegistrationSource;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\ForgotPasswordRequest;
use App\Http\Requests\Api\V1\MobileLoginRequest;
use App\Http\Requests\Api\V1\MobileRegisterRequest;
use App\Models\CandidateProfile;
use App\Models\User;
use App\Services\Api\V1\MobileCandidatePresenter;
use App\Services\LegalConsentService;
use App\Services\LoginLogService;
use App\Services\ReferralAttributionService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;

class MobileAuthController extends Controller
{
    public function register(
        MobileRegisterRequest $request,
        ReferralAttributionService $referrals,
        LegalConsentService $legalConsent,
        MobileCandidatePresenter $presenter,
    ): JsonResponse {
        $email = strtolower($request->string('email')->toString());
        $username = User::normalizeUsername($request->string('username')->toString());

        $user = DB::transaction(function () use ($request, $referrals, $username, $email) {
            $user = User::query()->create([
                'name' => $request->string('name')->toString(),
                'username' => $username,
                'email' => $email,
                'mobile' => $request->filled('mobile') ? $request->string('mobile')->toString() : null,
                'preferred_language' => $request->filled('preferred_language')
                    ? $request->string('preferred_language')->toString()
                    : 'en',
                'password' => $request->string('password')->toString(),
                'role' => UserRole::Candidate,
                'registration_source' => RegistrationSource::Mobile,
            ]);

            CandidateProfile::query()->create(['user_id' => $user->id]);

            $referrals->attributeRegistration($user, $request);

            $user->forceFill([
                'profile_completion' => $user->calculateProfileCompletion(),
            ])->saveQuietly();

            return $user->fresh() ?? $user;
        });

        event(new Registered($user));

        $legalConsent->recordRegistrationConsent($user, $request, 'mobile_api_signup');

        $deviceName = trim((string) $request->string('device_name')) ?: 'ournear-mobile';
        $expirationMinutes = config('sanctum.expiration');
        $expiresAt = is_numeric($expirationMinutes) && (int) $expirationMinutes > 0
            ? now()->addMinutes((int) $expirationMinutes)
            : null;

        $token = $user->createToken($deviceName, ['account:mobile'], $expiresAt);

        return response()->json([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $expiresAt?->toIso8601String(),
            'verification_email_sent' => true,
            'user' => $this->accountPayload($user, $presenter),
        ], 201);
    }

    public function forgotPassword(ForgotPasswordRequest $request, LoginLogService $logs): JsonResponse
    {
        $email = strtolower($request->string('email')->toString());
        $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

        if ($user?->isAccountMember()) {
            Password::sendResetLink($request->only('email'));
            $logs->passwordResetRequest($request, $user, $email);
        } else {
            $logs->passwordResetRequest($request, null, $email);
        }

        return response()->json([
            'message' => 'If an account exists for that email, a password reset link has been sent.',
        ]);
    }

    public function login(MobileLoginRequest $request, LoginLogService $logs, MobileCandidatePresenter $presenter): JsonResponse
    {
        $user = $request->validatedUser();
        $logs->success($request, $user, 'mobile_api');

        $deviceName = trim((string) $request->string('device_name')) ?: 'ournear-mobile';
        $expirationMinutes = config('sanctum.expiration');
        $expiresAt = is_numeric($expirationMinutes) && (int) $expirationMinutes > 0
            ? now()->addMinutes((int) $expirationMinutes)
            : null;

        $token = $user->createToken($deviceName, ['account:mobile'], $expiresAt);

        return response()->json([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $expiresAt?->toIso8601String(),
            'user' => $this->accountPayload($user, $presenter),
        ]);
    }

    public function show(Request $request, MobileCandidatePresenter $presenter): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'data' => $this->accountPayload($user, $presenter),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()?->currentAccessToken();
        if ($token !== null) {
            $token->delete();
        }

        return response()->json([
            'message' => 'Logged out.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function accountPayload(User $user, MobileCandidatePresenter $presenter): array
    {
        if ($user->isCandidate()) {
            return $presenter->account($user);
        }

        return [
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'role' => $user->role->value,
            'email_verified' => $user->hasVerifiedEmail(),
            'must_change_password' => (bool) $user->must_change_password,
        ];
    }
}
