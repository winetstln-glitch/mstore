<?php

namespace App\Http\Controllers;

use App\Jobs\RenewUserJob;
use App\Models\Invoice;
use App\Services\MidtransService;
use App\Services\MixRadiusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function midtrans(Request $request, MidtransService $midtrans, MixRadiusService $mix)
    {
        return $this->handleNotification($request, $midtrans, $mix);
    }

    public function handleNotification(Request $request, MidtransService $midtrans, MixRadiusService $mix)
    {
        $payload = $request->all();
        if (! $midtrans->verifySignature($payload)) {
            Log::warning('Midtrans signature invalid', $payload);

            return response()->json(['message' => 'invalid signature'], 400);
        }

        $orderId = $payload['order_id'] ?? null;
        if (! $orderId) {
            return response()->json(['message' => 'order_id missing'], 400);
        }

        $transactionStatus = $payload['transaction_status'] ?? '';
        $fraudStatus = $payload['fraud_status'] ?? '';

        if (! in_array($transactionStatus, ['capture', 'settlement']) || $fraudStatus === 'challenge') {
            return response()->json(['message' => 'ignored'], 200);
        }

        $userId = null;
        DB::transaction(function () use ($orderId, &$userId) {
            $invoice = Invoice::where('midtrans_order_id', $orderId)
                ->orWhere('code', $orderId)
                ->lockForUpdate()
                ->first();
            if (! $invoice) {
                throw new \RuntimeException('invoice not found');
            }
            if ($invoice->status !== 'paid') {
                $invoice->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                ]);
                $userId = $invoice->user_id;
            }
        });

        if ($userId) {
            RenewUserJob::dispatch($userId, 'Payment settled: '.$orderId)->onQueue('default');
        }

        return response()->json(['message' => 'ok']);
    }
}
