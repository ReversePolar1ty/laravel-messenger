<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\UserStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserStatusController extends Controller
{
    /**
     * Получает сервис, который хранит и вычисляет online/offline состояние пользователей.
     */
    public function __construct(protected UserStatusService $statusService) {}

    /**
     * Обновляет online-статус текущего пользователя по периодическому heartbeat-запросу.
     */
    public function heartbeat(Request $request): JsonResponse
    {
        $this->statusService->ping($request->user()->id);

        return response()->json(['status' => 'success']);
    }

    /**
     * Явно переводит пользователя в offline при logout или закрытии вкладки.
     */
    public function offline(Request $request): JsonResponse
    {
        $this->statusService->setOffline($request->user()->id);

        return response()->json(['status' => 'success']);
    }

    /**
     * Возвращает текущий статус конкретного пользователя для UI чата.
     */
    public function show(User $user): JsonResponse
    {
        return response()->json($this->statusService->getStatus($user->id));
    }
}
