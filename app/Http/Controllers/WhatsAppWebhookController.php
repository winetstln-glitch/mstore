<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Voucher;
use App\Models\VoucherPayment;
use App\Models\VoucherTemplate;
use App\Models\WhatsAppMenu;
use App\Models\WhatsAppLog;
use App\Services\AiService;
use App\Services\DuitkuService;
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
    protected $duitkuService;

    public function __construct(WhatsAppService $whatsappService, AiService $aiService, VoucherService $voucherService, DuitkuService $duitkuService)
    {
        $this->whatsappService = $whatsappService;
        $this->aiService = $aiService;
        $this->voucherService = $voucherService;
        $this->duitkuService = $duitkuService;
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

        // Check for pending payment
        $pendingPayment = VoucherPayment::where('phone_number', $phone)->where('status', 'pending')->first();
        if ($pendingPayment) {
            // Maybe user wants to check status?
            if (Str::contains($message, 'cek') || Str::contains($message, 'status')) {
                return $this->checkPaymentStatus($pendingPayment);
            }
        }

        // Handle Voucher Purchases
        if (Str::contains($message, 'voucher') || Str::contains($message, 'wifi') || Str::contains($message, 'internet')) {
            return $this->handleVoucherRequest($phone, $message);
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
    private function handleVoucherRequest(string $phone, string $message): string
    {
        $templates = VoucherTemplate::where('is_active', true)->orderBy('price')->get();

        if ($templates->isEmpty()) {
            return "Maaf, saat ini tidak ada paket voucher yang tersedia.";
        }

        // Check if user selected a template
        foreach ($templates as $template) {
            if (Str::contains($message, (string) $template->id)) {
                return $this->createPayment($phone, $template);
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
     * Create payment and return QR code
     */
    private function createPayment(string $phone, VoucherTemplate $template): string
    {
        // Check if already has pending payment
        $existingPending = VoucherPayment::where('phone_number', $phone)->where('status', 'pending')->first();
        if ($existingPending) {
            return "Anda masih memiliki pembayaran yang belum selesai!\n\n" .
                   "Silakan selesaikan pembayaran terlebih dahulu atau cek status dengan mengetik *cek*.";
        }

        // Create payment record
        $payment = VoucherPayment::create([
            'phone_number' => $phone,
            'voucher_template_id' => $template->id,
            'amount' => $template->price,
            'status' => 'pending',
            'payment_method' => 'QRIS',
        ]);

        // Create transaction with Duitku
        $duitkuResponse = $this->duitkuService->createTransaction(
            $payment->reference_id,
            $template->price,
            'QR', // QRIS payment method
            "Voucher Hotspot - {$template->name}",
            "Customer {$phone}",
            "customer@example.com",
            $phone
        );

        if (isset($duitkuResponse['statusCode']) && $duitkuResponse['statusCode'] == '00') {
            // Success
            $payment->update([
                'payment_reference' => $duitkuResponse['reference'],
                'qr_url' => $duitkuResponse['paymentUrl'],
                'qr_data' => $duitkuResponse['qrCode'] ?? null,
            ]);

            $paymentUrl = route('voucher.payment.show', $payment->reference_id);
            $expiresAt = $payment->expires_at->format('d M Y H:i');

            $response = "*🛒 Pembayaran Voucher Hotspot*\n\n";
            $response .= "*Paket:* {$template->name}\n";
            $response .= "*Total:* Rp " . number_format($template->price, 0, ',', '.') . "\n";
            $response .= "*Metode:* QRIS\n\n";
            $response .= "Silakan scan QR code atau buka link di bawah:\n";
            $response .= "*{$paymentUrl}*\n\n";
            $response .= "Kadaluarsa: {$expiresAt}\n\n";
            $response .= "Setelah pembayaran berhasil, voucher akan dikirim otomatis!";
            
            return $response;
        } else {
            // Failed
            $payment->update(['status' => 'failed']);
            $error = $duitkuResponse['statusMessage'] ?? 'Gagal membuat pembayaran';
            return "Maaf, {$error}. Silakan coba lagi nanti.";
        }
    }

    /**
     * Check payment status
     */
    private function checkPaymentStatus(VoucherPayment $payment): string
    {
        // Check with Duitku
        $duitkuResponse = $this->duitkuService->checkTransaction($payment->reference_id);

        if (isset($duitkuResponse['statusCode']) && $duitkuResponse['statusCode'] == '00') {
            // Already paid
            if ($payment->status !== 'paid') {
                // Mark as paid if not already
                $payment->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                ]);
                
                // Generate voucher
                $this->generateVoucherAndSendToUser($payment);
            }
        }

        $statusText = [
            'pending' => 'Menunggu Pembayaran',
            'paid' => 'Sudah Dibayar',
            'failed' => 'Gagal',
            'expired' => 'Kadaluarsa',
        ];

        $response = "*📊 Status Pembayaran*\n\n";
        $response .= "*ID:* {$payment->reference_id}\n";
        $response .= "*Status:* {$statusText[$payment->status]}\n";
        $response .= "*Paket:* {$payment->voucherTemplate->name}\n";
        $response .= "*Total:* Rp " . number_format($payment->amount, 0, ',', '.') . "\n";

        if ($payment->status === 'pending' && $payment->qr_url) {
            $paymentUrl = route('voucher.payment.show', $payment->reference_id);
            $response .= "\nUntuk membayar: {$paymentUrl}";
        }

        return $response;
    }

    /**
     * Generate voucher and send to user
     */
    private function generateVoucherAndSendToUser(VoucherPayment $payment)
    {
        try {
            // Already has voucher?
            if ($payment->voucher_id) {
                return;
            }

            $template = $payment->voucherTemplate;
            $batch = $this->voucherService->generateBatch(
                $template->rate_limit,
                $template->duration_seconds,
                $template->quota_mb,
                1,
                true
            );

            $voucher = Voucher::where('batch_id', $batch->id)->first();
            
            $payment->update([
                'voucher_id' => $voucher->id,
            ]);

            // Send to WhatsApp
            $message = "*🎉 Pembayaran Berhasil!*\n\n";
            $message .= "*Paket:* {$template->name}\n";
            if ($template->duration_seconds) {
                $message .= "*Durasi:* " . $this->formatDuration($template->duration_seconds) . "\n";
            }
            if ($template->quota_mb) {
                $message .= "*Kuota:* " . number_format($template->quota_mb, 0, ',', '.') . " MB\n";
            }
            $message .= "\n";
            $message .= "*Username:* `{$voucher->username}`\n";
            $message .= "*Password:* `{$voucher->password}`\n";
            $message .= "\n";
            $message .= "Gunakan username dan password di atas untuk login ke hotspot!";

            $this->whatsappService->sendMessage($payment->phone_number, $message);

        } catch (\Exception $e) {
            Log::error('Failed to generate and send voucher', ['error' => $e->getMessage(), 'payment_id' => $payment->id]);
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
