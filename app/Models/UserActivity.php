<?php

namespace App\Models;

use App\Casts\RecoveredBackedEnumCast;
use App\Enums\UserActivityType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserActivity extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'actor_id',
        'type',
        'description',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => RecoveredBackedEnumCast::class.':'.UserActivityType::class,
            'metadata' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
        $type = $this->type instanceof UserActivityType ? $this->type : UserActivityType::tryFrom((string) $this->type);

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user_name' => $this->user?->name,
            'actor_id' => $this->actor_id,
            'actor_name' => $this->actor?->name,
            'type' => $type?->value ?? (string) $this->type,
            'type_label' => $type?->label() ?? (string) $this->type,
            'description' => $this->description,
            'metadata' => $this->metadata ?? [],
            'created_at' => optional($this->created_at)?->toDateTimeString(),
        ];
    }
}
