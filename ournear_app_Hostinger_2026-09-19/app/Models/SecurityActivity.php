<?php

namespace App\Models;

use App\Enums\SecurityActivityType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SecurityActivity extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'actor_id',
        'subject_type',
        'subject_id',
        'action',
        'description',
        'ip_address',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'action' => SecurityActivityType::class,
            'metadata' => 'array',
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
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminArray(): array
    {
        $action = $this->action instanceof SecurityActivityType
            ? $this->action
            : SecurityActivityType::tryFrom((string) $this->action);

        return [
            'id' => $this->id,
            'actor_id' => $this->actor_id,
            'actor_name' => $this->actor?->name,
            'subject_type' => $this->subject_type,
            'subject_id' => $this->subject_id,
            'action' => $action?->value ?? (string) $this->action,
            'action_label' => $action?->label() ?? (string) $this->action,
            'description' => $this->description,
            'ip_address' => $this->ip_address,
            'metadata' => $this->metadata ?? [],
            'created_at' => optional($this->created_at)?->toDateTimeString(),
        ];
    }
}
