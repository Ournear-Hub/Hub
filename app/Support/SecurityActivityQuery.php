<?php

namespace App\Support;

use App\Enums\SecurityActivityType;
use App\Models\SecurityActivity;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class SecurityActivityQuery
{
    /**
     * @return LengthAwarePaginator<int, SecurityActivity>
     */
    public function adminIndex(Request $request): LengthAwarePaginator
    {
        $query = SecurityActivity::query()
            ->with('actor:id,name,email')
            ->latest('id');

        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $query->where(function (Builder $inner) use ($search) {
                $inner->where('description', 'like', '%'.$search.'%')
                    ->orWhereHas('actor', fn (Builder $actor) => $actor->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%'));
            });
        }

        $action = trim((string) $request->query('action', ''));
        if ($action !== '' && SecurityActivityType::tryFrom($action)) {
            $query->where('action', $action);
        }

        $from = trim((string) $request->query('date_from', ''));
        if ($from !== '') {
            $query->whereDate('created_at', '>=', $from);
        }

        $to = trim((string) $request->query('date_to', ''));
        if ($to !== '') {
            $query->whereDate('created_at', '<=', $to);
        }

        return $query->paginate(20)->withQueryString();
    }

    public function selected(Request $request): ?SecurityActivity
    {
        $id = $request->integer('activity');
        if ($id <= 0) {
            return null;
        }

        return SecurityActivity::query()->with('actor:id,name,email')->find($id);
    }

    /**
     * @return array<string, mixed>
     */
    public function filters(Request $request): array
    {
        $selectedId = $request->integer('activity');

        return [
            'search' => $request->string('search')->toString(),
            'action' => $request->string('action')->toString(),
            'date_from' => $request->string('date_from')->toString(),
            'date_to' => $request->string('date_to')->toString(),
            'activity' => $selectedId > 0 ? (string) $selectedId : '',
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public function actions(): array
    {
        return array_map(fn (SecurityActivityType $action) => [
            'value' => $action->value,
            'label' => $action->label(),
        ], SecurityActivityType::cases());
    }
}
