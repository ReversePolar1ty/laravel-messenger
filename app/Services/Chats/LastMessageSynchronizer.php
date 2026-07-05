<?php

namespace App\Services\Chats;

use App\Models\Chat;
use App\Models\Message;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LastMessageSynchronizer
{
    public function syncAfterDeletion(Chat $chat, Message $deletedMessage): void
    {
        DB::connection($chat->getConnectionName())->transaction(function () use ($chat, $deletedMessage) {
            $freshChat = Chat::query()
                ->whereKey($chat->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ((string) $freshChat->last_message_id !== (string) $deletedMessage->_id) {
                return;
            }

            $replacement = Message::query()
                ->where('chat_id', $chat->id)
                ->where('is_deleted', '!=', true)
                ->orderBy('_id', 'desc')
                ->first();

            $freshChat->update([
                'last_message_id' => $replacement ? (string) $replacement->_id : null,
                'last_message_text' => $replacement ? Str::limit((string) $replacement->text, 50) : null,
                'last_message_at' => $replacement?->created_at,
            ]);
        });
    }
}
