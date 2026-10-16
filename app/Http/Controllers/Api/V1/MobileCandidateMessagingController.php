<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ConversationContextType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SendApplicationMessageRequest;
use App\Models\Application;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\RecruitmentApplicationMessagingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileCandidateMessagingController extends Controller
{
    public function inbox(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->isCandidate(), 403);

        $applicationIds = Application::query()
            ->where('user_id', $user->id)
            ->pluck('id');

        $conversations = Conversation::query()
            ->where('context_type', ConversationContextType::Application)
            ->whereIn('context_id', $applicationIds)
            ->whereHas('participants', fn ($query) => $query->where('user_id', $user->id))
            ->with(['latestMessage', 'participantRecords'])
            ->orderByDesc('updated_at')
            ->get();

        $applications = Application::query()
            ->whereIn('id', $conversations->pluck('context_id'))
            ->with('job')
            ->get()
            ->keyBy('id');

        $data = $conversations->map(function (Conversation $conversation) use ($user, $applications) {
            $application = $applications->get($conversation->context_id);
            $latest = $conversation->latestMessage;
            $participant = $conversation->participantRecords
                ->firstWhere('user_id', $user->id);
            $lastReadAt = $participant?->last_read_at;

            $unread = $latest instanceof Message
                && (int) $latest->sender_id !== (int) $user->id
                && ($lastReadAt === null || $latest->created_at?->gt($lastReadAt));

            return [
                'id' => (string) ($application?->id ?? $conversation->context_id),
                'application_id' => $application?->id,
                'title' => $application?->job?->title ?? $conversation->subject ?? 'Application messages',
                'preview' => $latest instanceof Message ? $latest->body : '',
                'unread' => $unread,
                'job_slug' => $application?->job?->slug,
            ];
        })->values()->all();

        return response()->json(['data' => $data]);
    }

    public function show(Request $request, Application $application, RecruitmentApplicationMessagingService $messaging): JsonResponse
    {
        abort_unless($request->user() instanceof User && $request->user()->isCandidate(), 403);
        abort_unless((int) $application->user_id === (int) $request->user()->id, 403);

        $application->loadMissing('job');
        $thread = $messaging->threadFor($request->user(), $application);

        $messages = array_map(
            fn (array $row) => [
                'id' => (string) $row['id'],
                'body' => $row['body'],
                'from_candidate' => (bool) $row['is_mine'],
            ],
            $thread,
        );

        return response()->json([
            'data' => [
                'id' => (string) $application->id,
                'application_id' => $application->id,
                'title' => $application->job?->title ?? 'Application messages',
                'preview' => $messages !== [] ? ($messages[array_key_last($messages)]['body'] ?? '') : '',
                'unread' => false,
                'job_slug' => $application->job?->slug,
                'messages' => $messages,
            ],
        ]);
    }

    public function store(
        SendApplicationMessageRequest $request,
        Application $application,
        RecruitmentApplicationMessagingService $messaging,
    ): JsonResponse {
        $message = $messaging->send($request->user(), $application, $request->string('body')->toString());

        return response()->json([
            'message' => 'Message sent.',
            'data' => [
                'id' => (string) $message->id,
                'body' => $message->body,
                'from_candidate' => true,
            ],
        ], 201);
    }
}
