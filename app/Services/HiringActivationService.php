<?php

namespace App\Services;

use App\Models\EmployerProfile;
use App\Models\Industry;
use App\Models\User;
use App\Notifications\HiringEmailVerificationNotification;
use App\Support\ApplicationUrlResolver;
use App\Support\MobileNumber;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

class HiringActivationService
{
    public function __construct(
        private EmployerPostingEligibilityService $postingEligibility,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function gate(User $user): array
    {
        $user->loadMissing('employerProfile');
        $emailVerified = $user->emailVerified();
        $mobileVerified = $user->mobileVerified();
        $hiringEmail = $user->hiringEmail();
        $hiringEmailVerified = $user->hiringEmailVerified();
        $hasCompany = $user->employerProfile !== null;
        $workspaceReady = $this->postingEligibility->workspaceReady($user);
        $posting = $this->postingEligibility->for($user);

        $message = $workspaceReady
            ? 'Your employer workspace is ready. You can publish one trial job for one hour. Verify your contact details to unlock unlimited job publishing.'
            : 'Enter your company name, company phone, and company email to activate your employer workspace.';

        return [
            'email_verified' => $emailVerified,
            'mobile_verified' => $mobileVerified,
            'whatsapp_required' => false,
            'is_employer' => $user->isEmployer(),
            'has_company' => $hasCompany,
            'company_name' => $user->employerProfile?->company_name,
            'company_phone' => $user->employerProfile?->mobile_number,
            'industry_id' => $user->employerProfile?->industry_id,
            'hiring_email' => $hiringEmail,
            'hiring_email_verified' => $hiringEmailVerified,
            'uses_account_email' => strtolower($hiringEmail) === strtolower((string) $user->email),
            'hiring_active' => $workspaceReady,
            'workspace_ready' => $workspaceReady,
            'trial_available' => $posting['trial_available'],
            'trial_active' => $posting['trial_active'],
            'trial_expired' => $posting['trial_expired'],
            'trial_expires_at' => $posting['trial_expires_at'],
            'unlimited_publishing' => $posting['unlimited_publishing'],
            'message' => $message,
        ];
    }

    public function saveHiringEmail(User $user, ?string $email): void
    {
        if (! Schema::hasColumn('users', 'hiring_email')) {
            return;
        }

        $normalized = strtolower(trim((string) $email));
        if ($normalized === '') {
            $normalized = strtolower((string) $user->email);
        }

        $user->forceFill([
            'hiring_email' => $normalized,
            'hiring_email_verified_at' => $normalized === strtolower((string) $user->email) && $user->emailVerified()
                ? $user->email_verified_at
                : ($normalized === strtolower((string) $user->hiring_email) ? $user->hiring_email_verified_at : null),
        ])->save();
    }

    public function sendHiringEmailVerification(User $user): void
    {
        if ($user->hiringEmailVerified()) {
            return;
        }

        app(ApplicationUrlResolver::class)->applyForUrlGeneration();

        $url = URL::temporarySignedRoute(
            'account.hiring.email.verify',
            now()->addHours(24),
            [
                'id' => $user->id,
                'hash' => sha1($user->hiringEmail()),
            ],
        );

        $user->notify(new HiringEmailVerificationNotification($url));
    }

    public function verifyHiringEmail(User $user, string $hash): void
    {
        if (! hash_equals(sha1($user->hiringEmail()), $hash)) {
            throw ValidationException::withMessages([
                'hiring_email' => 'This recruiter email verification link is not valid.',
            ]);
        }

        if (! Schema::hasColumn('users', 'hiring_email_verified_at')) {
            return;
        }

        $user->forceFill([
            'hiring_email_verified_at' => now(),
        ])->save();
    }

    public function activate(User $user, string $companyName, string $companyPhone, ?string $companyEmail = null, ?int $industryId = null): EmployerProfile
    {
        $name = trim($companyName);
        if ($name === '') {
            throw ValidationException::withMessages([
                'company_name' => 'Company name is required to activate your employer workspace.',
            ]);
        }

        $phone = MobileNumber::normalize($companyPhone);
        if (! is_string($phone) || $phone === '') {
            throw ValidationException::withMessages([
                'company_phone' => 'A valid company phone number is required.',
            ]);
        }

        $email = trim((string) $companyEmail);
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages([
                'company_email' => 'A valid company email address is required.',
            ]);
        }

        if ($industryId === null || $industryId <= 0) {
            throw ValidationException::withMessages([
                'industry_id' => 'Select your company industry.',
            ]);
        }

        $industry = Industry::query()->find($industryId);
        if ($industry === null || ! $industry->isSelectable()) {
            throw ValidationException::withMessages([
                'industry_id' => 'Select an active industry.',
            ]);
        }

        $this->saveHiringEmail($user, $email);
        $user->refresh();

        $industryName = $industry->name;

        $profile = $user->employerProfile;
        if ($profile instanceof EmployerProfile) {
            $profile->fill([
                'company_name' => $name,
                'mobile_number' => $phone,
                'contact_person' => $profile->contact_person ?: $user->name,
                'industry_id' => $industryId,
                'industry' => $industryName,
            ])->save();

            return $profile->fresh() ?? $profile;
        }

        return EmployerProfile::query()->create([
            'user_id' => $user->id,
            'company_name' => $name,
            'contact_person' => $user->name,
            'mobile_number' => $phone,
            'industry_id' => $industryId,
            'industry' => $industryName,
        ]);
    }
}
