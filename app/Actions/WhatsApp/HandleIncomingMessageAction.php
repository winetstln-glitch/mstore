<?php

namespace App\Actions\WhatsApp;

use App\Models\Setting;
use App\Models\WhatsAppSession;
use App\Services\WhatsApp\WhatsAppAutoReplyService;
use App\Services\WhatsApp\WhatsAppDynamicReplyService;
use App\Services\WhatsApp\WhatsAppIntegrationRouter;
use Illuminate\Support\Facades\Log;

class HandleIncomingMessageAction
{
    public function __construct(
        private readonly WhatsAppAutoReplyService $autoReplyService,
        private readonly WhatsAppIntegrationRouter $integrationRouter,
        private readonly WhatsAppDynamicReplyService $dynamicReplyService,
        private readonly ProcessMultiFormatMessageAction $processMultiFormatAction
    ) {}

    public function execute(array $data): void
    {
        $autoreplyEnabled = Setting::getValue('whatsapp_autoreply_enabled', '1') == '1';

        if (!$autoreplyEnabled) {
            Log::info('Auto reply is disabled, skipping incoming message');
            return;
        }

        // Extract message from multiple formats
        $extracted = $this->extractMessage($data);

        if (!$extracted) {
            Log::warning('Could not extract message from webhook payload', ['payload' => $data]);
            return;
        }

        $from = $extracted['phone'];
        $message = $extracted['message'];

        Log::info('Received WhatsApp message', [
            'from' => $from,
            'message' => $message,
            'payload' => $data,
        ]);

        $user = $this->autoReplyService->getUserByPhone($from);
        $session = WhatsAppSession::getOrCreate($from);

        $reply = $this->integrationRouter->routeIncomingMessage($from, $message, $user);

        if (!$reply) {
            $dynamicReply = $this->dynamicReplyService->getReply($message, $user, $session);

            if (!empty($dynamicReply['text'])) {
                $this->processMultiFormatAction->execute($from, $dynamicReply);
                return;
            }
        }

        if (is_string($reply)) {
            $this->processMultiFormatAction->execute($from, [
                'type' => 'text',
                'text' => $reply,
            ]);
        } elseif (is_array($reply)) {
            $this->processMultiFormatAction->execute($from, $reply);
        }
    }

    private function extractMessage(array $data): ?array
    {
        // Format 1: Fonnte
        if (isset($data['data']['messages']) && is_array($data['data']['messages'])) {
            foreach ($data['data']['messages'] as $msg) {
                if (isset($msg['message'])) {
                    return [
                        'phone' => $msg['phone'] ?? $msg['sender'] ?? $msg['from'] ?? null,
                        'message' => $msg['message'],
                    ];
                }
            }
        }

        // Format 2: Wablas
        if (isset($data['data']) && is_array($data['data'])) {
            foreach ($data['data'] as $msg) {
                if (isset($msg['message'])) {
                    return [
                        'phone' => $msg['phone'] ?? $msg['sender'] ?? $msg['from'] ?? null,
                        'message' => $msg['message'],
                    ];
                }
            }
        }

        // Format 3: Generic
        if (isset($data['message'])) {
            return [
                'phone' => $data['phone'] ?? $data['sender'] ?? $data['from'] ?? null,
                'message' => $data['message'],
            ];
        }

        return null;
    }
}
