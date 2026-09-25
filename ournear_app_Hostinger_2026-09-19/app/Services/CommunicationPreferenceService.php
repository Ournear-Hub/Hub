<?php

namespace App\Services;

use App\Enums\CommunicationChannel;
use App\Models\User;
use App\Models\UserCommunicationPreference;

class CommunicationPreferenceService
{
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

        foreach (CommunicationChannel::cases() as $channel) {
            if (! array_key_exists($channel->value, $channels)) {
                continue;
            }

            $optedIn = (bool) $channels[$channel->value];
            $preference = $user->communicationPreferences()->where('channel', $channel->value)->first();
            if (! $preference) {
                continue;
            }

            $preference->fill([
                'opted_in' => $optedIn,
                'source' => $source,
                'consented_at' => $optedIn ? now() : $preference->consented_at,
                'opted_out_at' => $optedIn ? null : now(),
            ])->save();
        }
    }
}
