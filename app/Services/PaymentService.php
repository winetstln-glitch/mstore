<?php

namespace App\Services;

use App\Events\PaymentProcessed;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\PaymentTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    public function __construct(
        public readonly DuitkuService $duitkuService,
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

            // Generate QRIS using Duitku
            $qrisResponse = $this->duitkuService->createApiTransaction(
                referenceId: $transaction->reference_id,
                amount: $transaction->amount,
                paymentMethod: 'QR',
                productDetails: $this->getProductDetails($paymentable),
                customerName: $customerName,
                customerEmail: $email ?? 'default@example.com',
                customerPhone: $phoneNumber,
            );

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
        Log::info('Processing payment callback', ['data' => $data]);

        $referenceId = $data['merchantOrderId'] ?? $data['reference_id'] ?? null;
        if (!$referenceId) {
            throw new \Exception('Missing reference_id');
        }

        $transaction = PaymentTransaction::where('reference_id', $referenceId)->first();
        if (!$transaction) {
            throw new \Exception('Transaction not found: ' . $referenceId);
        }

        if ($transaction->status === 'paid') {
            Log::info('Transaction already paid', ['transaction_id' => $transaction->id]);
            return;
        }

        $statusCode = $data['statusCode'] ?? $data['status'] ?? '00';
        if ($statusCode === '00' || strtolower($statusCode) === 'success' || $data['success'] ?? false) {
            DB::beginTransaction();
            try {
                $transaction->markAsPaid($data['reference'] ?? $data['transaction_id'] ?? null);

                event(new PaymentProcessed($transaction));

                DB::commit();
                Log::info('Payment processed successfully', ['transaction_id' => $transaction->id]);
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Failed to process payment', ['error' => $e->getMessage()]);
                throw $e;
            }
        } elseif ($statusCode === '01' || $statusCode === '02') {
            $transaction->markAsFailed();
            Log::info('Payment failed or expired', ['transaction_id' => $transaction->id, 'status_code' => $statusCode]);
        }
    }

    private function getProductDetails(mixed $paymentable): string
    {
        if ($paymentable instanceof Invoice) {
            return "Pembayaran Tagihan {$paymentable->code}";
        }
        return "Pembayaran Produk";
    }
}
