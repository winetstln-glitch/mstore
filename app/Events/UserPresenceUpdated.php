<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserPresenceUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $userId,
        public string $name,
        public ?string $roleName,
        public bool $online,
        public ?string $lastSeenAt
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('presence.dashboard'),
            new PrivateChannel('presence.user.'.$this->userId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'presence.updated';
    }
}

