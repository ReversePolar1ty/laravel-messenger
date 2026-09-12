<?php

namespace App\Http\Requests;

use App\Models\Chat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $chat = $this->route('chat');

        if (! $user || ! $chat instanceof Chat) {
            return false;
        }

        return DB::connection($chat->getConnectionName())
            ->table('chat_participants')
            ->where('chat_id', $chat->id)
            ->where('user_id', $user->id)
            ->exists();
    }

    public function rules(): array
    {
        $chatConnection = (new Chat)->getConnectionName();

        return [
            // chat_id всегда берётся из route {chat}, body игнорируется.
            'chat_id' => [
                'required',
                'uuid',
                Rule::exists($chatConnection.'.chats', 'id'),
            ],
            'type' => ['required', 'string', Rule::in(['text', 'image', 'file'])],
            'text' => ['required_if:type,text', 'nullable', 'string', 'max:5000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $chat = $this->route('chat');

        $this->merge([
            'chat_id' => $chat instanceof Chat ? $chat->id : null,
            'type' => $this->type ?? 'text',
        ]);
    }
}
