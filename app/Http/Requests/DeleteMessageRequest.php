<?php

namespace App\Http\Requests;

use App\Models\Chat;
use App\Models\Message;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class DeleteMessageRequest extends FormRequest
{
    /**
     * Проверяет право на удаление до входа в контроллер.
     *
     * Удаление разрешено только автору сообщения, который остается участником чата.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $chat = $this->route('chat');
        $messageId = $this->route('message');

        if (! $user || ! $chat instanceof Chat || ! $messageId) {
            return false;
        }

        // Участники хранятся в MariaDB рядом с chat metadata, поэтому проверяем
        // доступ через SQL-соединение конкретной модели Chat.
        $isParticipant = DB::connection($chat->getConnectionName())
            ->table('chat_participants')
            ->where('chat_id', $chat->id)
            ->where('user_id', $user->id)
            ->exists();

        if (! $isParticipant) {
            return false;
        }

        // Сообщения лежат в MongoDB: дополнительно связываем message id с chat id,
        // чтобы нельзя было удалить свое сообщение из другого чата через URL.
        return Message::query()
            ->where('_id', $messageId)
            ->where('chat_id', $chat->id)
            ->where('sender_id', $user->id)
            ->where('is_deleted', '!=', true)
            ->exists();
    }

    public function rules(): array
    {
        // Все входные значения приходят из route parameters и проверяются в authorize().
        return [];
    }
}
