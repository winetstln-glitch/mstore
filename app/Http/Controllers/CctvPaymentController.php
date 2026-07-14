<?php

namespace App\Http\Controllers;

use App\Models\CctvBooking;
use App\Models\CctvPayment;
use App\Services\AuditLogService;
use App\Services\Cctv\CctvPaymentService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class CctvPaymentController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly CctvPaymentService $paymentService,
        private readonly AuditLogService $auditLogService,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:cctv.payment'),
        ];
    }

    public function index()
    {
        $payments = CctvPayment::query()
            ->with(['booking.package', 'paymentTransaction'])
            ->latest()
            ->paginate(20);

        return view('cctv.payments.index', compact('payments'));
    }

    public function create(CctvBooking $booking)
    {
        $booking->loadMissing('package');
        return view('cctv.payments.create', compact('booking'));
    }

    public function store(Request $request, CctvBooking $booking)
    {
        $booking->loadMissing('package');

        $validated = $request->validate([
            'type' => ['required', 'in:dp,final'],
            'amount' => ['required', 'integer', 'min:1000'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        $transaction = $this->paymentService->createQris(
            booking: $booking,
            type: (string) $validated['type'],
            amount: (int) $validated['amount'],
            customerName: $booking->customer_name,
            phoneNumber: $booking->customer_whatsapp,
            email: $validated['email'] ?? null,
        );

        $this->auditLogService->logAction('cctv.payment.created', $transaction->paymentable, [], $transaction->paymentable->toArray());

        return redirect()->route('cctv.payments.show', $transaction->paymentable)->with('success', 'QRIS berhasil dibuat.');
    }

    public function show(CctvPayment $payment)
    {
        $payment->loadMissing(['booking.package', 'paymentTransaction']);
        return view('cctv.payments.show', compact('payment'));
    }
}

