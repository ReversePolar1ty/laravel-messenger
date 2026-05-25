<?php

use App\Models\Chat;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\DB;

Broadcast::channel('chat.{chatId}', function ($user, $chatId) {
    return DB::connection((new Chat())->getConnectionName())
        ->table('chat_participants')
        ->where('chat_id', $chatId)
        ->where('user_id', $user->id)
        ->exists();
});
