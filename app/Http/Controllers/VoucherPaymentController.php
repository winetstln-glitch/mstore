<?php

namespace App\Http\Controllers;

use App\Models\Voucher;
use App\Models\VoucherPayment;
use App\Models\VoucherTemplate;
use App\Services\DuitkuService;
use App\Services\VoucherService;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VoucherPaymentController extends Controller
{
    protected $duitkuService;
    protected $voucherService;
    protected $whatsappService;

    public function __construct(DuitkuService $duitkuService, VoucherService $voucherService, WhatsAppService $whatsappService)
    {
        $this->duitkuService = $duitkuService;
        $this->voucherService = $voucherService;
        $this->whatsappService = $whatsappService;
    }

    public function index()
    {
        $templates = VoucherTemplate::where('is_active', true)->get();
        
        return view('voucher-payment.index', compact('templates'));
    }

    public function createPayment(Request $request)
    {
        $request->validate([
            'voucher_template_id' => 'required|exists:voucher_templates,id',
            'customer_name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:20',
        ]);

        $template = VoucherTemplate::findOrFail($request->voucher_template_id);
        
        // Generate reference ID
        $referenceId = 'VOUCHER-' . time() . '-' . strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 5));
        
        // Create payment record
        $payment = VoucherPayment::create([
            'reference_id' => $referenceId,
            'voucher_template_id' => $template->id,
            'customer_name' => $request->customer_name,
            'phone_number' => $request->phone_number,
            'amount' => $template->price,
            'status' => 'pending',
            'expires_at' => now()->addMinutes(1440), // 24 hours
        ]);

        // Create transaction with Duitku (using QRIS as default)
        $transaction = $this->duitkuService->createTransaction(
            $referenceId,
            $template->price,
            'QR', // QRIS payment method code
            $template->name,
            $request->customer_name,
            'customer@example.com',
            $request->phone_number
        );

        // Check if Duitku response is successful
        if (isset($transaction['statusCode']) && $transaction['statusCode'] === '00') {
            // Update payment with QR URL if available
            $payment->update([
                'qr_url' => $transaction['paymentUrl'] ?? null,
            ]);
        } else {
            // Handle error
            $payment->update(['status' => 'failed']);
            return back()->with('error', 'Gagal membuat transaksi: ' . ($transaction['statusMessage'] ?? 'Terjadi kesalahan'));
        }

        return redirect()->route('voucher.payment.show', $referenceId);
    }

    public function show($referenceId)
    {
        $payment = VoucherPayment::where('reference_id', $referenceId)->firstOrFail();
        
        return view('voucher-payment.show', compact('payment'));
    }

    public function callback(Request $request)
    {
        Log::info('Duitku Callback Received', $request->all());

        if (! $this->duitkuService->verifyCallback($request->all())) {
            return response()->json(['success' => false, 'message' => 'Invalid signature'], 400);
        }

        $payment = VoucherPayment::where('reference_id', $request->merchantOrderId)->first();
        
        if (! $payment) {
            return response()->json(['success' => false, 'message' => 'Payment not found'], 404);
        }

        if ($payment->status === 'paid') {
            return response()->json(['success' => true, 'message' => 'Payment already processed']);
        }

        if ($request->statusCode == '00') {
            // Payment success
            $payment->update([
                'status' => 'paid',
                'paid_at' => now(),
                'payment_reference' => $request->reference,
                'payment_method' => $request->paymentCode,
            ]);

            // Generate voucher
            $this->generateVoucherAndSendToUser($payment);
        } elseif (in_array($request->statusCode, ['01', '02'])) {
            // Payment failed or cancelled
            $payment->update([
                'status' => 'failed',
            ]);
        }

        return response()->json(['success' => true]);
    }

    public function return(Request $request)
    {
        $payment = VoucherPayment::where('reference_id', $request->merchantOrderId)->firstOrFail();
        
        return view('voucher-payment.return', compact('payment'));
    }

    protected function generateVoucherAndSendToUser(VoucherPayment $payment)
    {
        try {
            // Generate voucher
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

    protected function formatDuration(int $seconds): string
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
}

