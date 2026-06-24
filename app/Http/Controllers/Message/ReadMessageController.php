<?php

namespace App\Http\Controllers\Message;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Services\Chats\ChatQueryService;
use App\Services\Messages\ChatReadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReadMessageController extends Controller
{
    public function __construct(
        private readonly ChatQueryService $chats,
        private readonly ChatReadService $reads,
    ) {}

    /**
     * Сохраняет последнюю прочитанную позицию пользователя в чате.
     */
    public function store(Request $request, Chat $chat): JsonResponse
    {
        $user = $request->user();

        // Нельзя обновлять read-state для чата, в котором пользователь не состоит.
        abort_unless($this->chats->isParticipant($chat->id, $user->id), 403);

        // Если ID не передан, сервис сам возьмёт последнее сообщение чата.
        $validated = $request->validate([
            'last_read_message_id' => ['nullable', 'string', 'size:24'],
        ]);

        // Сервис не откатывает прогресс чтения назад и рассылает нужные WebSocket-события.
        return response()->json([
            'success' => true,
            'data' => $this->reads->markRead($chat, $user, $validated['last_read_message_id'] ?? null),
        ]);
    }
}
