<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\UserStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserStatusController extends Controller
{
    public function __construct(protected UserStatusService $statusService) {}

    /**
     * Обновление статуса "Онлайн" (Heartbeat)
     */
    public function heartbeat(Request $request): JsonResponse
    {
        $this->statusService->ping($request->user()->id);

        return response()->json(['status' => 'success']);
    }

    /**
     * Явное переключение в офлайн (при выходе или закрытии вкладки)
     */
    public function offline(Request $request): JsonResponse
    {
        $this->statusService->setOffline($request->user()->id);

        return response()->json(['status' => 'success']);
    }

    public function show(User $user): JsonResponse
    {
        return response()->json($this->statusService->getStatus($user->id));
    }
}
