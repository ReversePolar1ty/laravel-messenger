<?php

namespace App\Http\Requests;

use App\Models\Chat;
use App\Models\Message;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class DeleteMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $chat = $this->route('chat');
        $messageId = $this->route('message');

        if (! $user || ! $chat instanceof Chat || ! $messageId) {
            return false;
        }

        $isParticipant = DB::connection($chat->getConnectionName())
            ->table('chat_participants')
            ->where('chat_id', $chat->id)
            ->where('user_id', $user->id)
            ->exists();

        if (! $isParticipant) {
            return false;
        }

        return Message::query()
            ->where('_id', $messageId)
            ->where('chat_id', $chat->id)
            ->where('sender_id', $user->id)
            ->where('is_deleted', '!=', true)
            ->exists();
    }

    public function rules(): array
    {
        return [];
    }
}
