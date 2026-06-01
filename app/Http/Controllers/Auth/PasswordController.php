<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Обновляет пароль авторизованного пользователя после проверки текущего пароля.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        // Храним только hash пароля; исходный пароль из запроса не сохраняется.
        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back();
    }
}
