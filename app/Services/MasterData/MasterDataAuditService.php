<?php

namespace App\Services\MasterData;

use App\Enums\MasterDataChangeAction;
use App\Enums\MasterEntityType;
use App\Models\MasterDataChange;
use App\Models\User;

class MasterDataAuditService
{
    /**
     * @param  array<string, mixed>|null  $meta
     */
    public function record(
        User $actor,
        MasterEntityType $type,
        int $entityId,
        MasterDataChangeAction $action,
        string $body,
        ?array $meta = null,
    ): MasterDataChange {
        return MasterDataChange::query()->create([
            'entity_type' => $type->value,
            'entity_id' => $entityId,
            'action' => $action,
            'body' => $body,
            'actor_id' => $actor->id,
            'meta' => $meta,
        ]);
    }
}
