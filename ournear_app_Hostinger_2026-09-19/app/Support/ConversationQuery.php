<?php

namespace App\Support;

use App\Enums\ConversationContextType;
use App\Enums\PermissionName;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ConversationQuery
{
    /**
     * @return LengthAwarePaginator<int, Conversation>
     */
    public function adminIndex(Request $request, User $user): LengthAwarePaginator
    {
        $query = Conversation::query()
            ->whereHas('participantRecords', fn (Builder $participant) => $participant->where('user_id', $user->id))
            ->with(['latestMessage.sender:id,name', 'participants:id,name', 'participantRecords'])
            ->latest('updated_at');

        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $query->where(function (Builder $inner) use ($search) {
                $inner->where('subject', 'like', '%'.$search.'%')
                    ->orWhereHas('messages', fn (Builder $message) => $message->where('body', 'like', '%'.$search.'%'));
            });
        }

        if ($request->boolean('unread')) {
            $query->whereHas('latestMessage', function (Builder $message) use ($user) {
                $message->where('sender_id', '!=', $user->id);
            })->whereHas('participantRecords', function (Builder $participant) use ($user) {
                $participant->where('user_id', $user->id)
                    ->where(function (Builder $inner) {
                        $inner->whereNull('last_read_message_id')
                            ->orWhereRaw('conversation_participants.last_read_message_id != (
                                select max(id) from messages where messages.conversation_id = conversation_participants.conversation_id
                            )');
                    });
            });
        }

        return $query->paginate(15)->withQueryString();
    }

    public function selected(Request $request, User $user): ?Conversation
    {
        $id = $request->integer('conversation');
        if ($id <= 0) {
            return null;
        }

        return Conversation::query()
            ->with(['messages.sender:id,name', 'participants:id,name', 'participantRecords', 'creator:id,name', 'latestMessage'])
            ->whereKey($id)
            ->whereHas('participantRecords', fn (Builder $participant) => $participant->where('user_id', $user->id))
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    public function filters(Request $request): array
    {
        $selectedId = $request->integer('conversation');

        return [
            'search' => $request->string('search')->toString(),
            'unread' => $request->boolean('unread') ? '1' : '',
            'conversation' => $selectedId > 0 ? (string) $selectedId : '',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function options(): array
    {
        return [
            'admins' => User::query()
                ->where('role', 'admin')
                ->where('is_active', true)
                ->whereHas('roles.permissions', fn ($query) => $query->where('name', PermissionName::MessagesManage->value))
                ->orderBy('name')
                ->get(['id', 'name', 'email'])
                ->map(fn (User $user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'label' => $user->name.' · '.$user->email,
                ])
                ->all(),
            'contexts' => array_map(fn (ConversationContextType $type) => $type->value, ConversationContextType::cases()),
        ];
    }
}
