<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Http\Requests\SendMessageRequest;
use App\Models\Chat;
use App\Models\Message;
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
        broadcast(new MessageSent($message->toArray(), $validated['chat_id']))->toOthers();

        // Фронтенд сразу добавляет это сообщение в локальный список без полной перезагрузки.
        return response()->json([
            'success' => true,
            'data' => $message,
        ], 201);
    }
}
