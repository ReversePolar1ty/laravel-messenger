<?php

namespace App\Services\Chats;

use App\Models\Chat;
use App\Models\ChatRead;
use App\Services\UserStatusService;
use Illuminate\Support\Collection;

class ChatPresenter
{
    public function __construct(
        private readonly ChatQueryService $chats,
        private readonly UserStatusService $statusService,
    ) {}

    public function collection(Collection $chats, int $currentUserId): Collection
    {
        return $chats->map(fn (Chat $chat) => $this->single($chat, $currentUserId));
    }

    public function single(Chat $chat, int $currentUserId, bool $withStatus = false): array
    {
        $participants = $this->chats->participants($chat->id);
        $otherUser = $participants->firstWhere('id', '!=', $currentUserId);
        $currentUserRead = ChatRead::query()
            ->where('chat_id', $chat->id)
            ->where('user_id', $currentUserId)
            ->first();

        $data = $chat->toArray();
        $data['participants'] = $participants;
        $data['last_read_message_id'] = $currentUserRead?->last_read_message_id;
        $data['has_unread'] = $chat->last_message_id
            && (! $currentUserRead?->last_read_message_id
                || strcmp((string) $currentUserRead->last_read_message_id, (string) $chat->last_message_id) < 0);
        $data['display_title'] = $chat->type === 'direct'
            ? ($otherUser->name ?? "\u{041B}\u{0438}\u{0447}\u{043D}\u{044B}\u{0439} \u{0447}\u{0430}\u{0442}")
            : ($chat->title ?? "\u{0413}\u{0440}\u{0443}\u{043F}\u{043F}\u{043E}\u{0432}\u{043E}\u{0439} \u{0447}\u{0430}\u{0442}");
        $data['other_user_status'] = $withStatus && $otherUser
            ? $this->statusService->getStatus((int) $otherUser->id)
            : null;

        return $data;
    }
}
