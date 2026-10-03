<?php

namespace App\Models;

use App\Enums\PermissionName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'group',
        'label',
    ];

    /**
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminArray(): array
    {
        $enum = PermissionName::tryFrom($this->name);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'group' => $this->group,
            'label' => $this->label,
            'group_label' => $enum?->group() ?? $this->group,
        ];
    }
}
