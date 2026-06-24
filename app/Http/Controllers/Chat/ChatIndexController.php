<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use App\Services\Chats\ChatPresenter;
use App\Services\Chats\ChatQueryService;
use Inertia\Inertia;
use Inertia\Response;

class ChatIndexController extends Controller
{
    public function __construct(
        private readonly ChatQueryService $chats,
        private readonly ChatPresenter $presenter,
    ) {}

    /**
     * Показывает страницу со списком чатов текущего пользователя и результатами поиска людей.
     */
    public function __invoke(): Response
    {
        $user = request()->user();
        $search = trim((string) request('search', ''));

        // Сервис возвращает доменные данные, а presenter приводит чаты к формату, который ожидает Vue.
        return Inertia::render('Chat/Index', [
            'chats' => $this->presenter->collection($this->chats->forUser($user->id), $user->id),
            'users' => $this->chats->searchUsers($search, $user->id),
            'filters' => [
                'search' => $search,
            ],
        ]);
    }
}
