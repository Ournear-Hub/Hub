<?php

namespace App\Support;

use App\Enums\AdminNotificationEvent;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationQuery
{
    /**
     * @return LengthAwarePaginator<int, DatabaseNotification>
     */
    public function adminIndex(Request $request, User $user): LengthAwarePaginator
    {
        $query = $user->notifications()->latest();

        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $query->where(function (Builder $inner) use ($search) {
                $inner->where('data->title', 'like', '%'.$search.'%')
                    ->orWhere('data->body', 'like', '%'.$search.'%');
            });
        }

        $event = trim((string) $request->query('event', ''));
        if ($event !== '') {
            $query->where('data->event', $event);
        }

        if ($request->boolean('unread')) {
            $query->whereNull('read_at');
        }

        return $query->paginate(20)->withQueryString();
    }

    public function selected(Request $request, User $user): ?DatabaseNotification
    {
        $id = trim((string) $request->query('notification', ''));
        if ($id === '') {
            return null;
        }

        return $user->notifications()->whereKey($id)->first();
    }

    /**
     * @return array<string, mixed>
     */
    public function filters(Request $request): array
    {
        $selectedId = trim((string) $request->query('notification', ''));

        return [
            'search' => $request->string('search')->toString(),
            'event' => $request->string('event')->toString(),
            'unread' => $request->boolean('unread') ? '1' : '',
            'notification' => $selectedId,
        ];
    }

    /**
     * @return list<string>
     */
    public function events(): array
    {
        return array_map(fn (AdminNotificationEvent $event) => $event->value, AdminNotificationEvent::cases());
    }

    /**
     * @return array<string, mixed>
     */
    public static function toAdminArray(DatabaseNotification $notification): array
    {
        $data = is_array($notification->data) ? $notification->data : [];

        return [
            'id' => $notification->id,
            'event' => $data['event'] ?? '',
            'title' => $data['title'] ?? '',
            'body' => $data['body'] ?? '',
            'url' => $data['url'] ?? null,
            'context' => $data['context'] ?? [],
            'read_at' => $notification->read_at?->toDateTimeString(),
            'created_at' => $notification->created_at?->toDateTimeString(),
            'is_unread' => $notification->read_at === null,
        ];
    }
}
