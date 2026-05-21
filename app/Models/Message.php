<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MongoDB\Laravel\Eloquent\Model as MongoModel;

class Message extends MongoModel
{
    protected $connection = 'mongodb';
    protected $collection = 'messenger_messages';

    protected $fillable = [
        'chat_id',
        'sender_id',
        'type',
        'text',
        'attachments',
        'is_edited',
        'is_deleted'
    ];

    // Автоматическое приведение типов для вложений и флагов
    protected $casts = [
        'attachments' => AsArrayObject::class, // Сохраняется как JSON-массив в Mongo
        'is_edited'   => 'boolean',
        'is_deleted'  => 'boolean',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
    ];

    // Значения по умолчанию для новых документов
    protected $attributes = [
        'type' => 'text',
        'attachments' => [],
        'is_edited' => false,
        'is_deleted' => false,
    ];

    /**
     * Связь с создателем сообщения (из MariaDB)
     */
    public function sender(): BelongsTo
    {
        // Пакет mongodb/laravel сам поймет, что User находится на дефолтном (MariaDB) соединении
        return $this->belongsTo(User::class, 'sender_id', 'id');
    }

    /**
     * Связь с чатом (из MariaDB)
     */
    public function chat(): BelongsTo
    {
        return $this->belongsTo(Chat::class, 'chat_id', 'id');
    }

}
