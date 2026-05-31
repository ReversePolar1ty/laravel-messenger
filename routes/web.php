<?php

use App\Http\Controllers\ChatController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReadMessageController;
use App\Http\Controllers\SendMessageController;
use App\Http\Controllers\UserStatusController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/users/status/heartbeat', [UserStatusController::class, 'heartbeat'])->name('users.status.heartbeat');
    Route::post('/users/status/offline', [UserStatusController::class, 'offline'])->name('users.status.offline');
    Route::get('/users/{user}/status', [UserStatusController::class, 'show'])->name('users.status.show');

    Route::get('/chats', [ChatController::class, 'index'])->name('chats.index');
    Route::get('/chats/items', [ChatController::class, 'items'])->name('chats.items');
    Route::get('/chats/search', [ChatController::class, 'search'])->name('chats.search');
    Route::post('/chats/direct', [ChatController::class, 'store'])->name('chats.direct.store');

    // Страница конкретного чата (где рендерится наш Vue-компонент)
    Route::get('/chats/{chat}', [ChatController::class, 'show'])->name('chats.show');

    Route::post('/chats/{chat}/messages', [SendMessageController::class, 'store'])->name('chats.messages.store');
    Route::post('/chats/{chat}/read', [ReadMessageController::class, 'store'])->name('chats.read.store');
});

require __DIR__.'/auth.php';
