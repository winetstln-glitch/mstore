<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatTyping implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $threadId,
        public int $senderId,
        public string $senderName
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.thread.'.$this->threadId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'chat.typing';
    }
}

