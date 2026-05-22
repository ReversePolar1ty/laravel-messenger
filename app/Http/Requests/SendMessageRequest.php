<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendMessageRequest extends FormRequest
{

    public function authorize(): bool
    {
        // Проверка, состоит ли пользователь в этом чате.
        // Policy, например: return $this->user()->can('send-message', $this->chat_id);
        return true;
    }

    public function rules(): array
    {
        return [
            // Проверяем, что ID чата передан, это UUID и он существует в MariaDB
            'chat_id' => ['required', 'uuid', 'exists:chats,id'],

            // Тип сообщения (по умолчанию text, но может быть image, file и т.д.)
            'type'    => ['required', 'string', Rule::in(['text', 'image', 'file'])],

            // Текст обязателен, если нет вложений
            'text'    => ['required_if:type,text', 'nullable', 'string', 'max:5000'],

            // Валидация файлов (опционально, зависит от реализации на фронте)
//            'attachments'   => ['nullable', 'array', 'max:10'],
//            'attachments.*' => ['file', 'max:20480'], // Максимум 20 МБ на файл
        ];
    }

    protected function prepareForValidation(): void
    {
        // Устанавливаем дефолтный тип, если он не передан
        $this->merge([
            'type' => $this->type ?? 'text',
        ]);
    }
}
