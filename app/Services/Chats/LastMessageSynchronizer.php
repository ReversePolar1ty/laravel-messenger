<?php

namespace App\Services\Chats;

use App\Models\Chat;
use App\Models\Message;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LastMessageSynchronizer
{
    /**
     * Синхронизирует денормализованные поля последнего сообщения в MariaDB.
     *
     * Вызывается после удаления MongoDB-сообщения, потому что список чатов читает
     * last_message_id/text/at из таблицы chats, а не из коллекции сообщений.
     */
    public function syncAfterDeletion(Chat $chat, Message $deletedMessage): void
    {
        DB::connection($chat->getConnectionName())->transaction(function () use ($chat, $deletedMessage) {
            // Блокируем строку чата, чтобы параллельная отправка нового сообщения
            // не была перезаписана fallback-значением от удаления старого сообщения.
            $freshChat = Chat::query()
                ->whereKey($chat->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Если удаленное сообщение уже не последнее, denormalized preview не трогаем.
            if ((string) $freshChat->last_message_id !== (string) $deletedMessage->_id) {
                return;
            }

            // ObjectId в MongoDB монотонно растет, поэтому сортировка по _id desc
            // возвращает последнее неудаленное сообщение без отдельного SQL timestamp.
            $replacement = Message::query()
                ->where('chat_id', $chat->id)
                ->where('is_deleted', '!=', true)
                ->orderBy('_id', 'desc')
                ->first();

            // Если сообщений больше нет, очищаем preview чата полностью.
            $freshChat->update([
                'last_message_id' => $replacement ? (string) $replacement->_id : null,
                'last_message_text' => $replacement ? Str::limit((string) $replacement->text, 50) : null,
                'last_message_at' => $replacement?->created_at,
            ]);
        });
    }
}
