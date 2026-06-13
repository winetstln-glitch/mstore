<?php

namespace App\Models;

use App\Services\AccountingPoster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AtkTransaction extends Model
{
    protected $fillable = [
        'user_id', 'transaction_number', 'total_amount', 'transaction_category', 'payment_method', 'is_debt', 'cash_amount', 'change_amount', 'amount_paid', 'coordinator_id', 'customer_name', 'customer_phone', 'due_date', 'is_settled', 'settled_at', 'settled_amount', 'queue_number',
        'transaction_type', 'payment_status', 'journal_status', 'atk_cash_register_id', 'atk_float_account_id', 'grand_total', 'atk_customer_id',
    ];

    protected $casts = [
        'grand_total' => 'decimal:2',
    ];

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

    public function register(): BelongsTo
    {
        return $this->belongsTo(AtkCashRegister::class, 'atk_cash_register_id');
    }

    public function floatAccount(): BelongsTo
    {
        return $this->belongsTo(AtkFloatAccount::class, 'atk_float_account_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(AtkCustomer::class, 'atk_customer_id');
    }

    public function syncAccountingJournal(): void
    {
        if (! Schema::hasTable('journals') || ! Schema::hasTable('journal_entries') || ! Schema::hasTable('accounts')) {
            return;
        }

        Account::ensureDefaultChart();
        $this->loadMissing(['items.product']);

        $sumBankNominal = 0;
        $sumFee = 0;
        $sumRevenueSales = 0;
        $hpp = 0;

        foreach ($this->items as $item) {
            $category = strtoupper((string) ($item->product?->category ?? ''));
            $isService = $category === 'JASA POTOCOPY';
            $isBank = $category === 'JASA TRANSFER BANK';

            if ($isBank) {
                $sumBankNominal += (float) ($item->nominal_transaksi ?? 0);
                $sumFee += (float) ($item->fee ?? 0);
            } else {
                $sumRevenueSales += (float) ($item->subtotal ?? 0);
            }

            if (! $isService && ! $isBank) {
                $hpp += ((float) ($item->product?->cost_price ?? 0)) * (int) ($item->quantity ?? 0);
            }
        }

        $total = (float) ($this->total_amount ?? 0);
        $drCode = $this->payment_method === 'hutang' ? '1101' : ($this->payment_method === 'cash' ? '1001' : '1002');
        $drAccId = Account::where('code', $drCode)->value('id');
        $revAtkId = Account::where('code', '4003')->value('id');
        $revBankId = Account::where('code', '4004')->value('id');
        $depositId = Account::where('code', '1401')->value('id');
        $hppId = Account::where('code', '5001')->value('id');
        $inventoryId = Account::where('code', '1201')->value('id');

        DB::transaction(function () use ($drAccId, $revAtkId, $revBankId, $depositId, $hppId, $inventoryId, $total, $sumRevenueSales, $sumFee, $sumBankNominal, $hpp): void {
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

            if (! $drAccId || ! $revAtkId || ! $revBankId || ! $depositId || $total <= 0) {
                return;
            }

            $lines = [
                ['account_id' => $drAccId, 'debit' => $total, 'credit' => 0, 'unit' => 'ATK'],
            ];
            if ($sumRevenueSales > 0) {
                $lines[] = ['account_id' => $revAtkId, 'debit' => 0, 'credit' => $sumRevenueSales, 'unit' => 'ATK'];
            }
            if ($sumFee > 0) {
                $lines[] = ['account_id' => $revBankId, 'debit' => 0, 'credit' => $sumFee, 'unit' => 'ATK'];
            }
            if ($sumBankNominal > 0) {
                $lines[] = ['account_id' => $depositId, 'debit' => 0, 'credit' => $sumBankNominal, 'unit' => 'ATK'];
            }
            if ($hpp > 0 && $hppId && $inventoryId) {
                $lines[] = ['account_id' => $hppId, 'debit' => $hpp, 'credit' => 0, 'unit' => 'ATK'];
                $lines[] = ['account_id' => $inventoryId, 'debit' => 0, 'credit' => $hpp, 'unit' => 'ATK'];
            }

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
        });
    }
}
