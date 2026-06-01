<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\UserStatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Получает сервис статусов, чтобы синхронизировать online/offline состояние при login/logout.
     */
    public function __construct(private readonly UserStatusService $statusService) {}

    /**
     * Показывает страницу входа и передаёт флаги для UI восстановления пароля.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Обрабатывает вход: проверяет логин/пароль, обновляет сессию и отмечает пользователя онлайн.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        // После успешного логина меняем ID сессии, чтобы закрыть session fixation.
        $request->session()->regenerate();

        // Снимаем блокировку logout и сразу продлеваем online-статус пользователя.
        $this->statusService->clearLogoutBlock($request->user()->id);
        $this->statusService->ping($request->user()->id);

        return redirect()->intended(route('chats.index', absolute: false));
    }

    /**
     * Завершает сессию пользователя и переводит его в offline.
     */
    public function destroy(Request $request): RedirectResponse
    {
        if ($request->user()) {
            $this->statusService->setOffline($request->user()->id);
        }

        Auth::guard('web')->logout();

        // Полностью инвалидируем старую сессию и CSRF-токен после logout.
        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
