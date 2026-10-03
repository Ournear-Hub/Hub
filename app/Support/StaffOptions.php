<?php

namespace App\Support;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

class StaffOptions
{
    /**
     * Active admin-guard staff for assignment dropdowns. Not filtered by role slug.
     *
     * @return list<array{id: int, name: string}>
     */
    public static function users(): array
    {
        return static::assignableQuery()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
            ])
            ->values()
            ->all();
    }

    /**
     * Active Admin staff only. Candidate and Employer accounts are excluded.
     *
     * @return Builder<User>
     */
    public static function assignableQuery(): Builder
    {
        return User::query()
            ->where('role', UserRole::Admin)
            ->where('is_active', true);
    }

    public static function isAssignable(?int $userId): bool
    {
        if ($userId === null || $userId <= 0) {
            return true;
        }

        return static::assignableQuery()->whereKey($userId)->exists();
    }

    /**
     * @return list<string|Exists>
     */
    public static function assignedToRules(): array
    {
        return [
            'nullable',
            'integer',
            Rule::exists('users', 'id')->where(function ($query) {
                $query->where('role', UserRole::Admin->value)
                    ->where('is_active', true);
            }),
        ];
    }
}
