<?php

namespace App\Jobs\WhatsApp;

use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendWhatsAppMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly string $phoneNumber,
        public readonly string $message,
        public readonly ?string $mediaUrl = null,
        public readonly ?string $mediaType = null
    ) {
        $this->onQueue('whatsapp');
    }

    public function handle(WhatsAppService $whatsAppService): void
    {
        try {
            $success = $whatsAppService->sendMessage(
                $this->phoneNumber,
                $this->message,
                $this->mediaUrl,
                $this->mediaType
            );

            if (!$success) {
                Log::error('Failed to send WhatsApp message', [
                    'phone' => $this->phoneNumber,
                    'message' => $this->message,
                ]);
                $this->release(60);
            }
        } catch (\Throwable $e) {
            Log::error('Error sending WhatsApp message', [
                'phone' => $this->phoneNumber,
                'error' => $e->getMessage(),
            ]);
            $this->release(120);
        }
    }
}
