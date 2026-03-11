<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\Router;
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
        if ($price === 100000 && $customer->package) {
            if (preg_match('/(\d+)/', str_replace('.', '', $customer->package), $matches)) {
                $price = (int) $matches[1];
            }
        }

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
                    $this->unblockCustomer($customer);
                }
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
        $overdueInvoices = Invoice::where('status', 'pending')
            ->whereDate('due_date', '<', Carbon::today())
            ->get();
        foreach ($overdueInvoices as $invoice) {
            $customer = $this->findCustomerByInvoice($invoice);
            if (! $customer) {
                continue;
            }

            if ($customer->status === 'active' && $customer->auto_isolate) {
                $this->isolateCustomer($customer);
                $meta = $invoice->meta ?? [];
                if (! is_array($meta)) {
                    $meta = [];
                }
                $meta['overdue_checked_at'] = Carbon::now()->toDateTimeString();
                $invoice->update(['meta' => $meta]);
            }
        }
    }

    protected function unblockCustomer(Customer $customer)
    {
        $router = $this->findRouterForCustomer($customer);
        if (! $router || ! $this->executeToggleSecret($router, $customer->pppoe_user, true)) {
            return;
        }

        $customer->update(['status' => 'active']);
    }

    protected function isolateCustomer(Customer $customer)
    {
        $router = $this->findRouterForCustomer($customer);
        if (! $router || ! $this->executeToggleSecret($router, $customer->pppoe_user, false)) {
            return;
        }

        $customer->update(['status' => 'suspend']);
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

    protected function findRouterForCustomer(Customer $customer): ?Router
    {
        if (! $customer->pppoe_user) {
            return null;
        }

        $routers = Router::query()->where('is_active', true)->get();
        foreach ($routers as $router) {
            if ($this->hasPppoeSecret($router, $customer->pppoe_user)) {
                return $router;
            }
        }

        return null;
    }

    protected function hasPppoeSecret(Router $router, string $pppoeUser): bool
    {
        $mikrotik = new MikrotikService($router);
        if (! $mikrotik->isConnected()) {
            return false;
        }
        $secrets = $mikrotik->getSecrets();
        foreach ($secrets as $secret) {
            if (($secret['name'] ?? null) === $pppoeUser) {
                return true;
            }
        }

        return false;
    }

    protected function executeToggleSecret(Router $router, string $pppoeUser, bool $enable): bool
    {
        $mikrotik = new MikrotikService($router);
        if (! $mikrotik->isConnected()) {
            return false;
        }

        return $mikrotik->toggleSecret($pppoeUser, $enable);
    }
}
