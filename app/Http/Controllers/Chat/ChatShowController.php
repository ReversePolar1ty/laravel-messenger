<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\Message;
use App\Services\Chats\ChatPresenter;
use App\Services\Chats\ChatQueryService;
use Inertia\Inertia;
use Inertia\Response;

class ChatShowController extends Controller
{
    public function __construct(
        private readonly ChatQueryService $chats,
        private readonly ChatPresenter $presenter,
    ) {}

    /**
     * Показывает страницу конкретного чата с сообщениями и состояниями прочтения.
     */
    public function __invoke(Chat $chat): Response
    {
        $user = request()->user();

        // ID чата приходит из URL, поэтому перед отдачей данных проверяем участие пользователя в чате.
        abort_unless($this->chats->isParticipant($chat->id, $user->id), 403);

        // Сообщения хранятся отдельно от метаданных чата и выводятся в хронологическом порядке.
        $messages = Message::where('chat_id', $chat->id)
            ->orderBy('created_at', 'asc')
            ->get();

        return Inertia::render('Chat/Show', [
            'chat' => $this->presenter->single($chat, $user->id, true),
            'messages' => $messages,
            'readStates' => $this->chats->readStates($chat->id),
        ]);
    }
}
