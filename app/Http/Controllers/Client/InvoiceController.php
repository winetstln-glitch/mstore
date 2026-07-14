<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Setting;
use App\Services\Payment\PaymentManager;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class InvoiceController extends Controller
{
    protected $paymentManager;

    public function __construct(PaymentManager $paymentManager)
    {
        $this->paymentManager = $paymentManager;
    }

    public function index()
    {
        $user = Auth::user();
        $invoices = $user->invoices()->latest()->get();
        
        $midtrans = $this->paymentManager->gateway('midtrans');
        $clientKey = Setting::getValue('midtrans_client_key');
        $isProd = Setting::getValue('midtrans_sandbox') == '0';
        $snapJs = $isProd ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js';

        return view('client.invoices.index', compact('invoices', 'clientKey', 'snapJs'));
    }

    public function pay(Invoice $invoice)
    {
        $this->authorizeInvoice($invoice);
        if ($invoice->status === 'paid') {
            return redirect()->route('client.invoices.index')->with('success', 'Invoice sudah dibayar.');
        }
        if (empty($invoice->code)) {
            $invoice->code = 'INV-'.str_pad($invoice->id, 6, '0', STR_PAD_LEFT).'-'.Str::random(4);
            $invoice->save();
        }

        $midtrans = $this->paymentManager->gateway('midtrans');
        $payload = [
            'amount' => $invoice->amount,
            'reference_id' => $invoice->code,
            'description' => 'Invoice ' . $invoice->code,
            'customer_name' => Auth::user()->name,
            'customer_email' => Auth::user()->email,
            'customer_phone' => Auth::user()->phone,
        ];

        $transaction = $midtrans->createTransaction($payload);
        $token = $transaction['token'] ?? '';
        
        $clientKey = Setting::getValue('midtrans_client_key');
        $isProd = Setting::getValue('midtrans_sandbox') == '0';
        $snapJs = $isProd ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js';

        $invoice->update([
            'midtrans_order_id' => $invoice->code,
            'snap_token' => $token,
        ]);

        return view('client.invoices.pay', compact('invoice', 'token', 'clientKey', 'snapJs'));
    }

    public function show(Invoice $invoice)
    {
        $this->authorizeInvoice($invoice);
        $user = Auth::user();
        $customer = $user->customer ?? null;
        $devicesCount = $customer ? $customer->devices()->count() : 0;

        return view('client.invoices.show', compact('invoice', 'user', 'customer', 'devicesCount'));
    }

    protected function authorizeInvoice(Invoice $invoice): void
    {
        abort_if($invoice->user_id !== Auth::id(), 403);
    }
}
