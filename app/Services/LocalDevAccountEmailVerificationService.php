<?php

namespace App\Services;

use App\Enums\SecurityActivityType;
use App\Models\User;
use Illuminate\Contracts\Foundation\Application;

class LocalDevAccountEmailVerificationService
{
    public function __construct(
        private readonly Application $app,
        private readonly SecurityAuditService $audit,
        private readonly EmployerPostingEligibilityService $eligibility,
    ) {}

    public function assertAllowedEnvironment(): void
    {
        if ($this->app->isProduction()) {
            throw new \RuntimeException('Account email verification override is not allowed when APP_ENV=production.');
        }

        if (! $this->app->environment(['local', 'testing'])) {
            throw new \RuntimeException('Account email verification override is restricted to local and testing environments.');
        }
    }

    public function verify(User $user, ?User $actor = null): bool
    {
        $this->assertAllowedEnvironment();

        if ($user->emailVerified()) {
            return false;
        }

        $user->forceFill(['email_verified_at' => now()])->save();
        $this->eligibility->syncPublishingBenefits($user);

        $this->audit->record(
            SecurityActivityType::UserUpdated,
            'Local development: account email marked verified.',
            $actor,
            $user,
            metadata: [
                'local_dev_action' => 'verify_account_email',
                'target_user_id' => $user->id,
            ],
        );

        return true;
    }
}
