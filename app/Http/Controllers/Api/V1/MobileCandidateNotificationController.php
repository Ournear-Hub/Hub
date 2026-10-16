<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\CandidateNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class MobileCandidateNotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->authorize('viewAny', DatabaseNotification::class);
        abort_unless($user?->isCandidate() === true, 403);

        $page = max(1, (int) $request->integer('page', 1));
        $paginator = $user->notifications()
            ->latest()
            ->paginate(20, ['*'], 'page', $page);

        return response()->json([
            'data' => $paginator->getCollection()
                ->map(fn (DatabaseNotification $notification) => CandidateNotificationService::toAccountArray($notification))
                ->values()
                ->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
                'unread_count' => $user->unreadNotifications()->count(),
            ],
        ]);
    }

    public function markRead(Request $request, DatabaseNotification $notification): JsonResponse
    {
        $this->authorize('update', $notification);

        if ($notification->read_at === null) {
            $notification->markAsRead();
        }

        return response()->json([
            'message' => 'Notification marked as read.',
            'data' => CandidateNotificationService::toAccountArray($notification->fresh() ?? $notification),
        ]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->authorize('viewAny', DatabaseNotification::class);
        abort_unless($user?->isCandidate() === true, 403);

        $user->unreadNotifications->markAsRead();

        return response()->json([
            'message' => 'All notifications marked as read.',
            'meta' => ['unread_count' => 0],
        ]);
    }
}
