<?php

namespace App\Models;

use App\Enums\MasterDataChangeAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MasterDataChange extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'entity_type',
        'entity_id',
        'action',
        'body',
        'actor_id',
        'meta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'action' => MasterDataChangeAction::class,
            'meta' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminArray(): array
    {
        $this->loadMissing('actor');

        return [
            'id' => $this->id,
            'title' => $this->body,
            'meta' => trim(implode(' · ', array_filter([
                $this->actor?->name,
                $this->created_at?->toDateTimeString(),
            ]))),
        ];
    }
}
