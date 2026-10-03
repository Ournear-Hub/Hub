<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MarkNotificationRequest;
use App\Support\NotificationQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Inertia\Inertia;
use Inertia\Response;

class NotificationsController extends Controller
{
    public function index(Request $request, NotificationQuery $notifications): Response
    {
        $this->authorize('viewAny', DatabaseNotification::class);

        $user = $request->user();
        $page = $notifications->adminIndex($request, $user)
            ->through(function (DatabaseNotification $notification) use ($user) {
                $payload = NotificationQuery::toAdminArray($notification);
                $payload['can_toggle_read'] = $user->can('update', $notification);

                return $payload;
            });

        $selected = $notifications->selected($request, $user);
        if ($selected && $user->cannot('view', $selected)) {
            $selected = null;
        }

        $selectedPayload = $selected ? NotificationQuery::toAdminArray($selected) : null;
        if ($selectedPayload && $selected) {
            $selectedPayload['can_toggle_read'] = $user->can('update', $selected);
        }

        return Inertia::render('Admin/Notifications/Index', [
            'notifications' => $page,
            'selected' => $selectedPayload,
            'filters' => $notifications->filters($request),
            'events' => $notifications->events(),
            'categories' => $notifications->categories(),
            'recipients' => $notifications->recipients($user),
            'unreadCount' => $user->unreadNotifications()->count(),
            'can_view_recipients' => $user->isSuperAdmin(),
        ]);
    }

    public function markRead(MarkNotificationRequest $request, DatabaseNotification $notification): RedirectResponse
    {
        $notification->markAsRead();

        return back();
    }

    public function markUnread(MarkNotificationRequest $request, DatabaseNotification $notification): RedirectResponse
    {
        $notification->forceFill(['read_at' => null])->save();

        return back();
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $this->authorize('viewAny', DatabaseNotification::class);
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'All notifications marked as read.');
    }
}
