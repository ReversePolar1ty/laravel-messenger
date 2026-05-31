<?php

use App\Models\Chat;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\DB;

Broadcast::channel('chat.{chatId}', function ($user, $chatId) {
    return DB::connection((new Chat())->getConnectionName())
        ->table('chat_participants')
        ->where('chat_id', $chatId)
        ->where('user_id', $user->id)
        ->exists();
});

Broadcast::channel('user.{userId}', function (User $user, int $userId) {
    return (int) $user->id === $userId;
});

Broadcast::channel('online', function (User $user) {
    // Если пользователь авторизован, возвращаем массив его данных для фронтенда
    return [
        'id' => $user->id,
        'name' => $user->name,
    ];
});
