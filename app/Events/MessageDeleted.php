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

    public function __construct(public array $messageData, public int|string $chatId)
    {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.' . $this->chatId),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'message' => $this->messageData,
        ];
    }
}
