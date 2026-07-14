<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $threadId,
        public array $message
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.thread.'.$this->threadId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'chat.message.sent';
    }
}

