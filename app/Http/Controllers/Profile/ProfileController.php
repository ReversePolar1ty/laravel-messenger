<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
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
     * Обновляет имя и email пользователя.
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
     * Удаляет аккаунт после подтверждения текущего пароля.
     */
    public function destroy(Request $request): RedirectResponse
    {
        // current_password проверяет пароль текущего аутентифицированного пользователя.
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        // Перед удалением явно выключаем online-статус, чтобы presence не показывал удалённого пользователя.
        $this->statusService->setOffline($user->id);

        Auth::logout();

        $user->delete();

        // После удаления аккаунта старая сессия и CSRF-токен больше не должны использоваться.
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
