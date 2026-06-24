<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use App\Services\Chats\ChatPresenter;
use App\Services\Chats\ChatQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatItemsController extends Controller
{
    public function __construct(
        private readonly ChatQueryService $chats,
        private readonly ChatPresenter $presenter,
    ) {}

    /**
     * Возвращает актуальный список чатов для фонового обновления интерфейса.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        // Формат ответа совпадает с props страницы чатов, чтобы фронтенд мог заменить список без перезагрузки.
        return response()->json([
            'chats' => $this->presenter->collection($this->chats->forUser($user->id), $user->id),
        ]);
    }
}
