<?php

namespace App\Http\Controllers;

use App\Models\Voucher;
use App\Models\VoucherPayment;
use App\Models\VoucherTemplate;
use App\Services\Payment\PaymentManager;
use App\Services\VoucherService;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VoucherPaymentController extends Controller
{
    protected $paymentManager;
    protected $voucherService;
    protected $whatsappService;

    public function __construct(PaymentManager $paymentManager, VoucherService $voucherService, WhatsAppService $whatsappService)
    {
        $this->paymentManager = $paymentManager;
        $this->voucherService = $voucherService;
        $this->whatsappService = $whatsappService;
    }

    public function index()
    {
        return redirect('https://buy.mstore.id/e-voucher');
    }

    public function selectPaymentMethod(Request $request)
    {
        return redirect('https://buy.mstore.id/e-voucher');
    }

    public function createPayment(Request $request)
    {
        $request->validate([
            'voucher_template_id' => 'required|exists:voucher_templates,id',
            'customer_name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'payment_method' => 'nullable|string',
            'use_pop' => 'boolean',
        ]);

        $template = VoucherTemplate::findOrFail($request->voucher_template_id);
        $usePop = $request->boolean('use_pop', true);
        $email = $request->email ?? 'customer@example.com';
        
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
            'use_pop' => $usePop,
        ]);

        try {
            $duitku = $this->paymentManager->gateway('duitku');
            $payload = [
                'amount' => $template->price,
                'reference_id' => $referenceId,
                'description' => $template->name,
                'customer_name' => $request->customer_name,
                'customer_email' => $email,
                'customer_phone' => $request->phone_number,
            ];

            if (!$usePop && $request->filled('payment_method')) {
                $payload['payment_method'] = $request->payment_method;
            }

            $transaction = $duitku->createTransaction($payload);

            // Check if Duitku response is successful
            if (isset($transaction['statusCode']) && $transaction['statusCode'] === '00') {
                // Update payment with transaction data
                $payment->update([
                    'qr_url' => $transaction['paymentUrl'] ?? null,
                    'duitku_reference' => $transaction['reference'] ?? null,
                    'payment_method' => $request->payment_method,
                ]);
            } else {
                // Handle error
                $payment->update(['status' => 'failed']);
                return back()->with('error', 'Gagal membuat transaksi: ' . ($transaction['statusMessage'] ?? 'Terjadi kesalahan'));
            }
        } catch (\Exception $e) {
            $payment->update(['status' => 'failed']);
            return back()->with('error', 'Gagal membuat transaksi: ' . $e->getMessage());
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
        Log::info('Duitku Callback Received', [
            'merchantOrderId' => $request->input('merchantOrderId'),
            'resultCode' => $request->input('resultCode') ?? $request->input('statusCode'),
            'reference' => $request->input('reference'),
        ]);

        try {
            $duitku = $this->paymentManager->gateway('duitku');
            $notif = $duitku->handleNotification($request->all());
            
            if (!$notif) {
                return response()->json(['success' => false, 'message' => 'Invalid notification'], 400);
            }

            $referenceId = $notif['merchantOrderId'] ?? null;
            if (!is_string($referenceId) || $referenceId === '') {
                return response()->json(['success' => false, 'message' => 'Invalid reference'], 400);
            }

            $resultCode = (string) ($notif['resultCode'] ?? $notif['statusCode'] ?? '');

            $payment = null;
            DB::transaction(function () use ($referenceId, $notif, $resultCode, &$payment) {
                $payment = VoucherPayment::query()
                    ->where('reference_id', $referenceId)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($payment->status === 'paid') {
                    return;
                }

                if ($resultCode === '00') {
                $payment->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'payment_reference' => $notif['reference'] ?? null,
                    'payment_method' => $notif['paymentCode'] ?? $payment->payment_method,
                    'duitku_reference' => $notif['reference'] ?? $payment->duitku_reference,
                ]);
                return;
            }

            if (in_array($resultCode, ['01', '02'], true)) {
                $payment->update(['status' => 'failed']);
            }
        });

        if (! $payment) {
            return response()->json(['success' => false, 'message' => 'Payment not found'], 404);
        }

        if ($payment->status === 'paid') {
            FulfillVoucherPaymentJob::dispatch($payment->id);
        }

        return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Duitku Callback Error', ['message' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function return(Request $request)
    {
        $payment = VoucherPayment::where('reference_id', $request->merchantOrderId)->firstOrFail();
        
        return view('voucher-payment.return', compact('payment'));
    }

    public function checkStatus($referenceId)
    {
        $payment = VoucherPayment::where('reference_id', $referenceId)->firstOrFail();
        
        $duitku = $this->paymentManager->gateway('duitku');
        $status = $duitku->checkStatus($referenceId);
        
        return response()->json($status);
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
