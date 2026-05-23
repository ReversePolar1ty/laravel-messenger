<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendMessageRequest;
use App\Models\Chat;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Str;

class SendMessageController extends Controller
{
    public function store(SendMessageRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        // 1. Создаем сообщение в MongoDB
        $message = Message::create([
            'chat_id'    => $validated['chat_id'],
            'user_id'    => $user?->id ?? 1,
            'sender_id'    => $user?->id ?? 1,
            'type'       => $validated['type'] ?? 'text',
            'text'       => $validated['text'] ?? null,
            // attachments и другие поля по необходимости
        ]);

        // 2. Обновляем информацию в MariaDB (денормализация для списка чатов)
        $chat = Chat::findOrFail($validated['chat_id']);


        $chat->update([
            // $message->_id или $message->id вернет строковый ObjectId (24 символа),
            // что идеально подходит под твою колонку string('last_message_id', 24)
            'last_message_id' => (string) $message->_id,
            'last_message_text' => Str::limit($message->text, 50),
            'last_message_at' => $message->created_at,
        ]);

        // 3. Задел на будущее для WebSockets и Redis
        // broadcast(new MessageSentEvent($message, $chat))->toOthers();
        // Redis::zadd('user_chats:' . $user->id, $message->created_at->timestamp, $chat->id);

        // Возвращаем успешный ответ с данными сообщения
        return response()->json([
            'success' => true,
            'data' => $message
        ], 201);
    }
}
