<?php

namespace App\Http\Controllers;

use App\Models\WeddingBooking;
use App\Models\WeddingPayment;
use App\Services\AuditLogService;
use App\Services\Wedding\WeddingPaymentService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class WeddingPaymentController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly WeddingPaymentService $paymentService,
        private readonly AuditLogService $auditLogService,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:wedding.payment'),
        ];
    }

    public function index(Request $request)
    {
        $payments = WeddingPayment::query()
            ->with(['booking.package', 'paymentTransaction'])
            ->latest()
            ->paginate(20);

        return view('wedding.payments.index', compact('payments'));
    }

    public function create(WeddingBooking $booking)
    {
        $booking->loadMissing('package');
        return view('wedding.payments.create', compact('booking'));
    }

    public function store(Request $request, WeddingBooking $booking)
    {
        $booking->loadMissing('package');

        $validated = $request->validate([
            'type' => ['required', 'in:dp,final'],
            'amount' => ['nullable', 'integer', 'min:1000'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        $type = (string) $validated['type'];
        $amount = $validated['amount'] ?? null;

        if ($amount === null) {
            if ($type === 'dp') {
                $base = (int) ($booking->quotation_amount ?? $booking->package?->price ?? 0);
                $amount = (int) round($base * 0.3);
            } else {
                $amount = (int) ($booking->quotation_amount ?? $booking->package?->price ?? 0);
            }
        }

        $transaction = $this->paymentService->createQris(
            booking: $booking,
            type: $type,
            amount: (int) $amount,
            customerName: $booking->customer_name,
            phoneNumber: $booking->customer_whatsapp,
            email: $validated['email'] ?? null,
        );

        $this->auditLogService->logAction('wedding.payment.created', $transaction->paymentable, [], $transaction->paymentable->toArray());

        return redirect()->route('wedding.payments.show', $transaction->paymentable)->with('success', 'QRIS berhasil dibuat.');
    }

    public function show(WeddingPayment $payment)
    {
        $payment->loadMissing(['booking.package', 'paymentTransaction']);
        return view('wedding.payments.show', compact('payment'));
    }
}

