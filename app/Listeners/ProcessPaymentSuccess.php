<?php

namespace App\Listeners;

use App\Events\PaymentProcessed;
use App\Models\Invoice;
use App\Models\VoucherPayment;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

class ProcessPaymentSuccess
{
    public function __construct(
        public readonly WhatsAppService $whatsappService,
    ) {}

    public function handle(PaymentProcessed $event): void
    {
        $transaction = $event->transaction;
        Log::info('Processing payment success', ['transaction_id' => $transaction->id]);

        // Handle based on paymentable type
        if ($transaction->paymentable instanceof Invoice) {
            $this->processInvoicePayment($transaction);
        } elseif ($transaction->paymentable instanceof VoucherPayment) {
            $this->processVoucherPayment($transaction);
        }

        // Send WhatsApp notification
        $this->sendWhatsAppNotification($transaction);
    }

    private function processInvoicePayment(PaymentTransaction $transaction): void
    {
        $invoice = $transaction->paymentable;
        $invoice->update([
            'status' => 'paid',
            'paid_at' => $transaction->paid_at,
        ]);
        Log::info('Invoice marked as paid', ['invoice_id' => $invoice->id]);
    }

    private function processVoucherPayment(PaymentTransaction $transaction): void
    {
        $voucherPayment = $transaction->paymentable;
        $voucherPayment->update([
            'status' => 'paid',
            'paid_at' => $transaction->paid_at,
        ]);
        // TODO: Generate actual voucher if needed
        Log::info('Voucher payment marked as paid', ['voucher_payment_id' => $voucherPayment->id]);
    }

    private function sendWhatsAppNotification(PaymentTransaction $transaction): void
    {
        if (empty($transaction->phone_number)) {
            return;
        }

        $message = "✅ Pembayaran Berhasil!\n" .
                   "ID Pembayaran: {$transaction->reference_id}\n" .
                   "Jumlah: Rp " . number_format($transaction->amount, 0, ',', '.') . "\n" .
                   "Terima kasih telah melakukan pembayaran!";

        try {
            $this->whatsappService->sendMessage($transaction->phone_number, $message, 'payment_success');
            Log::info('WhatsApp notification sent for payment', ['transaction_id' => $transaction->id]);
        } catch (\Exception $e) {
            Log::error('Failed to send WhatsApp payment notification', ['error' => $e->getMessage()]);
        }
    }
}
