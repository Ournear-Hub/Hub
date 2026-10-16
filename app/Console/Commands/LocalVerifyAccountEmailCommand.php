<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\LocalDevAccountEmailVerificationService;
use Illuminate\Console\Command;

class LocalVerifyAccountEmailCommand extends Command
{
    protected $signature = 'ournear:local-verify-account-email
                            {identifier : Account email or username}
                            {--actor= : Optional admin email recorded as actor in the audit log}';

    protected $description = 'Mark one account email verified in local/testing only (no mail delivery required)';

    public function handle(LocalDevAccountEmailVerificationService $verification): int
    {
        try {
            $verification->assertAllowedEnvironment();
        } catch (\RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $identifier = trim((string) $this->argument('identifier'));
        if ($identifier === '') {
            $this->error('Identifier is required.');

            return self::FAILURE;
        }

        $user = User::query()
            ->where(function ($query) use ($identifier): void {
                $query->whereRaw('LOWER(email) = ?', [strtolower($identifier)])
                    ->orWhere('username', User::normalizeUsername($identifier));
            })
            ->first();

        if (! $user instanceof User) {
            $this->error('No account found for that email or username.');

            return self::FAILURE;
        }

        if ($user->emailVerified()) {
            $this->info("Account already email-verified: {$user->email} (id {$user->id}).");

            return self::SUCCESS;
        }

        $actor = null;
        $actorEmail = trim((string) $this->option('actor'));
        if ($actorEmail !== '') {
            $actor = User::query()->whereRaw('LOWER(email) = ?', [strtolower($actorEmail)])->first();
        }

        $verification->verify($user, $actor);

        $this->info("Email verified for {$user->email} (user id {$user->id}).");

        return self::SUCCESS;
    }
}
