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

    public function deleteForEveryone(Chat $chat, string $messageId, User $user): Message
    {
        $message = Message::query()
            ->where('_id', $messageId)
            ->where('chat_id', $chat->id)
            ->where('sender_id', $user->id)
            ->where('is_deleted', '!=', true)
            ->firstOrFail();

        $message->forceFill([
            'text' => null,
            'attachments' => [],
            'is_deleted' => true,
        ])->save();

        $deletedMessage = $message->fresh();

        $this->lastMessages->syncAfterDeletion($chat, $deletedMessage);

        broadcast(new MessageDeleted($deletedMessage->toArray(), $chat->id))->toOthers();

        $this->chats->participantIds($chat->id)
            ->each(fn ($userId) => broadcast(new ChatListUpdated((int) $userId)));

        return $deletedMessage;
    }
}
