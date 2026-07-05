<?php

use App\Http\Controllers\Chat\ChatIndexController;
use App\Http\Controllers\Chat\ChatItemsController;
use App\Http\Controllers\Chat\ChatSearchController;
use App\Http\Controllers\Chat\ChatShowController;
use App\Http\Controllers\Chat\DirectChatController;
use App\Http\Controllers\Message\DeleteMessageController;
use App\Http\Controllers\Message\ReadMessageController;
use App\Http\Controllers\Message\SendMessageController;
use App\Http\Controllers\Profile\ProfileController;
use App\Http\Controllers\User\UserStatusController;
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

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/users/status/heartbeat', [UserStatusController::class, 'heartbeat'])->name('users.status.heartbeat');
    Route::post('/users/status/offline', [UserStatusController::class, 'offline'])->name('users.status.offline');
    Route::get('/users/{user}/status', [UserStatusController::class, 'show'])->name('users.status.show');

    Route::get('/chats', ChatIndexController::class)->name('chats.index');
    Route::get('/chats/items', ChatItemsController::class)->name('chats.items');
    Route::get('/chats/search', ChatSearchController::class)->name('chats.search');
    Route::post('/chats/direct', [DirectChatController::class, 'store'])->name('chats.direct.store');

    // Страница конкретного чата (где рендерится наш Vue-компонент)
    Route::get('/chats/{chat}', ChatShowController::class)->name('chats.show');

    Route::post('/chats/{chat}/messages', [SendMessageController::class, 'store'])->name('chats.messages.store');
    Route::delete('/chats/{chat}/messages/{message}', DeleteMessageController::class)->name('chats.messages.destroy');
    Route::post('/chats/{chat}/read', [ReadMessageController::class, 'store'])->name('chats.read.store');
});

require __DIR__.'/auth.php';
