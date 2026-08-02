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
        return redirect()->route('client.onu-wifi.show');
    }

    public function pay(Invoice $invoice)
    {
        return redirect()->route('client.onu-wifi.show');
    }

    public function show(Invoice $invoice)
    {
        return redirect()->route('client.onu-wifi.show');
    }

    protected function authorizeInvoice(Invoice $invoice): void
    {
        abort_if($invoice->user_id !== Auth::id(), 403);
    }
}
