<?php

namespace App\Services\Chats;

use App\Models\Chat;
use App\Models\ChatRead;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ChatQueryService
{
    /**
     * Возвращает имя соединения, на котором хранятся чаты и участники.
     */
    public function connectionName(): string
    {
        return (new Chat)->getConnectionName();
    }

    /**
     * Получает все чаты, где пользователь состоит участником.
     */
    public function forUser(int $userId): Collection
    {
        $chatConnection = $this->connectionName();

        // Участники лежат в MariaDB рядом с чатами, поэтому подзапрос выполняется на том же соединении.
        return Chat::query()
            ->whereIn('id', DB::connection($chatConnection)->table('chat_participants')
                ->select('chat_id')
                ->where('user_id', $userId))
            ->orderByDesc('last_message_at')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Загружает участников чата вместе с базовыми данными пользователей.
     */
    public function participants(string $chatId): Collection
    {
        // Join нужен только для данных списка участников; модели User здесь не изменяются.
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

    /**
     * Ищет пользователей по имени и добавляет ID direct-чата, если он уже существует.
     */
    public function searchUsers(string $search, int $currentUserId): Collection
    {
        if ($search === '') {
            return collect();
        }

        // Текущего пользователя исключаем, чтобы нельзя было начать direct-чат с самим собой из поиска.
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

    /**
     * Находит существующий direct-чат между двумя пользователями.
     */
    public function findDirectChatId(int $firstUserId, int $secondUserId): ?string
    {
        $chatConnection = $this->connectionName();

        // Сначала ищем чаты, где присутствуют оба пользователя.
        $chatIds = DB::connection($chatConnection)
            ->table('chat_participants')
            ->whereIn('user_id', [$firstUserId, $secondUserId])
            ->groupBy('chat_id')
            ->havingRaw('COUNT(DISTINCT user_id) = 2')
            ->pluck('chat_id');

        if ($chatIds->isEmpty()) {
            return null;
        }

        // Затем отсекаем групповые чаты, где эти два пользователя могут быть участниками вместе с другими.
        return DB::connection($chatConnection)
            ->table('chats')
            ->whereIn('id', $chatIds)
            ->where('type', 'direct')
            ->value('id');
    }

    /**
     * Проверяет, состоит ли пользователь в указанном чате.
     */
    public function isParticipant(string $chatId, int $userId): bool
    {
        return DB::connection($this->connectionName())
            ->table('chat_participants')
            ->where('chat_id', $chatId)
            ->where('user_id', $userId)
            ->exists();
    }

    /**
     * Возвращает ID всех участников чата для рассылки событий обновления.
     */
    public function participantIds(string $chatId): Collection
    {
        return DB::connection($this->connectionName())
            ->table('chat_participants')
            ->where('chat_id', $chatId)
            ->pluck('user_id');
    }

    /**
     * Возвращает состояния прочтения всех участников чата.
     */
    public function readStates(string $chatId): Collection
    {
        // Read-state хранится в MongoDB, поэтому здесь используем модель ChatRead, а не SQL-таблицу.
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
