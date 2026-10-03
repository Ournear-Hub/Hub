<?php

namespace App\Services;

use App\Enums\SecurityActivityType;
use App\Models\SecurityActivity;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class SecurityAuditService
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function record(
        SecurityActivityType $action,
        string $description,
        ?User $actor = null,
        ?Model $subject = null,
        array $metadata = [],
        ?Request $request = null,
    ): void {
        $request ??= request();

        SecurityActivity::query()->create([
            'actor_id' => $actor?->id,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'action' => $action,
            'description' => $description,
            'ip_address' => $request instanceof Request ? $request->ip() : null,
            'metadata' => $metadata === [] ? null : $metadata,
        ]);
    }
}
