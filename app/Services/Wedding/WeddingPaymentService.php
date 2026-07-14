<?php

namespace App\Services\Wedding;

use App\Models\WeddingBooking;
use App\Models\WeddingPayment;
use App\Models\PaymentTransaction;
use App\Services\PaymentService;
use Illuminate\Support\Facades\DB;

class WeddingPaymentService
{
    public function __construct(
        private readonly PaymentService $paymentService,
    ) {}

    public function createQris(WeddingBooking $booking, string $type, int $amount, string $customerName, string $phoneNumber, ?string $email = null): PaymentTransaction
    {
        return DB::transaction(function () use ($booking, $type, $amount, $customerName, $phoneNumber, $email): PaymentTransaction {
            $payment = WeddingPayment::create([
                'wedding_booking_id' => $booking->id,
                'type' => $type,
                'amount' => $amount,
                'status' => 'pending',
            ]);

            return $this->paymentService->createQrisPayment(
                paymentable: $payment,
                customerName: $customerName,
                phoneNumber: $phoneNumber,
                email: $email,
            );
        });
    }
}

