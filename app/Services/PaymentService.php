<?php

namespace App\Services;

use App\Events\PaymentProcessed;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\PaymentTransaction;
use App\Models\User;
use App\Services\Payment\PaymentManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    public function __construct(
        public readonly PaymentManager $paymentManager,
    ) {}

    /**
     * Create QRIS payment transaction
     */
    public function createQrisPayment(
        mixed $paymentable,
        string $customerName,
        string $phoneNumber,
        string $email = null,
    ): PaymentTransaction {
        DB::beginTransaction();
        try {
            $user = $paymentable->user ?? ($paymentable->customer ? $paymentable->customer->user : null);

            $transaction = PaymentTransaction::create([
                'user_id' => $user?->id,
                'paymentable_type' => get_class($paymentable),
                'paymentable_id' => $paymentable->id,
                'customer_name' => $customerName,
                'phone_number' => $phoneNumber,
                'email' => $email,
                'amount' => $paymentable->amount ?? 0,
                'payment_type' => 'QRIS',
                'payment_gateway' => 'duitku',
                'status' => 'pending',
            ]);

            // Generate QRIS using Duitku Gateway
            $duitku = $this->paymentManager->gateway('duitku');
            $qrisResponse = $duitku->createTransaction([
                'reference_id' => $transaction->reference_id,
                'amount' => $transaction->amount,
                'payment_method' => 'QR',
                'description' => $this->getProductDetails($paymentable),
                'customer_name' => $customerName,
                'customer_email' => $email ?? 'default@example.com',
                'customer_phone' => $phoneNumber,
            ]);

            if (isset($qrisResponse['success']) && !$qrisResponse['success']) {
                throw new \Exception('Failed to generate QRIS: ' . ($qrisResponse['message'] ?? 'Unknown error'));
            }

            // Save QRIS details
            $transaction->update([
                'qr_url' => $qrisResponse['qrImageUrl'] ?? $qrisResponse['qr_url'] ?? null,
                'qr_data' => $qrisResponse['qrContent'] ?? $qrisResponse['qr_data'] ?? null,
                'gateway_reference_id' => $qrisResponse['reference'] ?? $qrisResponse['reference_id'] ?? null,
            ]);

            DB::commit();
            return $transaction;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create QRIS payment', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Process callback from payment gateway
     */
    public function processCallback(array $data): void
    {
        $duitku = $this->paymentManager->gateway('duitku');
        $notif = $duitku->handleNotification($data);

        if (! is_array($notif)) {
            throw new \Exception('Invalid signature');
        }

        $referenceId = $notif['merchantOrderId'] ?? $notif['reference_id'] ?? null;
        if (! is_string($referenceId) || $referenceId === '') {
            throw new \Exception('Missing reference_id');
        }

        Log::info('Processing payment callback', [
            'merchantOrderId' => $referenceId,
            'resultCode' => $notif['resultCode'] ?? $notif['statusCode'] ?? null,
        ]);

        $normalizeAmount = static function ($value): int {
            $raw = preg_replace('/[^0-9]/', '', (string) $value);
            return (int) ($raw === '' ? 0 : $raw);
        };

        DB::transaction(function () use ($referenceId, $notif, $normalizeAmount) {
            $transaction = PaymentTransaction::query()
                ->where('reference_id', $referenceId)
                ->lockForUpdate()
                ->first();

            if (! $transaction) {
                throw new \RuntimeException('Transaction not found: ' . $referenceId);
            }

            if ($transaction->status === 'paid') {
                return;
            }

            $notifAmount = $normalizeAmount($notif['amount'] ?? $notif['paymentAmount'] ?? 0);
            $expectedAmount = $normalizeAmount($transaction->amount);
            if ($notifAmount > 0 && $expectedAmount > 0 && $notifAmount !== $expectedAmount) {
                $transaction->markAsFailed();
                return;
            }

            $resultCode = (string) ($notif['resultCode'] ?? $notif['statusCode'] ?? $notif['status'] ?? '');
            if ($resultCode === '00' || strtolower($resultCode) === 'success' || ($notif['success'] ?? false)) {
                $transaction->markAsPaid($notif['reference'] ?? $notif['transaction_id'] ?? null);
                event(new PaymentProcessed($transaction->fresh()));
                return;
            }

            if ($resultCode === '02') {
                $transaction->markAsExpired();
                return;
            }

            if ($resultCode === '01') {
                $transaction->markAsFailed();
            }
        });
    }

    private function getProductDetails(mixed $paymentable): string
    {
        if ($paymentable instanceof Invoice) {
            return "Pembayaran Tagihan {$paymentable->code}";
        }
        return "Pembayaran Produk";
    }
}
