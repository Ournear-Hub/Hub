<?php

namespace App\Support;

use App\Enums\StaffRoleSlug;
use App\Enums\UserRole;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class UserQuery
{
    /**
     * @return LengthAwarePaginator<int, User>
     */
    public function adminIndex(Request $request): LengthAwarePaginator
    {
        $query = User::query()
            ->with('roles')
            ->latest('id');

        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $query->where(function (Builder $inner) use ($search) {
                $inner->where('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%');
            });
        }

        $accountType = trim((string) $request->query('account_type', ''));
        if (in_array($accountType, array_map(fn (UserRole $role) => $role->value, UserRole::cases()), true)) {
            $query->where('role', $accountType);
        }

        $status = trim((string) $request->query('status', ''));
        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        $roleId = $request->integer('role_id');
        if ($roleId > 0) {
            $query->whereHas('roles', fn (Builder $role) => $role->where('roles.id', $roleId));
        }

        return $query->paginate(15)->withQueryString();
    }

    public function selected(Request $request): ?User
    {
        $id = $request->integer('user');
        if ($id <= 0) {
            return null;
        }

        return User::query()->with('roles.permissions')->find($id);
    }

    /**
     * @return array<string, mixed>
     */
    public function filters(Request $request): array
    {
        $selectedId = $request->integer('user');

        return [
            'search' => $request->string('search')->toString(),
            'account_type' => $request->string('account_type')->toString(),
            'status' => $request->string('status')->toString(),
            'role_id' => $request->integer('role_id') > 0 ? (string) $request->integer('role_id') : '',
            'user' => $selectedId > 0 ? (string) $selectedId : '',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function options(): array
    {
        return [
            'account_types' => array_map(fn (UserRole $role) => [
                'value' => $role->value,
                'label' => ucfirst($role->value),
            ], UserRole::cases()),
            'roles' => Role::query()
                ->orderBy('name')
                ->get(['id', 'name', 'slug', 'is_locked'])
                ->map(fn (Role $role) => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'slug' => $role->slug,
                    'is_locked' => $role->is_locked,
                    'assignable' => $role->slug !== StaffRoleSlug::SuperAdmin->value,
                ])
                ->all(),
            'statuses' => [
                ['value' => 'active', 'label' => 'Active'],
                ['value' => 'inactive', 'label' => 'Inactive'],
            ],
        ];
    }
}
