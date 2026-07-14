<?php

namespace App\Models;

use App\Services\AccountingPoster;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AtkTransaction extends Model
{

    public $businessUnitCode = 'ATK';
    public $businessUnitName = 'Toko ATK';
    public $businessUnitType = 'RETAIL';
    public $transactionType = 'atk';

    protected $fillable = [
        'user_id', 'transaction_number', 'total_amount', 'transaction_category', 'payment_method', 'is_debt', 'cash_amount', 'change_amount', 'amount_paid', 'coordinator_id', 'customer_name', 'customer_phone', 'due_date', 'is_settled', 'settled_at', 'settled_amount', 'queue_number',
        'transaction_type', 'payment_status', 'journal_status', 'atk_float_account_id', 'grand_total', 'atk_customer_id',
        'status', 'posted_at', 'reversed_at'
    ];

    protected $casts = [
        'grand_total' => 'decimal:2',
    ];

    protected function itemCategories(): Attribute
    {
        return Attribute::make(
            get: function () {
                return $this->items->map(function ($item) {
                    if ($item->item_type === 'customer_payment') return 'Pembayaran Pelanggan';
                    if ($item->item_type === 'cash_out') return 'Cash Out';
                    if ($item->item_type === 'top_up') return 'Top Up';
                    if ($item->item_type === 'ppob') return 'PPOB';
                    if ($item->product) return $item->product->category;
                    return '-';
                })->unique()->implode(', ');
            }
        );
    }

    protected function productNames(): Attribute
    {
        return Attribute::make(
            get: function () {
                $names = $this->items->map(function ($item) {
                    return $item->product_name ?? '-';
                })->unique()->take(3)->values();
                
                return [
                    'display' => $names->implode(', ') . ($this->items->count() > 3 ? '...' : ''),
                    'total' => $this->items->count(),
                ];
            }
        );
    }

    public function items()
    {
        return $this->hasMany(AtkTransactionItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function coordinator()
    {
        return $this->belongsTo(Coordinator::class);
    }

    public function floatAccount(): BelongsTo
    {
        return $this->belongsTo(AtkFloatAccount::class, 'atk_float_account_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(AtkCustomer::class, 'atk_customer_id');
    }

    public function cashMovements()
    {
        return $this->hasMany(AtkCashMovement::class);
    }

    public function generalTransactions()
    {
        return $this->morphMany(GeneralTransaction::class, 'reference');
    }

    public function syncAccountingJournal(): void
    {
        if (! Schema::hasTable('journals') || ! Schema::hasTable('journal_entries') || ! Schema::hasTable('accounts')) {
            return;
        }

        if (method_exists(Account::class, 'ensureDefaultChart')) {
            Account::ensureDefaultChart();
        }
        $this->loadMissing(['items.product']);

        $sumBankNominal = 0;
        $sumFee = 0;
        $sumRevenueSales = 0;
        $sumPPOBTopUpNominal = 0;
        $sumCustomerPaymentNominal = 0;
        $sumCashOutNominal = 0; // Track nominal cash given to customer
        $hpp = 0;

        foreach ($this->items as $item) {
            $itemType = $item->item_type ?? '';
            $category = strtoupper((string) ($item->product?->category ?? ''));
            $isService = $category === 'JASA POTOCOPY';
            $isBank = $category === 'JASA TRANSFER BANK';

            if ($itemType === 'customer_payment') {
                $sumCustomerPaymentNominal += (float) ($item->subtotal ?? 0);
            } elseif ($itemType === 'top_up' || $itemType === 'ppob') {
                $sumPPOBTopUpNominal += (float) ($item->nominal_transaksi ?? 0);
                $sumFee += (float) ($item->fee ?? 0);
            } elseif ($itemType === 'cash_out') {
                $sumCashOutNominal += (float) ($item->nominal_transaksi ?? 0); // Track nominal cash out
                $sumFee += (float) ($item->fee ?? 0);
            } elseif ($isBank) {
                $sumBankNominal += (float) ($item->nominal_transaksi ?? 0);
                $sumFee += (float) ($item->fee ?? 0);
            } else {
                $sumRevenueSales += (float) ($item->subtotal ?? 0);
            }

            if (! $isService && ! $isBank && !in_array($itemType, ['top_up', 'ppob', 'cash_out', 'customer_payment'])) {
                $hpp += ((float) ($item->product?->cost_price ?? 0)) * (int) ($item->quantity ?? 0);
            }
        }

        $drCode = $this->payment_method === 'hutang' ? '1101' : ($this->payment_method === 'cash' ? '1001' : '1002');
        $drAccId = Account::where('code', $drCode)->value('id');
        $revAtkId = Account::where('code', '4003')->value('id');
        $revBankId = Account::where('code', '4004')->value('id');
        $depositId = Account::where('code', '1401')->value('id');
        $hppId = Account::where('code', '5001')->value('id');
        $inventoryId = Account::where('code', '1201')->value('id');
        $customerReceivableId = Account::where('code', '1101')->value('id');
        $floatAccId = Account::where('code', '1002')->value('id');
        $cashAccId = Account::where('code', '1001')->value('id');

        DB::transaction(function () use ($drAccId, $revAtkId, $revBankId, $depositId, $hppId, $inventoryId, $sumRevenueSales, $sumFee, $sumBankNominal, $sumPPOBTopUpNominal, $sumCustomerPaymentNominal, $sumCashOutNominal, $hpp, $customerReceivableId, $floatAccId, $cashAccId): void {
            $journals = Journal::where('source_type', 'atk_transaction')
                ->where('source_id', $this->id)
                ->with('entries')
                ->get();

            foreach ($journals as $journal) {
                foreach ($journal->entries as $entry) {
                    $entry->delete();
                }
                $journal->delete();
            }

            $lines = [];

            // Handle customer payment
            if ($sumCustomerPaymentNominal > 0 && $customerReceivableId) {
                if ($drAccId) {
                    $lines[] = ['account_id' => $drAccId, 'debit' => $sumCustomerPaymentNominal, 'credit' => 0, 'unit' => 'ATK'];
                }
                $lines[] = ['account_id' => $customerReceivableId, 'debit' => 0, 'credit' => $sumCustomerPaymentNominal, 'unit' => 'ATK'];
            }

            // Cash Out specific journal: We receive float increase, give cash out
            if ($sumCashOutNominal > 0) {
                // Debit Float (because float account received money from customer)
                if ($floatAccId) {
                    $lines[] = ['account_id' => $floatAccId, 'debit' => $sumCashOutNominal + $sumFee, 'credit' => 0, 'unit' => 'ATK'];
                }
                // Credit Cash (because we gave cash out to customer)
                if ($cashAccId) {
                    $lines[] = ['account_id' => $cashAccId, 'debit' => 0, 'credit' => $sumCashOutNominal, 'unit' => 'ATK'];
                }
                // The remaining is our fee (credit to revenue) - we add that later below
            }

            // Calculate total transaction amount excluding customer payment AND cash out (since cash out handled separately)
            $totalTransactionAmount = $sumRevenueSales + $sumBankNominal + $sumPPOBTopUpNominal;
            if ($totalTransactionAmount > 0 && $drAccId) {
                $lines[] = ['account_id' => $drAccId, 'debit' => $totalTransactionAmount, 'credit' => 0, 'unit' => 'ATK'];
            }

            // Credit for revenue
            if ($sumRevenueSales > 0 && $revAtkId) {
                $lines[] = ['account_id' => $revAtkId, 'debit' => 0, 'credit' => $sumRevenueSales, 'unit' => 'ATK'];
            }

            // Credit for fees
            if ($sumFee > 0 && $revBankId) {
                $lines[] = ['account_id' => $revBankId, 'debit' => 0, 'credit' => $sumFee, 'unit' => 'ATK'];
            }

            // Credit for bank transfer nominal
            if ($sumBankNominal > 0 && $depositId) {
                $lines[] = ['account_id' => $depositId, 'debit' => 0, 'credit' => $sumBankNominal, 'unit' => 'ATK'];
            }

            // Credit for PPOB and top up nominal
            if ($sumPPOBTopUpNominal > 0 && $depositId) {
                $lines[] = ['account_id' => $depositId, 'debit' => 0, 'credit' => $sumPPOBTopUpNominal, 'unit' => 'ATK'];
            }

            // COGS entries
            if ($hpp > 0 && $hppId && $inventoryId) {
                $lines[] = ['account_id' => $hppId, 'debit' => $hpp, 'credit' => 0, 'unit' => 'ATK'];
                $lines[] = ['account_id' => $inventoryId, 'debit' => 0, 'credit' => $hpp, 'unit' => 'ATK'];
            }

            if (count($lines) > 0) {
                $date = $this->created_at?->toDateString() ?? now()->toDateString();
                $poster = app(AccountingPoster::class);
                $poster->post(
                    'ATK-'.($this->transaction_number ?: $this->id),
                    $date,
                    'ATK POS',
                    $lines,
                    null,
                    'atk_transaction',
                    $this->id
                );
            }
        });
    }
}
