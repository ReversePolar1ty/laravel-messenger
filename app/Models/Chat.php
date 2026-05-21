<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MongoDB\Laravel\Eloquent\HybridRelations;

class Chat extends Model
{
    use HasUuids, HybridRelations;

    protected $connection = 'mariadb';

    protected $fillable = [
        'type',
        'title',
        'avatar_url',
        'creator_id',
        'last_message_id',
        'last_message_text',
        'last_message_at'
    ];

    protected $casts = [
        'last_message_at' => 'datetime'
    ];

    /**
     * Получить все сообщения чата из MongoDB
     */
    public function messages(): HasMany
    {
        // Возвращает стандартную связь, но запросы будут уходить в MongoDB
        return $this->hasMany(Message::class, 'chat_id', 'id');
    }
}
