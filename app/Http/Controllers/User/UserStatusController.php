<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UserStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserStatusController extends Controller
{
    public function __construct(private readonly UserStatusService $statusService) {}

    /**
     * Обновляет online-статус текущего пользователя по heartbeat-запросу.
     */
    public function heartbeat(Request $request): JsonResponse
    {
        $this->statusService->ping($request->user()->id);

        return response()->json(['status' => 'success']);
    }

    /**
     * Принудительно переводит текущего пользователя в offline.
     */
    public function offline(Request $request): JsonResponse
    {
        $this->statusService->setOffline($request->user()->id);

        return response()->json(['status' => 'success']);
    }

    /**
     * Возвращает вычисленный статус конкретного пользователя для интерфейса чата.
     */
    public function show(User $user): JsonResponse
    {
        return response()->json($this->statusService->getStatus($user->id));
    }
}
