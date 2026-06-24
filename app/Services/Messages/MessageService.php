<?php

namespace App\Services\Messages;

use App\Events\ChatListUpdated;
use App\Events\MessageRead;
use App\Events\MessageSent;
use App\Models\Chat;
use App\Models\ChatRead;
use App\Models\Message;
use App\Models\User;
use App\Services\Chats\ChatQueryService;
use Illuminate\Support\Str;

class MessageService
{
    public function __construct(private readonly ChatQueryService $chats) {}

    /**
     * Создаёт сообщение, обновляет метаданные чата и рассылает события участникам.
     */
    public function send(User $sender, array $data): Message
    {
        // История сообщений хранится в MongoDB, поэтому создаём документ Message отдельно от SQL-чата.
        $message = Message::create([
            'chat_id' => $data['chat_id'],
            'user_id' => $sender->id,
            'sender_id' => $sender->id,
            'type' => $data['type'] ?? 'text',
            'text' => $data['text'] ?? null,
        ]);

        // В MariaDB держим денормализованные поля для быстрого списка чатов.
        $chat = Chat::findOrFail($data['chat_id']);
        $chat->update([
            'last_message_id' => (string) $message->_id,
            'last_message_text' => Str::limit($message->text, 50),
            'last_message_at' => $message->created_at,
        ]);

        // Отправитель сразу считается прочитавшим своё сообщение.
        $read = ChatRead::query()->updateOrCreate(
            [
                'chat_id' => $chat->id,
                'user_id' => $sender->id,
            ],
            [
                'last_read_message_id' => (string) $message->_id,
                'last_read_at' => now(),
            ]
        );

        // Открытая страница чата у других участников должна увидеть новый read-state отправителя.
        broadcast(new MessageRead(
            $chat->id,
            $sender->id,
            (string) $message->_id,
            $read->last_read_at->toISOString(),
        ))->toOthers();

        broadcast(new MessageSent($message->toArray(), $data['chat_id']))->toOthers();

        // Список чатов обновляем всем участникам: меняются последний текст, дата и unread-индикаторы.
        $this->chats->participantIds($chat->id)
            ->each(fn ($userId) => broadcast(new ChatListUpdated((int) $userId)));

        return $message;
    }
}
