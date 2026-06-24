<?php

namespace App\Http\Controllers\Message;

use App\Http\Controllers\Controller;
use App\Http\Requests\SendMessageRequest;
use App\Services\Messages\MessageService;
use Illuminate\Http\JsonResponse;

class SendMessageController extends Controller
{
    public function __construct(private readonly MessageService $messages) {}

    /**
     * Принимает валидированное сообщение, сохраняет его и возвращает созданную запись.
     */
    public function store(SendMessageRequest $request): JsonResponse
    {
        // SendMessageRequest уже проверил формат данных и доступ пользователя к чату.
        $message = $this->messages->send($request->user(), $request->validated());

        // Фронтенд использует этот ответ, чтобы сразу добавить сообщение в локальный список.
        return response()->json([
            'success' => true,
            'data' => $message,
        ], 201);
    }
}
