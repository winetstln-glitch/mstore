<?php

namespace App\Actions\WhatsApp;

use App\Events\WhatsApp\WhatsAppAnalyticsObserved;
use App\Models\Setting;
use App\Models\WhatsAppLog;
use App\Models\WhatsAppSession;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppGroup;
use App\Services\WhatsApp\WhatsAppAutoReplyService;
use App\Services\WhatsApp\WhatsAppDynamicReplyService;
use App\Services\WhatsApp\WhatsAppIntegrationRouter;
use App\Services\WhatsApp\WhatsAppIntentService;
use Illuminate\Support\Facades\Log;

class HandleIncomingMessageAction
{
    public function __construct(
        private readonly WhatsAppAutoReplyService $autoReplyService,
        private readonly WhatsAppIntegrationRouter $integrationRouter,
        private readonly WhatsAppDynamicReplyService $dynamicReplyService,
        private readonly ProcessMultiFormatMessageAction $processMultiFormatAction,
        private readonly WhatsAppIntentService $intentService
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
            Log::warning('Could not extract message from webhook payload', [
                'keys' => array_slice(array_keys($data), 0, 25),
            ]);
            return;
        }

        $from = $extracted['phone'];
        $message = $extracted['message'];
        $mediaType = $extracted['media_type'] ?? null;
        $mediaUrl = $extracted['media_url'] ?? null;
        $isGroup = $extracted['is_group'] ?? false;
        $groupId = $extracted['group_id'] ?? null;
        $groupName = $extracted['group_name'] ?? null;
        $senderType = $extracted['sender_type'] ?? 'customer';

        // Handle group messages
        if ($isGroup && $groupId) {
            $group = WhatsAppGroup::getOrCreate($groupId, $groupName);
            if (!$group->bot_enabled) {
                Log::info('Bot disabled for group, skipping reply', ['group_id' => $groupId]);
                // Still log the message
                WhatsAppLog::logMessage('incoming', $from, (string) $message, 'received', [
                    'media_type' => $mediaType,
                    'media_url' => $mediaUrl,
                    'is_group' => true,
                    'group_id' => $groupId,
                    'sender_type' => $senderType,
                ]);
                return;
            }
        }

        // Skip processing if sender is agent, bot, or system
        if (in_array($senderType, ['agent', 'bot', 'system'])) {
            Log::info('Skipping message from non-customer sender', [
                'sender_type' => $senderType,
                'phone' => $this->maskPhone($from)
            ]);
            WhatsAppLog::logMessage('incoming', $from, (string) $message, 'received', [
                'media_type' => $mediaType,
                'media_url' => $mediaUrl,
                'is_group' => $isGroup,
                'group_id' => $groupId,
                'sender_type' => $senderType,
            ]);
            return;
        }

        // Classify intent
        $intentResult = $this->intentService->classifyIntent((string) $message);
        $intent = $intentResult['intent'];
        $confidence = $intentResult['confidence_score'];
        $normalizedMessage = $intentResult['normalized_message'];

        Log::info('Processing WhatsApp message', [
            'from' => $this->maskPhone($from),
            'is_group' => $isGroup,
            'group_id' => $groupId,
            'sender_type' => $senderType,
            'intent' => $intent,
            'confidence_score' => $confidence,
            'normalized_message' => $normalizedMessage,
            'has_media' => !empty($mediaUrl),
            'media_type' => $mediaType,
        ]);

        $user = $this->autoReplyService->getUserByPhone($from);
        $session = WhatsAppSession::getOrCreate($from);
        $conversation = WhatsAppConversation::getOrCreate($from, $isGroup, $groupId);
        $conversation->update([
            'last_intent' => $intent,
            'confidence_score' => $confidence,
            'sender_type' => $senderType,
        ]);

        // Log message with intent and group info
        WhatsAppLog::logMessage('incoming', $from, (string) $message, 'received', [
            'media_type' => $mediaType,
            'media_url' => $mediaUrl,
            'is_group' => $isGroup,
            'group_id' => $groupId,
            'intent' => $intent,
            'confidence_score' => $confidence,
            'normalized_message' => $normalizedMessage,
            'sender_type' => $senderType,
        ]);

        // Human takeover if confidence < 70% or intent unknown
        if ($confidence < 70 || $intent === 'unknown') {
            Log::info('Low confidence or unknown intent, triggering human takeover', [
                'from' => $this->maskPhone($from),
                'confidence_score' => $confidence,
                'intent' => $intent,
                'takeover_reason' => $confidence < 70 ? 'low_confidence' : 'unknown_intent'
            ]);
            $conversation->update([
                'status' => 'waiting_cs',
                'takeover_reason' => $confidence < 70 ? 'low_confidence' : 'unknown_intent',
            ]);
            // Send fallback message or don't reply
            return;
        }

        // Get reply for intent
        $intentReply = $this->intentService->getReplyForIntent($intent);
        if ($intentReply) {
            Log::info('Using intent-specific reply', ['intent' => $intent]);
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
                    'confidence_score' => $confidence,
                ],
            ]));
            $this->processMultiFormatAction->execute($from, [
                'type' => 'text',
                'text' => $intentReply,
                'is_group' => $isGroup,
                'group_id' => $groupId,
            ]);
            return;
        }

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
                        'confidence_score' => $confidence,
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
                    'confidence_score' => $confidence,
                ],
            ]));
            $this->processMultiFormatAction->execute($from, [
                'type' => 'text',
                'text' => $reply,
                'is_group' => $isGroup,
                'group_id' => $groupId,
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
                    'confidence_score' => $confidence,
                ],
            ]));
            $this->processMultiFormatAction->execute($from, array_merge($reply, [
                'is_group' => $isGroup,
                'group_id' => $groupId,
            ]));
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

        // Format 3: Generic (including group message format from user)
        if (isset($data['message'])) {
            $isGroup = isset($data['isGroup']) ? (bool)$data['isGroup'] : false;
            return [
                'phone' => $data['phone'] ?? $data['sender'] ?? $data['from'] ?? null,
                'message' => $data['message'] ?? '',
                'media_type' => $data['media_type'] ?? null,
                'media_url' => $data['media_url'] ?? null,
                'is_group' => $isGroup,
                'group_id' => $isGroup ? ($data['group']['group_id'] ?? $data['group_id'] ?? null) : null,
                'group_name' => $isGroup ? ($data['group']['subject'] ?? $data['group_name'] ?? null) : null,
                'sender_type' => $data['sender_type'] ?? 'customer',
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
        $isGroup = isset($msg['isGroup']) ? (bool)$msg['isGroup'] : false;

        return [
            'phone' => $phone,
            'message' => $text,
            'media_type' => $mediaType,
            'media_url' => $mediaUrl,
            'is_group' => $isGroup,
            'group_id' => $isGroup ? ($msg['group']['group_id'] ?? $msg['group_id'] ?? null) : null,
            'group_name' => $isGroup ? ($msg['group']['subject'] ?? $msg['group_name'] ?? null) : null,
            'sender_type' => $msg['sender_type'] ?? 'customer',
        ];
    }

    private function extractFromWablasMessage(array $msg): ?array
    {
        $phone = $msg['phone'] ?? $msg['sender'] ?? $msg['from'] ?? null;
        if (!$phone) return null;

        $text = $msg['message'] ?? $msg['caption'] ?? '';
        $mediaType = $msg['type'] ?? null;
        $mediaUrl = $msg['url'] ?? $msg['media_url'] ?? null;
        $isGroup = isset($msg['isGroup']) ? (bool)$msg['isGroup'] : false;

        return [
            'phone' => $phone,
            'message' => $text,
            'media_type' => $mediaType,
            'media_url' => $mediaUrl,
            'is_group' => $isGroup,
            'group_id' => $isGroup ? ($msg['group']['group_id'] ?? $msg['group_id'] ?? null) : null,
            'group_name' => $isGroup ? ($msg['group']['subject'] ?? $msg['group_name'] ?? null) : null,
            'sender_type' => $msg['sender_type'] ?? 'customer',
        ];
    }

    private function maskPhone(?string $phone): string
    {
        $p = preg_replace('/\s+/', '', (string) $phone);
        if ($p === '') {
            return '';
        }
        if (strlen($p) <= 4) {
            return str_repeat('*', strlen($p));
        }
        return str_repeat('*', max(0, strlen($p) - 4)) . substr($p, -4);
    }
}
