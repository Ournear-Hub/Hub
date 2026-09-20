<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ConversationContextType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReplyMessageRequest;
use App\Http\Requests\Admin\StartConversationRequest;
use App\Models\Conversation;
use App\Models\User;
use App\Services\MessageService;
use App\Support\ConversationQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MessagesController extends Controller
{
    public function index(Request $request, ConversationQuery $conversations): Response
    {
        $this->authorize('viewAny', Conversation::class);

        $user = $request->user();
        $page = $conversations->adminIndex($request, $user)
            ->through(fn (Conversation $conversation) => $conversation->toAdminListArray($user));

        $selected = $conversations->selected($request, $user);
        if ($selected && $user->cannot('view', $selected)) {
            $selected = null;
        }

        if ($selected) {
            app(MessageService::class)->markRead($selected, $user);
            $selected->refresh()->load(['messages.sender:id,name', 'participants:id,name', 'participantRecords', 'creator:id,name', 'latestMessage']);
        }

        return Inertia::render('Admin/Messages/Index', [
            'conversations' => $page,
            'selected' => $selected?->toAdminArray($user),
            'filters' => $conversations->filters($request),
            'options' => $conversations->options(),
        ]);
    }

    public function store(StartConversationRequest $request, MessageService $messages): RedirectResponse
    {
        $contextType = $request->filled('context_type')
            ? ConversationContextType::from($request->string('context_type')->toString())
            : null;

        $conversation = $messages->start(
            $request->user(),
            User::query()->findOrFail($request->integer('recipient_id')),
            $request->string('subject')->toString(),
            $request->string('body')->toString(),
            $contextType,
            $request->filled('context_id') ? $request->integer('context_id') : null,
        );

        return redirect()
            ->route('admin.messages.index', ['conversation' => $conversation->id])
            ->with('success', 'Message sent.');
    }

    public function reply(
        ReplyMessageRequest $request,
        Conversation $conversation,
        MessageService $messages,
    ): RedirectResponse {
        $messages->reply($conversation, $request->user(), $request->string('body')->toString());

        return back()->with('success', 'Reply sent.');
    }
}
