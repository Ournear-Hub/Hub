<?php

namespace App\Services;

use App\Enums\UserActivityType;
use App\Models\User;
use App\Models\UserActivity;
use Illuminate\Support\Carbon;

class UserActivityService
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function record(
        User $user,
        UserActivityType $type,
        string $description,
        ?User $actor = null,
        array $metadata = [],
        ?Carbon $at = null,
    ): UserActivity {
        $activity = new UserActivity([
            'user_id' => $user->id,
            'actor_id' => $actor?->id,
            'type' => $type,
            'description' => $description,
            'metadata' => $metadata === [] ? null : $metadata,
        ]);

        if ($at) {
            $activity->created_at = $at;
            $activity->updated_at = $at;
        }

        $activity->save();

        return $activity;
    }
}
