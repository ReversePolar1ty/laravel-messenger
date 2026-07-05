<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageDeleted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Событие для открытых страниц чата: клиент получает soft-deleted сообщение
     * и убирает его из локального списка без полной перезагрузки Inertia-страницы.
     */
    public function __construct(public array $messageData, public int|string $chatId)
    {
    }

    /**
     * Рассылаем событие только участникам конкретного приватного канала чата.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.' . $this->chatId),
        ];
    }

    /**
     * Сохраняем payload совместимым с MessageSent: фронтенд читает поле message.
     */
    public function broadcastWith(): array
    {
        return [
            'message' => $this->messageData,
        ];
    }
}
