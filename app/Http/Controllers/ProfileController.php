<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Services\UserStatusService;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Получает сервис статусов, чтобы перед удалением аккаунта явно перевести пользователя в offline.
     */
    public function __construct(private readonly UserStatusService $statusService) {}

    /**
     * Показывает страницу редактирования профиля текущего пользователя.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
        ]);
    }

    /**
     * Обновляет имя/email пользователя и сбрасывает верификацию, если email изменился.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        // При смене email Laravel должен заново подтвердить адрес.
        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Удаляет аккаунт после проверки пароля и полностью завершает текущую сессию.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        // Перед удалением явно фиксируем offline-статус, чтобы presence не показывал удалённого пользователя онлайн.
        $this->statusService->setOffline($user->id);

        Auth::logout();

        $user->delete();

        // Инвалидация сессии и CSRF-токена защищает от повторного использования старой авторизации.
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
