<?php

namespace App\Services;

use App\Enums\CommunicationChannel;
use App\Enums\UserActivityType;
use App\Models\User;
use App\Models\UserCommunicationPreference;

class CommunicationPreferenceService
{
    public function __construct(private UserActivityService $activities) {}

    public function ensureDefaults(User $user): void
    {
        foreach (CommunicationChannel::cases() as $channel) {
            UserCommunicationPreference::query()->firstOrCreate(
                [
                    'user_id' => $user->id,
                    'channel' => $channel->value,
                ],
                [
                    'opted_in' => $channel->defaultOptIn(),
                    'consented_at' => $channel->defaultOptIn() ? now() : null,
                    'source' => 'system_default',
                    'opted_out_at' => $channel->defaultOptIn() ? null : now(),
                ],
            );
        }

        $this->normalizeLegacyInAppDefault($user);
    }

    /**
     * In-app recruitment notifications (account notification center).
     */
    public function canSendRecruitmentInApp(User $user): bool
    {
        return $this->canSend($user, CommunicationChannel::Push);
    }

    /**
     * Operational recruitment email (rejected/selected/interview, employer alerts).
     */
    public function canSendRecruitmentEmail(User $user): bool
    {
        return $this->canSend($user, CommunicationChannel::System);
    }

    private function normalizeLegacyInAppDefault(User $user): void
    {
        $push = $user->communicationPreferences()
            ->where('channel', CommunicationChannel::Push->value)
            ->first();

        if ($push === null || $push->source !== 'system_default' || $push->opted_in) {
            return;
        }

        $push->fill([
            'opted_in' => true,
            'consented_at' => now(),
            'opted_out_at' => null,
        ])->save();
    }

    public function canSend(User $user, CommunicationChannel $channel): bool
    {
        $this->ensureDefaults($user);

        $preference = $user->communicationPreferences()
            ->where('channel', $channel->value)
            ->first();

        return (bool) $preference?->opted_in;
    }

    /**
     * @param  array<string, bool>  $channels
     */
    public function sync(User $user, array $channels, string $source = 'admin'): void
    {
        $this->ensureDefaults($user);

        $changed = false;

        foreach (CommunicationChannel::cases() as $channel) {
            if (! array_key_exists($channel->value, $channels)) {
                continue;
            }

            $optedIn = (bool) $channels[$channel->value];
            $preference = $user->communicationPreferences()->where('channel', $channel->value)->first();
            if (! $preference) {
                continue;
            }

            if ((bool) $preference->opted_in !== $optedIn) {
                $changed = true;
            }

            $preference->fill([
                'opted_in' => $optedIn,
                'source' => $source,
                'consented_at' => $optedIn ? now() : $preference->consented_at,
                'opted_out_at' => $optedIn ? null : now(),
            ])->save();
        }

        if ($changed) {
            $this->activities->record(
                $user,
                UserActivityType::CommunicationPreferenceChanged,
                'Communication preferences were updated.',
                $user,
                ['source' => $source],
            );
        }
    }
}
