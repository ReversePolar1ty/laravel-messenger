<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use App\Services\Chats\ChatQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatSearchController extends Controller
{
    public function __construct(private readonly ChatQueryService $chats) {}

    /**
     * Ищет пользователей по строке поиска и возвращает результат для live-search.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $search = trim((string) $request->query('search', ''));

        // В сервисе к найденным пользователям добавляется chat_id, если direct-чат уже существует.
        return response()->json([
            'users' => $this->chats->searchUsers($search, $user->id),
            'filters' => [
                'search' => $search,
            ],
        ]);
    }
}
