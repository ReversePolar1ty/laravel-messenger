<?php

namespace App\Services\Messages;

use App\Events\ChatListUpdated;
use App\Events\MessageRead;
use App\Models\Chat;
use App\Models\ChatRead;
use App\Models\Message;
use App\Models\User;

class ChatReadService
{
    /**
     * Обновляет последнюю прочитанную позицию пользователя в чате.
     */
    public function markRead(Chat $chat, User $user, ?string $messageId): ?ChatRead
    {
        $message = $this->resolveMessage($chat->id, $messageId);

        // Если в чате нет сообщений или переданный ID не найден, менять read-state нечего.
        if (! $message) {
            return null;
        }

        $lastReadMessageId = (string) ($message->_id ?? $message->id);

        // Текущий прогресс нужен, чтобы не откатить прочтение на более старое сообщение.
        $read = ChatRead::query()
            ->where('chat_id', $chat->id)
            ->where('user_id', $user->id)
            ->first();

        // Mongo ObjectId монотонно растёт, поэтому строковое сравнение сохраняет направление прогресса.
        if ($read && $read->last_read_message_id && strcmp((string) $read->last_read_message_id, $lastReadMessageId) >= 0) {
            return $read;
        }

        // updateOrCreate покрывает первый read-state пользователя и последующие обновления.
        $read = ChatRead::query()->updateOrCreate(
            [
                'chat_id' => $chat->id,
                'user_id' => $user->id,
            ],
            [
                'last_read_message_id' => $lastReadMessageId,
                'last_read_at' => now(),
            ]
        );

        // Обновляем открытую страницу чата и список чатов текущего пользователя.
        broadcast(new MessageRead(
            $chat->id,
            $user->id,
            $lastReadMessageId,
            $read->last_read_at->toISOString(),
        ))->toOthers();
        broadcast(new ChatListUpdated($user->id));

        return $read;
    }

    /**
     * Находит сообщение, до которого пользователь дочитал.
     */
    private function resolveMessage(string $chatId, ?string $messageId): ?Message
    {
        if ($messageId) {
            // Когда клиент передал конкретный ID, принимаем только сообщение из этого же чата.
            return Message::query()
                ->where('chat_id', $chatId)
                ->where('_id', $messageId)
                ->first();
        }

        // Без ID считаем прочитанным последнее сообщение чата.
        return Message::query()
            ->where('chat_id', $chatId)
            ->orderByDesc('created_at')
            ->first();
    }
}
