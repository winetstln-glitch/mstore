<?php

namespace App\Http\Controllers;

use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService
    ) {}

    public function callback(Request $request)
    {
        Log::info('Payment Callback Received', $request->all());

        try {
            $this->paymentService->processCallback($request->all());
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Payment Callback Error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function return(Request $request)
    {
        return redirect()->route('home')->with('status', 'Pembayaran selesai diproses!');
    }
}
