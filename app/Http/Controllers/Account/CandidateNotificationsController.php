<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Services\CandidateNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Inertia\Inertia;
use Inertia\Response;

class CandidateNotificationsController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $this->authorize('viewAny', DatabaseNotification::class);

        $page = $user->notifications()
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn (DatabaseNotification $notification) => CandidateNotificationService::toAccountArray($notification));

        return Inertia::render('Account/Notifications', [
            'notifications' => $page,
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    public function markRead(Request $request, DatabaseNotification $notification): RedirectResponse
    {
        $this->authorize('update', $notification);

        if ($notification->read_at === null) {
            $notification->markAsRead();
        }

        $payload = CandidateNotificationService::toAccountArray($notification->fresh() ?? $notification);
        $fallback = route('account.notifications.index');

        if ($request->boolean('redirect')) {
            return redirect($payload['url'] ?: $fallback);
        }

        return back();
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $this->authorize('viewAny', DatabaseNotification::class);

        $request->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'All notifications marked as read.');
    }
}
