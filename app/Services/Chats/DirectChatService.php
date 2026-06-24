<?php

namespace App\Services\Chats;

use App\Events\ChatListUpdated;
use App\Models\Chat;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DirectChatService
{
    public function __construct(private readonly ChatQueryService $chats) {}

    public function findOrCreate(User $creator, int $targetUserId): Chat
    {
        abort_if($creator->id === $targetUserId, 422, "\u{041D}\u{0435}\u{043B}\u{044C}\u{0437}\u{044F} \u{0441}\u{043E}\u{0437}\u{0434}\u{0430}\u{0442}\u{044C} \u{0447}\u{0430}\u{0442} \u{0441} \u{0441}\u{0430}\u{043C}\u{0438}\u{043C} \u{0441}\u{043E}\u{0431}\u{043E}\u{0439}.");

        $existingChatId = $this->chats->findDirectChatId($creator->id, $targetUserId);

        if ($existingChatId) {
            return Chat::findOrFail($existingChatId);
        }

        $chat = DB::connection($this->chats->connectionName())->transaction(function () use ($creator, $targetUserId) {
            $chat = Chat::create([
                'type' => 'direct',
                'creator_id' => $creator->id,
            ]);

            DB::connection($this->chats->connectionName())->table('chat_participants')->insert([
                [
                    'chat_id' => $chat->id,
                    'user_id' => $creator->id,
                    'role' => 'member',
                    'joined_at' => now(),
                ],
                [
                    'chat_id' => $chat->id,
                    'user_id' => $targetUserId,
                    'role' => 'member',
                    'joined_at' => now(),
                ],
            ]);

            return $chat;
        });

        broadcast(new ChatListUpdated($creator->id));
        broadcast(new ChatListUpdated($targetUserId));

        return $chat;
    }
}
