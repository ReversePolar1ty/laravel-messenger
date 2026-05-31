<?php

namespace App\Http\Controllers;

use App\Events\ChatListUpdated;
use App\Events\MessageSent;
use App\Events\MessageRead;
use App\Http\Requests\SendMessageRequest;
use App\Models\Chat;
use App\Models\ChatRead;
use App\Models\Message;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class SendMessageController extends Controller
{
    public function store(SendMessageRequest $request): JsonResponse
    {
        // SendMessageRequest уже проверил данные и то, что пользователь состоит в чате.
        $validated = $request->validated();
        $user = $request->user();

        // Основное тело сообщения пишем в MongoDB: там хранится история сообщений.
        $message = Message::create([
            'chat_id' => $validated['chat_id'],
            'user_id' => $user->id,
            'sender_id' => $user->id,
            'type' => $validated['type'] ?? 'text',
            'text' => $validated['text'] ?? null,
            // attachments и другие поля можно добавить позже, когда появится загрузка файлов.
        ]);

        // В MariaDB обновляем только денормализованные поля для быстрого списка чатов.
        $chat = Chat::findOrFail($validated['chat_id']);

        $chat->update([
            // MongoDB ObjectId сохраняем строкой, чтобы связать чат с последним сообщением.
            'last_message_id' => (string) $message->_id,
            'last_message_text' => Str::limit($message->text, 50),
            'last_message_at' => $message->created_at,
        ]);

        // Транслируем событие всем участникам чата, кроме отправителя.
        $read = ChatRead::query()->updateOrCreate(
            [
                'chat_id' => $chat->id,
                'user_id' => $user->id,
            ],
            [
                'last_read_message_id' => (string) $message->_id,
                'last_read_at' => now(),
            ]
        );

        broadcast(new MessageRead(
            $chat->id,
            $user->id,
            (string) $message->_id,
            $read->last_read_at->toISOString(),
        ))->toOthers();

        broadcast(new MessageSent($message->toArray(), $validated['chat_id']))->toOthers();

        DB::connection((new Chat())->getConnectionName())
            ->table('chat_participants')
            ->where('chat_id', $chat->id)
            ->pluck('user_id')
            ->each(fn ($userId) => broadcast(new ChatListUpdated((int) $userId)));

        // Фронтенд сразу добавляет это сообщение в локальный список без полной перезагрузки.
        return response()->json([
            'success' => true,
            'data' => $message,
        ], 201);
    }
}
