<?php

namespace App\Http\Controllers;

use App\Events\ChatListUpdated;
use App\Events\MessageRead;
use App\Models\Chat;
use App\Models\ChatRead;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReadMessageController extends Controller
{
    /**
     * Сохраняет последнюю прочитанную позицию пользователя в чате и рассылает обновления по WebSocket.
     */
    public function store(Request $request, Chat $chat): JsonResponse
    {
        $user = $request->user();
        $chatConnection = (new Chat())->getConnectionName();

        // UUID чата приходит из маршрута, поэтому доступ проверяем по таблице участников, а не доверяем URL.
        abort_unless(
            DB::connection($chatConnection)
                ->table('chat_participants')
                ->where('chat_id', $chat->id)
                ->where('user_id', $user->id)
                ->exists(),
            403
        );

        $validated = $request->validate([
            'last_read_message_id' => ['nullable', 'string', 'size:24'],
        ]);

        $message = $this->resolveMessage($chat->id, $validated['last_read_message_id'] ?? null);

        // Если в чате ещё нет сообщений или ID не найден, менять read-state нечего.
        if (! $message) {
            return response()->json([
                'success' => true,
                'data' => null,
            ]);
        }

        $lastReadMessageId = (string) ($message->_id ?? $message->id);
        $read = ChatRead::query()
            ->where('chat_id', $chat->id)
            ->where('user_id', $user->id)
            ->first();

        // Не откатываем прогресс чтения назад, если клиент прислал более старое сообщение.
        if ($read && $read->last_read_message_id && strcmp((string) $read->last_read_message_id, $lastReadMessageId) >= 0) {
            return response()->json([
                'success' => true,
                'data' => $read,
            ]);
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

        // MessageRead нужен открытой странице чата, ChatListUpdated - открытому списку чатов текущего пользователя.
        broadcast(new MessageRead(
            $chat->id,
            $user->id,
            $lastReadMessageId,
            $read->last_read_at->toISOString(),
        ))->toOthers();
        broadcast(new ChatListUpdated($user->id));

        return response()->json([
            'success' => true,
            'data' => $read,
        ]);
    }

    /**
     * Находит сообщение, до которого пользователь дочитал; без ID берём последнее сообщение чата.
     */
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
