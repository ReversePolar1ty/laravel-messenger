<?php

namespace App\Services\Messages;

use App\Events\ChatListUpdated;
use App\Events\MessageDeleted;
use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use App\Services\Chats\ChatQueryService;
use App\Services\Chats\LastMessageSynchronizer;

class MessageDeletionService
{
    public function __construct(
        private readonly ChatQueryService $chats,
        private readonly LastMessageSynchronizer $lastMessages,
    ) {}

    /**
     * Удаляет сообщение для всех участников чата по Telegram-подходу.
     *
     * Документ остается в MongoDB как soft-deleted запись, чтобы не ломать историю,
     * read-state и ссылки на message id, но фронтенд больше не показывает его в чате.
     */
    public function deleteForEveryone(Chat $chat, string $messageId, User $user): Message
    {
        // Повторяем ключевые ограничения из FormRequest на уровне сервиса:
        // удалять можно только свое, неудаленное сообщение из текущего чата.
        $message = Message::query()
            ->where('_id', $messageId)
            ->where('chat_id', $chat->id)
            ->where('sender_id', $user->id)
            ->where('is_deleted', '!=', true)
            ->firstOrFail();

        // Не удаляем документ физически: MongoDB хранит историю сообщений,
        // а MariaDB может ссылаться на ObjectId последнего сообщения.
        $message->forceFill([
            'text' => null,
            'attachments' => [],
            'is_deleted' => true,
        ])->save();

        $deletedMessage = $message->fresh();

        // Если удалили последнее сообщение чата, нужно пересчитать MariaDB-дубликат
        // last_message_*, иначе список чатов будет показывать уже удаленный текст.
        $this->lastMessages->syncAfterDeletion($chat, $deletedMessage);

        // Открытые страницы конкретного чата убирают сообщение из локального списка.
        broadcast(new MessageDeleted($deletedMessage->toArray(), $chat->id))->toOthers();

        // Список чатов у всех участников должен обновить preview и unread-индикаторы.
        $this->chats->participantIds($chat->id)
            ->each(fn ($userId) => broadcast(new ChatListUpdated((int) $userId)));

        return $deletedMessage;
    }
}
