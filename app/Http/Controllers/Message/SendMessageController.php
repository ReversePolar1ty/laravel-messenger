<?php

namespace App\Http\Controllers\Message;

use App\Http\Controllers\Controller;
use App\Http\Requests\SendMessageRequest;
use App\Models\Chat;
use App\Services\Messages\MessageService;
use Illuminate\Http\JsonResponse;

class SendMessageController extends Controller
{
    public function __construct(private readonly MessageService $messages) {}

    /**
     * Принимает валидированное сообщение, сохраняет его и возвращает созданную запись.
     */
    public function store(SendMessageRequest $request, Chat $chat): JsonResponse
    {
        // chat_id всегда из route {chat}; body не может подменить целевой чат.
        $message = $this->messages->send($request->user(), [
            ...$request->validated(),
            'chat_id' => $chat->id,
        ]);

        // Фронтенд использует этот ответ, чтобы сразу добавить сообщение в локальный список.
        return response()->json([
            'success' => true,
            'data' => $message,
        ], 201);
    }
}
