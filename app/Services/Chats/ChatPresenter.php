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

    /**
     * Преобразует коллекцию моделей чатов в массивы для Inertia/Vue.
     */
    public function collection(Collection $chats, int $currentUserId): Collection
    {
        return $chats->map(fn (Chat $chat) => $this->single($chat, $currentUserId));
    }

    /**
     * Собирает представление одного чата с участниками, заголовком и unread-флагом.
     */
    public function single(Chat $chat, int $currentUserId, bool $withStatus = false): array
    {
        $participants = $this->chats->participants($chat->id);
        $otherUser = $participants->firstWhere('id', '!=', $currentUserId);

        // Последняя прочитанная позиция нужна и для страницы чата, и для индикатора unread в списке.
        $currentUserRead = ChatRead::query()
            ->where('chat_id', $chat->id)
            ->where('user_id', $currentUserId)
            ->first();

        $data = $chat->toArray();
        $data['participants'] = $participants;
        $data['last_read_message_id'] = $currentUserRead?->last_read_message_id;

        // Mongo ObjectId сравнивается как строка, чтобы понять, есть ли сообщения новее прочитанного.
        $data['has_unread'] = $chat->last_message_id
            && (! $currentUserRead?->last_read_message_id
                || strcmp((string) $currentUserRead->last_read_message_id, (string) $chat->last_message_id) < 0);

        // Для direct-чата фронтенд показывает имя собеседника, для группового - название чата.
        $data['display_title'] = $chat->type === 'direct'
            ? ($otherUser->name ?? "\u{041B}\u{0438}\u{0447}\u{043D}\u{044B}\u{0439} \u{0447}\u{0430}\u{0442}")
            : ($chat->title ?? "\u{0413}\u{0440}\u{0443}\u{043F}\u{043F}\u{043E}\u{0432}\u{043E}\u{0439} \u{0447}\u{0430}\u{0442}");

        // Статус собеседника нужен только на странице чата, чтобы не делать лишние Redis-запросы в списке.
        $data['other_user_status'] = $withStatus && $otherUser
            ? $this->statusService->getStatus((int) $otherUser->id)
            : null;

        return $data;
    }
}
