<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatMessagesRead implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  array<int>  $messageIds
     */
    public function __construct(
        public int $threadId,
        public int $readerId,
        public array $messageIds,
        public string $readAt
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.thread.'.$this->threadId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'chat.messages.read';
    }
}

