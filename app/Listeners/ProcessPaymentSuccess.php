<?php

namespace App\Listeners;

use App\Events\PaymentProcessed;
use App\Models\Invoice;
use App\Models\PaymentTransaction;
use App\Models\Transaction;
use App\Models\VoucherPayment;
use App\Models\WeddingPayment;
use App\Models\CctvPayment;
use App\Services\AuditLogService;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Log;

class ProcessPaymentSuccess
{
    public function __construct(
        public readonly WhatsAppService $whatsappService,
        public readonly AuditLogService $auditLogService,
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
        } elseif ($transaction->paymentable instanceof WeddingPayment) {
            $this->processWeddingPayment($transaction);
        } elseif ($transaction->paymentable instanceof CctvPayment) {
            $this->processCctvPayment($transaction);
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

    private function processWeddingPayment(PaymentTransaction $transaction): void
    {
        /** @var WeddingPayment $payment */
        $payment = $transaction->paymentable;
        $payment->loadMissing('booking');

        $old = $payment->toArray();
        $payment->update([
            'status' => 'paid',
            'paid_at' => $transaction->paid_at,
        ]);
        $this->auditLogService->logAction('wedding.payment.paid', $payment, $old, $payment->toArray());

        $booking = $payment->booking;
        if ($booking) {
            $oldBooking = $booking->toArray();
            if ($payment->type === 'dp') {
                $booking->update([
                    'status' => 'dp',
                    'dp_amount' => $booking->dp_amount ?? $payment->amount,
                ]);
                $this->auditLogService->logAction('wedding.booking.dp_paid', $booking, $oldBooking, $booking->toArray());
            } else {
                $booking->update([
                    'status' => 'confirmed',
                    'confirmed_at' => $booking->confirmed_at ?? now(),
                ]);
                $this->auditLogService->logAction('wedding.booking.confirmed', $booking, $oldBooking, $booking->toArray());
            }

            Transaction::create([
                'user_id' => null,
                'type' => 'income',
                'category' => 'Wedding & Event',
                'amount' => (string) $payment->amount,
                'description' => 'Wedding payment '.$payment->type.' - '.$booking->booking_number,
                'transaction_date' => ($transaction->paid_at ?? now())->toDateString(),
                'reference_number' => 'WEDPAY-'.$transaction->id,
            ]);
        }
    }

    private function processCctvPayment(PaymentTransaction $transaction): void
    {
        /** @var CctvPayment $payment */
        $payment = $transaction->paymentable;
        $payment->loadMissing('booking');

        $old = $payment->toArray();
        $payment->update([
            'status' => 'paid',
            'paid_at' => $transaction->paid_at,
        ]);
        $this->auditLogService->logAction('cctv.payment.paid', $payment, $old, $payment->toArray());

        $booking = $payment->booking;
        if ($booking) {
            $oldBooking = $booking->toArray();
            if ($payment->type === 'dp') {
                $booking->update([
                    'status' => 'dp',
                    'dp_amount' => $booking->dp_amount ?? $payment->amount,
                ]);
                $this->auditLogService->logAction('cctv.booking.dp_paid', $booking, $oldBooking, $booking->toArray());
            } else {
                $booking->update([
                    'status' => 'completed',
                    'completed_at' => $booking->completed_at ?? now(),
                ]);
                $this->auditLogService->logAction('cctv.booking.completed', $booking, $oldBooking, $booking->toArray());
            }

            Transaction::create([
                'user_id' => null,
                'type' => 'income',
                'category' => 'CCTV Installation',
                'amount' => (string) $payment->amount,
                'description' => 'CCTV payment '.$payment->type.' - '.$booking->booking_number,
                'transaction_date' => ($transaction->paid_at ?? now())->toDateString(),
                'reference_number' => 'CCTVPAY-'.$transaction->id,
            ]);
        }
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
