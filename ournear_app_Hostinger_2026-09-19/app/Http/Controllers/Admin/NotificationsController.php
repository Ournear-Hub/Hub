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
            ->through(fn (DatabaseNotification $notification) => NotificationQuery::toAdminArray($notification));

        $selected = $notifications->selected($request, $user);
        if ($selected && $user->cannot('view', $selected)) {
            $selected = null;
        }

        return Inertia::render('Admin/Notifications/Index', [
            'notifications' => $page,
            'selected' => $selected ? NotificationQuery::toAdminArray($selected) : null,
            'filters' => $notifications->filters($request),
            'events' => $notifications->events(),
            'unreadCount' => $user->unreadNotifications()->count(),
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
