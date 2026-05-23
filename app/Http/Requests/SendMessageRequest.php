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
        if (! $this->user() || ! $this->chat_id) {
            return false;
        }

        return DB::connection((new Chat())->getConnectionName())
            ->table('chat_participants')
            ->where('chat_id', $this->chat_id)
            ->where('user_id', $this->user()->id)
            ->exists();
    }

    public function rules(): array
    {
        return [
            'chat_id' => ['required', 'uuid', 'exists:chats,id'],
            'type' => ['required', 'string', Rule::in(['text', 'image', 'file'])],
            'text' => ['required_if:type,text', 'nullable', 'string', 'max:5000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'type' => $this->type ?? 'text',
        ]);
    }
}
