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
    public function markRead(Chat $chat, User $user, ?string $messageId): ?ChatRead
    {
        $message = $this->resolveMessage($chat->id, $messageId);

        if (! $message) {
            return null;
        }

        $lastReadMessageId = (string) ($message->_id ?? $message->id);
        $read = ChatRead::query()
            ->where('chat_id', $chat->id)
            ->where('user_id', $user->id)
            ->first();

        if ($read && $read->last_read_message_id && strcmp((string) $read->last_read_message_id, $lastReadMessageId) >= 0) {
            return $read;
        }

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

        broadcast(new MessageRead(
            $chat->id,
            $user->id,
            $lastReadMessageId,
            $read->last_read_at->toISOString(),
        ))->toOthers();
        broadcast(new ChatListUpdated($user->id));

        return $read;
    }

    private function resolveMessage(string $chatId, ?string $messageId): ?Message
    {
        if ($messageId) {
            return Message::query()
                ->where('chat_id', $chatId)
                ->where('_id', $messageId)
                ->first();
        }

        return Message::query()
            ->where('chat_id', $chatId)
            ->orderByDesc('created_at')
            ->first();
    }
}
