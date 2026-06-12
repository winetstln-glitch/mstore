<?php

namespace App\Jobs;

use App\Models\Setting;
use App\Models\WhatsAppLog;
use App\Services\WhatsApp\WhatsAppAutoReplyService;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\App;
use App\Models\WhatsAppMenu;
use Illuminate\Support\Str;

class ProcessDelayedWhatsAppReply implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Data yang dibutuhkan
    public string $phone;
    public string $message;
    public bool $isGroup;
    public int $incomingTimestamp;
    public string $conversationId;

    /**
     * Create a new job instance.
     */
    public function __construct(string $phone, string $message, bool $isGroup, int $incomingTimestamp, string $conversationId)
    {
        $this->phone = $phone;
        $this->message = $message;
        $this->isGroup = $isGroup;
        $this->incomingTimestamp = $incomingTimestamp;
        $this->conversationId = $conversationId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Processing delayed WhatsApp reply', [
            'phone' => $this->phone,
            'since' => date('Y-m-d H:i:s', $this->incomingTimestamp),
        ]);

        // Cek apakah fitur masih aktif
        $featureEnabled = Setting::getValue('whatsapp_delay_reply_enabled', '0') == '1';
        if (!$featureEnabled) {
            Log::info('Delayed reply feature is disabled, skipping');
            return;
        }

        // Cek apakah ada pesan outgoing dari kita ke nomor ini setelah incoming message
        $hasCsReply = WhatsAppLog::where('phone_number', $this->phone)
            ->where('type', 'outgoing')
            ->where('created_at', '>', date('Y-m-d H:i:s', $this->incomingTimestamp))
            ->exists();

        if ($hasCsReply) {
            Log::info('CS already replied, skipping auto reply');
            return;
        }

        // Jika CS tidak membalas, jalankan auto reply logic seperti biasa
        $startTime = microtime(true);
        $response = $this->processAutoReplyLogic($this->phone, $this->message, $this->isGroup);
        
        if ($response) {
            $whatsappService = App::make(WhatsAppService::class);
            $sendResult = $whatsappService->sendMessage($this->phone, $response);
            $processingTime = round((microtime(true) - $startTime) * 1000);
            
            WhatsAppLog::logMessage('outgoing', $this->phone, $response, $sendResult['success'] ? 'sent' : 'failed', $sendResult, null, [
                'conversation_id' => $this->conversationId,
                'sender_type' => 'bot',
                'message_type' => 'text',
                'processing_time_ms' => $processingTime,
            ]);
        }
    }

    /**
     * Logic auto reply dari WhatsAppWebhookController
     */
    private function processAutoReplyLogic(string $phone, string $message, bool $isGroup): ?string
    {
        $message = trim(Str::lower($message));

        // Check if autoreply is enabled
        $autoreplyEnabled = Setting::getValue('whatsapp_autoreply_enabled', '1') == '1';
        if (!$autoreplyEnabled) {
            return null;
        }

        // First, try WhatsAppAutoReplyService
        $autoReplyService = App::make(WhatsAppAutoReplyService::class);
        $autoReply = $autoReplyService->getReply($message);
        if ($autoReply) {
            return $autoReply;
        }

        // Next, check WhatsAppMenu (from builder)
        $menus = WhatsAppMenu::active()
            ->orderBy('priority', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($menus as $menu) {
            $keyword = Str::lower($menu->keyword);
            
            if ($message === $keyword) {
                $menu->incrementHitCount();
                return $this->renderMenuReplyText($menu);
            }

            if ($menu->enable_fuzzy_match) {
                similar_text($message, $keyword, $percent);
                if ($percent >= 70 || Str::contains($message, $keyword)) {
                    $menu->incrementHitCount();
                    return $this->renderMenuReplyText($menu);
                }
            }
        }

        // Check voucher flow - skip untuk delay reply agar CS yang handle
        if ($message === 'voucher' || $message === 'beli' || $message === 'paket' || is_numeric($message)) {
            return null;
        }

        // Custom unknown keyword reply
        $customReply = Setting::getValue('whatsapp_unknown_keyword_reply');
        if ($customReply) {
            return $customReply;
        }

        // Default fallback
        return 'Maaf, saya tidak memahami pesan Anda. Silakan ketik "bantuan" untuk melihat daftar menu yang tersedia.';
    }

    /**
     * Render menu reply text only
     */
    private function renderMenuReplyText(WhatsAppMenu $menu): string
    {
        $reply = $menu->response_text;
        
        $reply = str_replace('{nama_user}', 'Teman', $reply);
        $reply = str_replace('{jam_sekarang}', now()->format('H:i'), $reply);
        $reply = str_replace('{tanggal_sekarang}', now()->format('d M Y'), $reply);

        return $reply;
    }
}
