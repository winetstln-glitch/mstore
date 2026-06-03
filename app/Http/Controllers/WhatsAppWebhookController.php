<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Voucher;
use App\Models\VoucherTemplate;
use App\Models\WhatsAppMenu;
use App\Models\WhatsAppLog;
use App\Services\AiService;
use App\Services\VoucherService;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WhatsAppWebhookController extends Controller
{
    protected $whatsappService;
    protected $aiService;
    protected $voucherService;

    public function __construct(WhatsAppService $whatsappService, AiService $aiService, VoucherService $voucherService)
    {
        $this->whatsappService = $whatsappService;
        $this->aiService = $aiService;
        $this->voucherService = $voucherService;
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
            
            if (! $messageData) {
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
                        'is_group' => isset($msg['is_group']) ? (bool) $msg['is_group'] : false,
                    ];
                }
            }
        }

        // Generic format
        if (isset($payload['message']) && isset($payload['phone'])) {
            return [
                'phone' => $payload['phone'],
                'message' => $payload['message'],
                'is_group' => isset($payload['is_group']) ? (bool) $payload['is_group'] : false,
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
        if (! $autoreplyEnabled) {
            return null;
        }

        // Handle Voucher Purchases
        if (Str::contains($message, 'voucher') || Str::contains($message, 'wifi') || Str::contains($message, 'internet')) {
            return $this->handleVoucherRequest($message);
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
     * Handle voucher purchase requests
     */
    private function handleVoucherRequest(string $message): string
    {
        $templates = VoucherTemplate::where('is_active', true)->orderBy('price')->get();

        if ($templates->isEmpty()) {
            return "Maaf, saat ini tidak ada paket voucher yang tersedia.";
        }

        // Check if user selected a template
        foreach ($templates as $template) {
            if (Str::contains($message, (string) $template->id)) {
                return $this->generateVoucherForTemplate($template);
            }
        }

        // Show list of templates
        $response = "*Daftar Paket Voucher Hotspot:*\n\n";
        foreach ($templates as $index => $template) {
            $response .= ($index + 1) . ". *{$template->name}*\n";
            $response .= "   💰 Harga: Rp " . number_format($template->price, 0, ',', '.') . "\n";
            if ($template->duration_seconds) {
                $response .= "   ⏱ Durasi: " . $this->formatDuration($template->duration_seconds) . "\n";
            }
            if ($template->quota_mb) {
                $response .= "   📊 Kuota: " . number_format($template->quota_mb, 0, ',', '.') . " MB\n";
            }
            if ($template->rate_limit) {
                $response .= "   🚀 Kecepatan: {$template->rate_limit}\n";
            }
            $response .= "   👉 Balas dengan *{$template->id}* untuk beli paket ini!\n\n";
        }

        return $response;
    }

    /**
     * Generate a single voucher from a template
     */
    private function generateVoucherForTemplate(VoucherTemplate $template): string
    {
        try {
            // Generate 1 voucher
            $batch = $this->voucherService->generateBatch(
                $template->rate_limit,
                $template->duration_seconds,
                $template->quota_mb,
                1,
                true
            );

            $voucher = Voucher::where('batch_id', $batch->id)->first();

            if (! $voucher) {
                return "Maaf, gagal membuat voucher. Silakan coba lagi nanti.";
            }

            // Format the response
            $response = "*🎉 Voucher Hotspot Berhasil Dibuat!*\n\n";
            $response .= "*Paket:* {$template->name}\n";
            if ($template->duration_seconds) {
                $response .= "*Durasi:* " . $this->formatDuration($template->duration_seconds) . "\n";
            }
            if ($template->quota_mb) {
                $response .= "*Kuota:* " . number_format($template->quota_mb, 0, ',', '.') . " MB\n";
            }
            $response .= "\n";
            $response .= "*Username:* `{$voucher->username}`\n";
            $response .= "*Password:* `{$voucher->password}`\n";
            $response .= "\n";
            $response .= "Gunakan username dan password di atas untuk login ke hotspot!";

            return $response;
        } catch (\Exception $e) {
            Log::error('Voucher generation error: ' . $e->getMessage());
            return "Maaf, terjadi kesalahan saat membuat voucher. Silakan coba lagi nanti.";
        }
    }

    /**
     * Format duration seconds to human-readable format
     */
    private function formatDuration(int $seconds): string
    {
        if ($seconds >= 86400) {
            $days = floor($seconds / 86400);
            return $days . ' ' . ($days > 1 ? 'hari' : 'hari');
        }
        if ($seconds >= 3600) {
            $hours = floor($seconds / 3600);
            return $hours . ' ' . ($hours > 1 ? 'jam' : 'jam');
        }
        if ($seconds >= 60) {
            $minutes = floor($seconds / 60);
            return $minutes . ' ' . ($minutes > 1 ? 'menit' : 'menit');
        }
        return $seconds . ' detik';
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
                    $output .= ($index + 1) . ". {$cleanItem}\n";
                }
            }

            if (isset($aiResponse['footer'])) {
                $output .= "\n" . strip_tags($aiResponse['footer']);
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
