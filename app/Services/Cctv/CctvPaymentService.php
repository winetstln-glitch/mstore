<?php

namespace App\Services\Cctv;

use App\Models\CctvBooking;
use App\Models\CctvPayment;
use App\Models\PaymentTransaction;
use App\Services\PaymentService;
use Illuminate\Support\Facades\DB;

class CctvPaymentService
{
    public function __construct(
        private readonly PaymentService $paymentService,
    ) {}

    public function createQris(CctvBooking $booking, string $type, int $amount, string $customerName, string $phoneNumber, ?string $email = null): PaymentTransaction
    {
        return DB::transaction(function () use ($booking, $type, $amount, $customerName, $phoneNumber, $email): PaymentTransaction {
            $payment = CctvPayment::create([
                'cctv_booking_id' => $booking->id,
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

