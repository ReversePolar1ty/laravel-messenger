<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model as MongoModel;

class ChatRead extends MongoModel
{
    protected $connection = 'mongodb';
    protected $collection = 'chat_reads';

    protected $fillable = [
        'chat_id',
        'user_id',
        'last_read_message_id',
        'last_read_at',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'last_read_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
