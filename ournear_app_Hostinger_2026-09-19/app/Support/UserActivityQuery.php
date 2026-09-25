<?php

namespace App\Support;

use App\Enums\UserActivityType;
use App\Models\UserActivity;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class UserActivityQuery
{
    /**
     * @return LengthAwarePaginator<int, UserActivity>
     */
    public function adminIndex(Request $request): LengthAwarePaginator
    {
        $query = UserActivity::query()
            ->with(['user', 'actor'])
            ->latest('id');

        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $query->where(function (Builder $inner) use ($search) {
                $inner->where('description', 'like', '%'.$search.'%')
                    ->orWhereHas('user', function (Builder $user) use ($search) {
                        $user->where('name', 'like', '%'.$search.'%')
                            ->orWhere('email', 'like', '%'.$search.'%')
                            ->orWhere('business_id', 'like', '%'.$search.'%');
                    });
            });
        }

        $type = trim((string) $request->query('type', ''));
        if (UserActivityType::tryFrom($type)) {
            $query->where('type', $type);
        }

        return $query->paginate(20)->withQueryString();
    }

    /**
     * @return array<string, mixed>
     */
    public function filters(Request $request): array
    {
        return [
            'search' => $request->string('search')->toString(),
            'type' => $request->string('type')->toString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function options(): array
    {
        return [
            'types' => array_map(fn (UserActivityType $type) => [
                'value' => $type->value,
                'label' => $type->label(),
            ], UserActivityType::cases()),
        ];
    }
}
