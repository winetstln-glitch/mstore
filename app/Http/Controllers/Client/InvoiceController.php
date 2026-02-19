<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\MidtransService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class InvoiceController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $invoices = $user->invoices()->latest()->get();
        $clientKey = app(MidtransService::class)->getClientKey();
        $snapJs = config('services.midtrans.is_production', env('MIDTRANS_IS_PRODUCTION')) ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js';
        return view('client.invoices.index', compact('invoices', 'clientKey', 'snapJs'));
    }

    public function pay(Invoice $invoice, MidtransService $midtrans)
    {
        $this->authorizeInvoice($invoice);
        if ($invoice->status === 'paid') {
            return redirect()->route('client.invoices.index')->with('success', 'Invoice sudah dibayar.');
        }
        if (empty($invoice->code)) {
            $invoice->code = 'INV-' . str_pad($invoice->id, 6, '0', STR_PAD_LEFT) . '-' . Str::random(4);
            $invoice->save();
        }
        $token = $midtrans->createSnapToken($invoice);
        $clientKey = $midtrans->getClientKey();
        $snapJs = config('services.midtrans.is_production', env('MIDTRANS_IS_PRODUCTION')) ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js';
        return view('client.invoices.pay', compact('invoice', 'token', 'clientKey', 'snapJs'));
    }

    protected function authorizeInvoice(Invoice $invoice): void
    {
        abort_if($invoice->user_id !== Auth::id(), 403);
    }
}

