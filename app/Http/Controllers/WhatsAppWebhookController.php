<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Voucher;
use App\Models\VoucherPayment;
use App\Models\VoucherTemplate;
use App\Models\WhatsAppMenu;
use App\Models\WhatsAppLog;
use App\Services\Payment\PaymentManager;
use App\Services\VoucherService;
use App\Services\WhatsAppService;
use App\Services\WhatsApp\WhatsAppAutoReplyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WhatsAppWebhookController extends Controller
{
    protected $whatsappService;
    protected $aiService;
    protected $voucherService;
    protected $paymentManager;
    protected $autoReplyService;

    public function __construct(WhatsAppService $whatsappService, AiService $aiService, VoucherService $voucherService, PaymentManager $paymentManager, WhatsAppAutoReplyService $autoReplyService)
    {
        $this->whatsappService = $whatsappService;
        $this->aiService = $aiService;
        $this->voucherService = $voucherService;
        $this->paymentManager = $paymentManager;
        $this->autoReplyService = $autoReplyService;
    }

    /**
     * Handle incoming WhatsApp messages from webhook
     */
    public function handle(Request $request)
    {
        if ($request->isMethod('GET')) {
            $verifyToken = config('services.whatsapp.verify_token');
            $receivedToken = $request->input('hub.verify_token') ?? $request->input('verify_token');
            $challenge = $request->input('hub.challenge') ?? $request->input('challenge');

            if (is_string($verifyToken) && $verifyToken !== '') {
                if (! is_string($receivedToken) || $receivedToken === '' || ! hash_equals($verifyToken, $receivedToken)) {
                    return response('Invalid verify token', 403);
                }
            }

            return response((string) ($challenge ?? 'OK'), 200);
        }

        if (! $this->isValidWebhookRequest($request)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $payload = null;
        try {
            $payload = $request->all();
            Log::info('WhatsApp Webhook Received', [
                'keys' => array_slice(array_keys($payload), 0, 20),
            ]);

            // Try to extract message from different provider formats (Fonnte, Wablas, etc.)
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
                WhatsAppLog::logMessage('incoming', $phone, $request->getContent() ?? 'error', 'failed', $payload ?? [], $e->getMessage());
            }
            
            return response()->json(['error' => 'Server error'], 500);
        }
    }

    private function isValidWebhookRequest(Request $request): bool
    {
        $secret = config('services.whatsapp.secret');
        if (! is_string($secret) || trim($secret) === '') {
            return true;
        }

        $secret = trim($secret);

        $signature256 = $request->header('X-Hub-Signature-256');
        if (is_string($signature256) && $signature256 !== '') {
            $rawBody = (string) $request->getContent();
            $expected = 'sha256=' . hash_hmac('sha256', $rawBody, $secret);
            return hash_equals($expected, $signature256);
        }

        $authorization = $request->header('Authorization');
        if (is_string($authorization) && $authorization !== '') {
            if (str_starts_with($authorization, 'Bearer ')) {
                $token = substr($authorization, strlen('Bearer '));
                return is_string($token) && $token !== '' && hash_equals($secret, $token);
            }
        }

        $headerSecret = $request->header('X-Webhook-Secret') ?? $request->header('X-Webhook-Token');
        if (is_string($headerSecret) && $headerSecret !== '') {
            return hash_equals($secret, $headerSecret);
        }

        $querySecret = $request->query('secret');
        if (is_string($querySecret) && $querySecret !== '') {
            return hash_equals($secret, $querySecret);
        }

        return false;
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

        // Wablas format - various possibilities
        if (isset($payload['data']) && is_array($payload['data'])) {
            foreach ($payload['data'] as $msg) {
                if (isset($msg['message'])) {
                    // Extract phone number without @c.us suffix
                    $phone = $msg['phone'] ?? $msg['sender'] ?? null;
                    if ($phone && str_contains($phone, '@')) {
                        $phone = explode('@', $phone)[0];
                    }
                    return [
                        'phone' => $phone,
                        'message' => $msg['message'],
                        'is_group' => isset($msg['is_group']) ? (bool) $msg['is_group'] : (isset($msg['group_id']) && !empty($msg['group_id'])),
                    ];
                }
            }
        }

        // Generic format 1
        if (isset($payload['message']) && isset($payload['phone'])) {
            $phone = $payload['phone'];
            if ($phone && str_contains($phone, '@')) {
                $phone = explode('@', $phone)[0];
            }
            return [
                'phone' => $phone,
                'message' => $payload['message'],
                'is_group' => isset($payload['is_group']) ? (bool) $payload['is_group'] : (isset($payload['group_id']) && !empty($payload['group_id'])),
            ];
        }
        
        // Generic format 2 - maybe payload is single message object
        if (isset($payload['message']) || isset($payload['text'])) {
            $phone = $payload['phone'] ?? $payload['sender'] ?? $payload['from'] ?? null;
            if ($phone && str_contains($phone, '@')) {
                $phone = explode('@', $phone)[0];
            }
            return [
                'phone' => $phone,
                'message' => $payload['message'] ?? $payload['text'] ?? '',
                'is_group' => isset($payload['is_group']) ? (bool) $payload['is_group'] : (isset($payload['group_id']) && !empty($payload['group_id'])),
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

        // First, try WhatsAppAutoReplyService for default menus (halo, bantuan)
        $autoReply = $this->autoReplyService->getReply($message);
        if ($autoReply) {
            return $autoReply;
        }

        // Next, check WhatsAppMenu (from builder) for matching keywords
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

        // If no menu matches, use custom reply from setting
        $customReply = Setting::getValue('whatsapp_unknown_keyword_reply');
        if ($customReply) {
            return $customReply;
        }

        // Default fallback (if custom reply is not set)
        return 'Maaf, saya tidak memahami pesan Anda. Silakan ketik "bantuan" untuk melihat daftar menu yang tersedia.';
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
        $duitku = $this->paymentManager->gateway('duitku');
        $duitkuResponse = $duitku->createTransaction([
            'reference_id' => $payment->reference_id,
            'amount' => $template->price,
            'payment_method' => 'QR',
            'description' => 'Voucher Hotspot: '.$template->name,
            'customer_name' => 'WA-'.$phone,
            'customer_email' => 'customer@mstore.id',
            'customer_phone' => $phone,
        ]);

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
        $duitku = $this->paymentManager->gateway('duitku');
        $duitkuResponse = $duitku->checkStatus($payment->reference_id);

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
