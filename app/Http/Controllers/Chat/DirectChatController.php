<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use App\Services\Chats\DirectChatService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DirectChatController extends Controller
{
    public function __construct(private readonly DirectChatService $directChats) {}

    /**
     * Создаёт direct-чат с выбранным пользователем или открывает уже существующий.
     */
    public function store(Request $request): RedirectResponse
    {
        // Контроллер проверяет только HTTP-вход; поиск дублей и создание участников остаются в сервисе.
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $chat = $this->directChats->findOrCreate($request->user(), (int) $validated['user_id']);

        // Для пользователя результат одинаковый: он попадает на страницу нужного direct-чата.
        return redirect()->route('chats.show', $chat);
    }
}
