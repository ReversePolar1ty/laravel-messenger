<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Показывает страницу регистрации.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Создаёт нового пользователя, отправляет событие регистрации и сразу авторизует его.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Событие запускает стандартные процессы Laravel, например отправку verification email.
        event(new Registered($user));

        // После регистрации пользователь сразу попадает в приложение без отдельного логина.
        Auth::login($user);

        return redirect(route('chats.index', absolute: false));
    }
}
