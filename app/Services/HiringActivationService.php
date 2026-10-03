<?php

namespace App\Services;

use App\Models\EmployerProfile;
use App\Models\User;
use App\Notifications\HiringEmailVerificationNotification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

class HiringActivationService
{
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
        $active = $emailVerified && $mobileVerified && $hiringEmailVerified && $hasCompany;

        $message = 'Hiring services active';
        if (! $active) {
            if (! $emailVerified) {
                $message = 'Email verification is required to activate hiring services.';
            } elseif (! $mobileVerified) {
                $message = 'Mobile verification required to activate hiring services.';
            } elseif (! $hiringEmailVerified) {
                $message = 'Verify recruiter email to activate hiring services.';
            } elseif (! $hasCompany) {
                $message = 'Add your company name to activate hiring services. This uses your existing account.';
            }
        }

        return [
            'email_verified' => $emailVerified,
            'mobile_verified' => $mobileVerified,
            'whatsapp_required' => false,
            'is_employer' => $user->isEmployer(),
            'has_company' => $hasCompany,
            'company_name' => $user->employerProfile?->company_name,
            'hiring_email' => $hiringEmail,
            'hiring_email_verified' => $hiringEmailVerified,
            'uses_account_email' => strtolower($hiringEmail) === strtolower((string) $user->email),
            'hiring_active' => $active,
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

    public function activate(User $user, string $companyName, ?string $hiringEmail = null): EmployerProfile
    {
        if (! $user->emailVerified()) {
            throw ValidationException::withMessages([
                'email' => 'Email verification is required to activate hiring services.',
            ]);
        }

        if (! $user->mobileVerified()) {
            throw ValidationException::withMessages([
                'mobile' => 'Mobile verification required to activate hiring services.',
            ]);
        }

        $this->saveHiringEmail($user, $hiringEmail);
        $user->refresh();

        if (! $user->hiringEmailVerified()) {
            $this->sendHiringEmailVerification($user);
            throw ValidationException::withMessages([
                'hiring_email' => 'Verify Recruiter Email. A verification link was sent to the hiring email.',
            ]);
        }

        $name = trim($companyName);
        if ($name === '') {
            throw ValidationException::withMessages([
                'company_name' => 'Company name is required to activate hiring services.',
            ]);
        }

        $profile = $user->employerProfile;
        if ($profile instanceof EmployerProfile) {
            $profile->fill(['company_name' => $name])->save();

            return $profile->fresh() ?? $profile;
        }

        return EmployerProfile::query()->create([
            'user_id' => $user->id,
            'company_name' => $name,
            'contact_person' => $user->name,
            'mobile_number' => $user->mobile,
        ]);
    }
}
