<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ConfirmablePasswordController extends Controller
{
    /**
     * Показывает форму повторного ввода пароля для защищённых действий.
     */
    public function show(): Response
    {
        return Inertia::render('Auth/ConfirmPassword');
    }

    /**
     * Проверяет пароль текущего пользователя и запоминает время подтверждения в сессии.
     */
    public function store(Request $request): RedirectResponse
    {
        if (! Auth::guard('web')->validate([
            'email' => $request->user()->email,
            'password' => $request->password,
        ])) {
            // Ошибку привязываем к полю password, чтобы форма показала её рядом с вводом пароля.
            throw ValidationException::withMessages([
                'password' => __('auth.password'),
            ]);
        }

        // Laravel middleware password.confirm смотрит именно на этот timestamp.
        $request->session()->put('auth.password_confirmed_at', time());

        return redirect()->intended(route('chats.index', absolute: false));
    }
}
