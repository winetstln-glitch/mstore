<?php

namespace App\Notifications;

use App\Models\ChatMessage;
use App\Models\ChatThread;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ChatMessageNotification extends Notification
{
    use Queueable;

    public function __construct(
        public ChatThread $thread,
        public ChatMessage $message,
        public string $senderName
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'chat_message',
            'thread_id' => $this->thread->id,
            'message_id' => $this->message->id,
            'subject' => 'Pesan baru dari '.$this->senderName,
            'message' => mb_strimwidth((string) $this->message->body, 0, 120, '...'),
            'sender_name' => $this->senderName,
            'url' => route('chat.index', ['thread' => $this->thread->id]),
        ];
    }
}

