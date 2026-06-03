<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\WhatsAppMenu;
use App\Models\WhatsAppLog;
use App\Services\AiService;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WhatsAppWebhookController extends Controller
{
    protected $whatsappService;
    protected $aiService;

    public function __construct(WhatsAppService $whatsappService, AiService $aiService)
    {
        $this->whatsappService = $whatsappService;
        $this->aiService = $aiService;
    }

    /**
     * Handle incoming WhatsApp messages from webhook
     */
    public function handle(Request $request)
    {
        // Verify token first (optional, for security)
        $verifyToken = config('services.whatsapp.verify_token');
        if ($verifyToken && $request->input('verify_token') !== $verifyToken) {
            return response()->json(['error' => 'Invalid verify token'], 403);
        }

        try {
            $payload = $request->all();
            Log::info('WhatsApp Webhook Received', $payload);

            // Try to extract message from different provider formats (Fonnte, etc.)
            $messageData = $this->extractMessage($payload);
            
            if (!$messageData) {
                return response()->json(['status' => 'no_message']);
            }

            $phone = $messageData['phone'];
            $message = $messageData['message'];
            $isGroup = $messageData['is_group'];

            // Log incoming message
            WhatsAppLog::logMessage('incoming', $phone, $message, 'delivered', $payload);

            // Process the message
            $response = $this->processIncomingMessage($phone, $message, $isGroup);

            if ($response) {
                // Send reply back
                $this->whatsappService->sendMessage($phone, $response);
            }

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('WhatsApp Webhook Error: ' . $e->getMessage(), ['exception' => $e]);
            
            // Log error to WhatsAppLog
            if (isset($phone)) {
                WhatsAppLog::logMessage('incoming', $phone, $request->getContent() ?? 'error', 'failed', $payload, $e->getMessage());
            }
            
            return response()->json(['error' => 'Server error'], 500);
        }
    }

    /**
     * Extract message details from provider payload
     */
    private function extractMessage(array $payload): ?array
    {
        // Fonnte format
        if (isset($payload['data']['messages']) && is_array($payload['data']['messages'])) {
            foreach ($payload['data']['messages'] as $msg) {
                if (isset($msg['message'])) {
                    return [
                        'phone' => $msg['phone'] ?? $msg['sender'] ?? null,
                        'message' => $msg['message'],
                        'is_group' => isset($msg['is_group']) ? (bool)$msg['is_group'] : false,
                    ];
                }
            }
        }

        // Generic format
        if (isset($payload['message']) && isset($payload['phone'])) {
            return [
                'phone' => $payload['phone'],
                'message' => $payload['message'],
                'is_group' => isset($payload['is_group']) ? (bool)$payload['is_group'] : false,
            ];
        }

        return null;
    }

    /**
     * Process incoming message and find appropriate reply
     */
    private function processIncomingMessage(string $phone, string $message, bool $isGroup): ?string
    {
        $message = trim(Str::lower($message));

        // Check if autoreply is enabled
        $autoreplyEnabled = Setting::getValue('whatsapp_autoreply_enabled', '1') == '1';
        if (!$autoreplyEnabled) {
            return null;
        }

        // First, check WhatsAppMenu for matching keywords
        $menus = WhatsAppMenu::active()
            ->orderBy('priority', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($menus as $menu) {
            $keyword = Str::lower($menu->keyword);
            
            // Exact match
            if ($message === $keyword) {
                $menu->incrementHitCount();
                return $this->renderMenuReply($menu);
            }

            // Fuzzy match (if enabled)
            if ($menu->enable_fuzzy_match) {
                similar_text($message, $keyword, $percent);
                if ($percent >= 70 || Str::contains($message, $keyword)) {
                    $menu->incrementHitCount();
                    return $this->renderMenuReply($menu);
                }
            }
        }

        // If no menu matches, try AI service for general queries
        $aiResponse = $this->aiService->processChat($message);
        return $this->renderAiResponse($aiResponse);
    }

    /**
     * Render AI response to WhatsApp-friendly format
     */
    private function renderAiResponse($aiResponse): string
    {
        if (is_string($aiResponse)) {
            // Strip HTML tags for WhatsApp
            return strip_tags($aiResponse, '<b><i><u><br><ol><ul><li>');
        }

        if (is_array($aiResponse)) {
            $output = '';
            
            if (isset($aiResponse['title'])) {
                $output .= "*{$aiResponse['title']}*\n";
            }

            if (isset($aiResponse['items']) && is_array($aiResponse['items'])) {
                foreach ($aiResponse['items'] as $index => $item) {
                    // Strip HTML from items
                    $cleanItem = strip_tags($item, '<b><i><u>');
                    $output .= ($index + 1).". {$cleanItem}\n";
                }
            }

            if (isset($aiResponse['footer'])) {
                $output .= "\n".strip_tags($aiResponse['footer']);
            }

            return $output;
        }

        return (string) $aiResponse;
    }

    /**
     * Render menu reply based on type
     */
    private function renderMenuReply(WhatsAppMenu $menu): string
    {
        $reply = $menu->response_text;
        
        // Replace placeholders
        $reply = str_replace('{nama_user}', 'Teman', $reply);
        $reply = str_replace('{jam_sekarang}', now()->format('H:i'), $reply);
        $reply = str_replace('{tanggal_sekarang}', now()->format('d M Y'), $reply);

        return $reply;
    }
}
