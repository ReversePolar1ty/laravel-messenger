<?php

namespace App\Services\Chats;

use App\Models\Chat;
use App\Models\ChatRead;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ChatQueryService
{
    public function connectionName(): string
    {
        return (new Chat)->getConnectionName();
    }

    public function forUser(int $userId): Collection
    {
        $chatConnection = $this->connectionName();

        return Chat::query()
            ->whereIn('id', DB::connection($chatConnection)->table('chat_participants')
                ->select('chat_id')
                ->where('user_id', $userId))
            ->orderByDesc('last_message_at')
            ->orderByDesc('created_at')
            ->get();
    }

    public function participants(string $chatId): Collection
    {
        return DB::connection($this->connectionName())
            ->table('chat_participants')
            ->join('users', 'users.id', '=', 'chat_participants.user_id')
            ->where('chat_participants.chat_id', $chatId)
            ->orderBy('users.name')
            ->get([
                'users.id',
                'users.name',
                'users.email',
                'chat_participants.role',
            ]);
    }

    public function searchUsers(string $search, int $currentUserId): Collection
    {
        if ($search === '') {
            return collect();
        }

        return User::query()
            ->where('id', '!=', $currentUserId)
            ->where('name', 'like', "%{$search}%")
            ->orderBy('name')
            ->limit(10)
            ->get(['id', 'name', 'email'])
            ->map(fn (User $foundUser) => [
                'id' => $foundUser->id,
                'name' => $foundUser->name,
                'email' => $foundUser->email,
                'chat_id' => $this->findDirectChatId($currentUserId, $foundUser->id),
            ]);
    }

    public function findDirectChatId(int $firstUserId, int $secondUserId): ?string
    {
        $chatConnection = $this->connectionName();
        $chatIds = DB::connection($chatConnection)
            ->table('chat_participants')
            ->whereIn('user_id', [$firstUserId, $secondUserId])
            ->groupBy('chat_id')
            ->havingRaw('COUNT(DISTINCT user_id) = 2')
            ->pluck('chat_id');

        if ($chatIds->isEmpty()) {
            return null;
        }

        return DB::connection($chatConnection)
            ->table('chats')
            ->whereIn('id', $chatIds)
            ->where('type', 'direct')
            ->value('id');
    }

    public function isParticipant(string $chatId, int $userId): bool
    {
        return DB::connection($this->connectionName())
            ->table('chat_participants')
            ->where('chat_id', $chatId)
            ->where('user_id', $userId)
            ->exists();
    }

    public function participantIds(string $chatId): Collection
    {
        return DB::connection($this->connectionName())
            ->table('chat_participants')
            ->where('chat_id', $chatId)
            ->pluck('user_id');
    }

    public function readStates(string $chatId): Collection
    {
        return ChatRead::query()
            ->where('chat_id', $chatId)
            ->get(['chat_id', 'user_id', 'last_read_message_id', 'last_read_at'])
            ->map(fn (ChatRead $read) => [
                'chat_id' => $read->chat_id,
                'user_id' => $read->user_id,
                'last_read_message_id' => $read->last_read_message_id,
                'last_read_at' => $read->last_read_at,
            ]);
    }
}
