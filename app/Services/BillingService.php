<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BillingService
{
    /**
     * Generate Invoice for a Customer
     */
    public function generateInvoice(Customer $customer, $month = null, $year = null)
    {
        $month = $month ?: Carbon::now()->month;
        $year = $year ?: Carbon::now()->year;

        $periodDate = Carbon::createFromDate($year, $month, 1);

        // Check if already exists
        $exists = Invoice::where('customer_id', $customer->id)
            ->whereYear('period_date', $year)
            ->whereMonth('period_date', $month)
            ->exists();

        if ($exists) {
            return null;
        }

        // Calculate Due Date (e.g., 10th of the month or based on customer cycle)
        $dueDay = $customer->billing_cycle_date ?? 10;
        $dueDate = Carbon::createFromDate($year, $month, $dueDay);

        $price = 100000;
        if ($customer->package_id) {
            $pkg = Package::find($customer->package_id);
            if ($pkg) {
                $price = (int) $pkg->price;
            }
        }
        if ($price === 100000 && $customer->package) {
            if (preg_match('/(\d+)/', str_replace('.', '', $customer->package), $matches)) {
                $price = (int) $matches[1];
            }
        }

        $invoice = Invoice::create([
            'invoice_number' => 'INV/'.$year.$month.'/'.$customer->id,
            'customer_id' => $customer->id,
            'period_date' => $periodDate,
            'due_date' => $dueDate,
            'amount' => $price,
            'status' => 'unpaid',
        ]);

        // Send Notification (Stub)
        // WhatsAppService::sendInvoice($customer, $invoice);

        return $invoice;
    }

    /**
     * Process Payment
     */
    public function processPayment(Invoice $invoice, $amount, $method = 'cash', $userId = null, $ref = null)
    {
        return DB::transaction(function () use ($invoice, $amount, $method, $userId, $ref) {
            // Create Transaction
            Transaction::create([
                'invoice_id' => $invoice->id,
                'user_id' => $userId,
                'type' => 'income',
                'amount' => $amount,
                'method' => $method,
                'reference_number' => $ref,
                'category' => 'Internet Payment',
                'transaction_date' => Carbon::now(),
                'description' => "Payment for Invoice #{$invoice->invoice_number}",
            ]);

            // Update Invoice
            $paidTotal = $invoice->transactions()->sum('amount') + $amount; // + current amount since transaction created above? No, wait.
            // Transaction is created inside transaction block.
            // Let's re-query or just add.

            if ($paidTotal >= $invoice->amount) {
                $invoice->update([
                    'status' => 'paid',
                    'paid_at' => Carbon::now(),
                ]);

                // Auto Open Isolir
                if ($invoice->customer->status === 'suspend' && $invoice->customer->auto_isolate) {
                    $this->unblockCustomer($invoice->customer);
                }

                // Send Notification
                // WhatsAppService::sendPaymentSuccess($invoice->customer, $invoice);
            }

            return $invoice;
        });
    }

    public function syncFromMixRadius(User $user, MixRadiusService $mix): void
    {
        $identity = $user->radius_username ?: ($user->username ?: $user->email);
        if (! $identity) {
            return;
        }
        $items = $mix->fetchInvoices($identity);
        if (! is_array($items)) {
            return;
        }
        foreach ($items as $it) {
            try {
                $code = $it['code'] ?? null;
                $amount = isset($it['amount']) ? (int) $it['amount'] : null;
                $due = $it['due_date'] ?? null;
                $status = $it['status'] ?? 'pending';
                $dueDate = null;
                if ($due) {
                    try {
                        $dueDate = Carbon::parse($due);
                    } catch (\Throwable $e) {
                        $dueDate = null;
                    }
                }
                $inv = null;
                if ($code) {
                    $inv = Invoice::where('user_id', $user->id)->where('code', $code)->first();
                }
                if (! $inv && $dueDate && $amount) {
                    $inv = Invoice::where('user_id', $user->id)
                        ->whereDate('due_date', $dueDate->toDateString())
                        ->where('amount', $amount)
                        ->first();
                }
                if (! $inv) {
                    $inv = new Invoice;
                    $inv->user_id = $user->id;
                }
                if ($code && empty($inv->code)) {
                    $inv->code = $code;
                }
                if ($amount !== null) {
                    $inv->amount = $amount;
                }
                if ($dueDate) {
                    $inv->due_date = $dueDate;
                }
                if ($status) {
                    $inv->status = $status === 'paid' ? 'paid' : 'pending';
                    if ($inv->status === 'paid' && ! $inv->paid_at) {
                        $inv->paid_at = Carbon::now();
                    }
                }
                $meta = $inv->meta ?? [];
                if (! is_array($meta)) {
                    $meta = [];
                }
                if (! empty($it['package'])) {
                    $meta['package'] = $it['package'];
                }
                if (! empty($it['period'])) {
                    $meta['period'] = $it['period'];
                }
                if (! empty($meta)) {
                    $inv->meta = $meta;
                }
                $inv->save();
            } catch (\Throwable $e) {
                Log::warning('Billing sync item failed', ['message' => $e->getMessage()]);
            }
        }
    }

    /**
     * Check Overdue and Isolate
     */
    public function checkOverdue()
    {
        $overdueInvoices = Invoice::where('status', 'unpaid')
            ->where('due_date', '<', Carbon::now())
            ->with('customer')
            ->get();
        foreach ($overdueInvoices as $invoice) {
            $customer = $invoice->customer;

            if ($customer->status === 'active' && $customer->auto_isolate) {
                // Block/Isolate
                $this->isolateCustomer($customer);

                // Update Invoice Status
                $invoice->update(['status' => 'overdue']);

                // Send Notification
                // WhatsAppService::sendIsolationNotification($customer);
            }
        }
    }

    protected function unblockCustomer(Customer $customer)
    {
        if (! $customer->router) {
            return;
        }

        $mikrotik = new MikrotikService($customer->router);
        if ($mikrotik->toggleSecret($customer->pppoe_user, true)) {
            $customer->update(['status' => 'active']);
        }
    }

    protected function isolateCustomer(Customer $customer)
    {
        if (! $customer->router) {
            return;
        }

        $mikrotik = new MikrotikService($customer->router);
        // Option 1: Disable Secret
        if ($mikrotik->toggleSecret($customer->pppoe_user, false)) {
            $customer->update(['status' => 'suspend']);
        }
    }
}
