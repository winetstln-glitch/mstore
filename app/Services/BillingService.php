<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Network\Events\Domain\CustomerSuspended;
use Modules\Network\Events\Domain\CustomerUnsuspended;

class BillingService
{
    /**
     * Generate Invoice for a Customer
     */
    public function generateInvoice(Customer $customer, $month = null, $year = null)
    {
        if (! $customer->user_id) {
            return null;
        }

        $month = $month ?: Carbon::now()->month;
        $year = $year ?: Carbon::now()->year;
        $period = sprintf('%04d-%02d', (int) $year, (int) $month);
        $code = 'INV-'.$year.str_pad((string) $month, 2, '0', STR_PAD_LEFT).'-'.str_pad((string) $customer->user_id, 6, '0', STR_PAD_LEFT);
        $exists = Invoice::where('user_id', $customer->user_id)
            ->where('code', $code)
            ->exists();

        if ($exists) {
            return null;
        }

        $dueDay = (int) ($customer->billing_cycle_date ?? 10);
        $dueDay = max(1, min($dueDay, 28));
        $dueDate = Carbon::createFromDate($year, $month, $dueDay);

        $price = 100000;
        if ($customer->package_id) {
            $pkg = Package::find($customer->package_id);
            if ($pkg) {
                $price = (int) $pkg->price;
            }
        }
        // Hapus fallback regex dari nama paket: terlalu tidak andal karena bisa menangkap
        // angka kecepatan (misal 50 dari "50Mbps") alih-alih harga yang sebenarnya.
        // Harga default Rp 100.000 digunakan jika package_id tidak ditemukan.

        $invoice = Invoice::create([
            'user_id' => $customer->user_id,
            'code' => $code,
            'due_date' => $dueDate,
            'amount' => $price,
            'status' => 'pending',
            'meta' => array_filter([
                'customer_id' => $customer->id,
                'period' => $period,
                'package' => $customer->package ?? null,
            ]),
        ]);

        return $invoice;
    }

    /**
     * Process Payment
     */
    public function processPayment(Invoice $invoice, $amount, $method = 'cash', $userId = null, $ref = null)
    {
        return DB::transaction(function () use ($invoice, $amount, $method, $userId, $ref) {
            $reference = $ref ?: ($invoice->code ?: 'INV-'.$invoice->id);
            Transaction::create([
                'user_id' => $userId,
                'type' => 'income',
                'amount' => $amount,
                'reference_number' => $reference,
                'category' => 'Internet Payment',
                'transaction_date' => Carbon::now(),
                'description' => 'Payment for invoice '.($invoice->code ?: '#'.$invoice->id).' via '.$method,
            ]);

            $paidTotal = (float) Transaction::where('reference_number', $reference)->sum('amount');

            if ($paidTotal >= $invoice->amount) {
                $invoice->update([
                    'status' => 'paid',
                    'paid_at' => Carbon::now(),
                ]);

                $customer = $this->findCustomerByInvoice($invoice);
                if ($customer && $customer->status === 'suspend' && $customer->auto_isolate) {
                    $customer->update(['status' => 'active']);
                    event(new CustomerUnsuspended($customer));
                }
            }

            return $invoice;
        });
    }

    /**
     * Check Overdue and Isolate
     */
    public function checkOverdue()
    {
        $overdueInvoices = Invoice::where('status', 'pending')
            ->whereDate('due_date', '<', Carbon::today())
            ->get();

        foreach ($overdueInvoices as $invoice) {
            $customer = $this->findCustomerByInvoice($invoice);
            if (! $customer) {
                continue;
            }

            if ($customer->status === 'active' && $customer->auto_isolate) {
                DB::transaction(function () use ($customer, $invoice) {
                    $customer->update(['status' => 'suspend']);

                    $meta = $invoice->meta ?? [];
                    if (! is_array($meta)) {
                        $meta = [];
                    }
                    $meta['overdue_checked_at'] = Carbon::now()->toDateTimeString();
                    $invoice->update(['meta' => $meta]);
                });

                // Event dikirim setelah transaksi commit agar tidak trigger di-rollback
                event(new CustomerSuspended($customer));
            }
        }
    }

    protected function findCustomerByInvoice(Invoice $invoice): ?Customer
    {
        if ($invoice->user_id) {
            $customer = Customer::where('user_id', $invoice->user_id)->first();
            if ($customer) {
                return $customer;
            }
        }

        $meta = $invoice->meta ?? [];
        if (! is_array($meta) || empty($meta['customer_id'])) {
            return null;
        }

        return Customer::find($meta['customer_id']);
    }
}
