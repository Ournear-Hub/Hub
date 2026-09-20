<?php

namespace App\Support;

use App\Enums\LoginEvent;
use App\Models\LoginLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class LoginLogQuery
{
    /**
     * @return LengthAwarePaginator<int, LoginLog>
     */
    public function adminIndex(Request $request): LengthAwarePaginator
    {
        $query = LoginLog::query()
            ->with('user:id,name,email')
            ->latest('id');

        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $query->where(function (Builder $inner) use ($search) {
                $inner->where('email', 'like', '%'.$search.'%')
                    ->orWhere('ip_address', 'like', '%'.$search.'%')
                    ->orWhereHas('user', fn (Builder $user) => $user->where('name', 'like', '%'.$search.'%'));
            });
        }

        $event = trim((string) $request->query('event', ''));
        if ($event !== '' && LoginEvent::tryFrom($event)) {
            $query->where('event', $event);
        }

        $outcome = trim((string) $request->query('outcome', ''));
        if ($outcome === 'success') {
            $query->where('succeeded', true);
        } elseif ($outcome === 'failure') {
            $query->where('succeeded', false);
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

    public function selected(Request $request): ?LoginLog
    {
        $id = $request->integer('log');
        if ($id <= 0) {
            return null;
        }

        return LoginLog::query()->with('user:id,name,email')->find($id);
    }

    /**
     * @return array<string, mixed>
     */
    public function filters(Request $request): array
    {
        $selectedId = $request->integer('log');

        return [
            'search' => $request->string('search')->toString(),
            'event' => $request->string('event')->toString(),
            'outcome' => $request->string('outcome')->toString(),
            'date_from' => $request->string('date_from')->toString(),
            'date_to' => $request->string('date_to')->toString(),
            'log' => $selectedId > 0 ? (string) $selectedId : '',
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public function events(): array
    {
        return array_map(fn (LoginEvent $event) => [
            'value' => $event->value,
            'label' => $event->label(),
        ], LoginEvent::cases());
    }
}
