<?php

namespace App\Actions\WhatsApp;

use App\Events\WhatsApp\WhatsAppAnalyticsObserved;
use App\Models\Setting;
use App\Models\WhatsAppLog;
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
        $mediaType = $extracted['media_type'] ?? null;
        $mediaUrl = $extracted['media_url'] ?? null;

        WhatsAppLog::logMessage('incoming', $from, (string) $message, 'received', [
            'media_type' => $mediaType,
            'media_url' => $mediaUrl,
        ]);

        Log::info('Received WhatsApp message', [
            'from' => $from,
            'message' => $message,
            'has_media' => !empty($mediaUrl),
            'media_type' => $mediaType,
        ]);

        $user = $this->autoReplyService->getUserByPhone($from);
        $session = WhatsAppSession::getOrCreate($from);
        $intent = $this->classifyAnalyticsIntent((string) $message);

        // Pass media info to dynamic reply service for OCR/voice processing (Phase 3+)
        $reply = $this->integrationRouter->routeIncomingMessage($from, $message, $user);

        if (!$reply) {
            $dynamicReply = $this->dynamicReplyService->getReply($message, $user, $session, $mediaUrl, $mediaType);

            if (!empty($dynamicReply)) {
                event(new WhatsAppAnalyticsObserved([
                    'occurred_at' => now(),
                    'direction' => 'incoming',
                    'phone_number' => $from,
                    'whatsapp_session_id' => $session->id,
                    'intent' => $intent,
                    'used_ai' => true,
                    'is_fallback' => $intent === 'unknown',
                    'meta' => [
                        'media_type' => $mediaType,
                        'has_media' => ! empty($mediaUrl),
                    ],
                ]));
                $this->processMultiFormatAction->execute($from, $dynamicReply);
                return;
            }
        }

        if (is_string($reply)) {
            event(new WhatsAppAnalyticsObserved([
                'occurred_at' => now(),
                'direction' => 'incoming',
                'phone_number' => $from,
                'whatsapp_session_id' => $session->id,
                'intent' => $intent,
                'used_ai' => false,
                'is_fallback' => false,
                'meta' => [
                    'media_type' => $mediaType,
                    'has_media' => ! empty($mediaUrl),
                ],
            ]));
            $this->processMultiFormatAction->execute($from, [
                'type' => 'text',
                'text' => $reply,
            ]);
        } elseif (is_array($reply)) {
            event(new WhatsAppAnalyticsObserved([
                'occurred_at' => now(),
                'direction' => 'incoming',
                'phone_number' => $from,
                'whatsapp_session_id' => $session->id,
                'intent' => $intent,
                'used_ai' => false,
                'is_fallback' => false,
                'meta' => [
                    'media_type' => $mediaType,
                    'has_media' => ! empty($mediaUrl),
                ],
            ]));
            $this->processMultiFormatAction->execute($from, $reply);
        }
    }

    private function classifyAnalyticsIntent(string $message): string
    {
        $m = strtolower($message);

        if (str_contains($m, 'tagihan') || str_contains($m, 'invoice') || str_contains($m, 'bayar') || str_contains($m, 'pembayaran')) {
            return 'tagihan';
        }
        if (str_contains($m, 'gangguan') || str_contains($m, 'internet mati') || str_contains($m, 'wifi mati') || str_contains($m, 'lemot')) {
            return 'gangguan';
        }
        if (str_contains($m, 'paket') || str_contains($m, 'harga internet') || str_contains($m, 'daftar paket')) {
            return 'paket_internet';
        }
        if (str_contains($m, 'voucher')) {
            return 'voucher';
        }
        if (str_contains($m, 'pasang') || str_contains($m, 'instalasi') || str_contains($m, 'daftar internet')) {
            return 'instalasi_baru';
        }
        if (str_contains($m, 'wedding') || str_contains($m, 'nikah') || str_contains($m, 'pernikahan') || str_contains($m, 'event')) {
            return 'wedding';
        }
        if (str_contains($m, 'cctv') || str_contains($m, 'kamera')) {
            return 'cctv';
        }

        return 'unknown';
    }

    private function extractMessage(array $data): ?array
    {
        // Format 1: Fonnte
        if (isset($data['data']['messages']) && is_array($data['data']['messages'])) {
            foreach ($data['data']['messages'] as $msg) {
                $extracted = $this->extractFromFonnteMessage($msg);
                if ($extracted) {
                    return $extracted;
                }
            }
        }

        // Format 2: Wablas
        if (isset($data['data']) && is_array($data['data'])) {
            foreach ($data['data'] as $msg) {
                $extracted = $this->extractFromWablasMessage($msg);
                if ($extracted) {
                    return $extracted;
                }
            }
        }

        // Format 3: Generic
        if (isset($data['message'])) {
            return [
                'phone' => $data['phone'] ?? $data['sender'] ?? $data['from'] ?? null,
                'message' => $data['message'] ?? '',
                'media_type' => $data['media_type'] ?? null,
                'media_url' => $data['media_url'] ?? null,
            ];
        }

        return null;
    }

    private function extractFromFonnteMessage(array $msg): ?array
    {
        $phone = $msg['phone'] ?? $msg['sender'] ?? $msg['from'] ?? null;
        if (!$phone) return null;

        $text = $msg['message'] ?? $msg['caption'] ?? '';
        $mediaType = $msg['type'] ?? null;
        $mediaUrl = $msg['url'] ?? $msg['media_url'] ?? null;

        return [
            'phone' => $phone,
            'message' => $text,
            'media_type' => $mediaType,
            'media_url' => $mediaUrl,
        ];
    }

    private function extractFromWablasMessage(array $msg): ?array
    {
        $phone = $msg['phone'] ?? $msg['sender'] ?? $msg['from'] ?? null;
        if (!$phone) return null;

        $text = $msg['message'] ?? $msg['caption'] ?? '';
        $mediaType = $msg['type'] ?? null;
        $mediaUrl = $msg['url'] ?? $msg['media_url'] ?? null;

        return [
            'phone' => $phone,
            'message' => $text,
            'media_type' => $mediaType,
            'media_url' => $mediaUrl,
        ];
    }
}
