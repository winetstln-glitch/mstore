<?php

namespace App\Jobs\WhatsApp;

use App\Actions\WhatsApp\HandleIncomingMessageAction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessIncomingWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly array $payload
    ) {
        $this->onQueue('whatsapp');
    }

    public function handle(HandleIncomingMessageAction $action): void
    {
        try {
            $action->execute($this->payload);
        } catch (\Throwable $e) {
            Log::error('Error processing WhatsApp webhook', [
                'keys' => array_slice(array_keys($this->payload), 0, 25),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
