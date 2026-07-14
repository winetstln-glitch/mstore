<?php

namespace App\Models;

use App\Services\AccountingPoster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AtkFloatTransaction extends Model
{
    protected $fillable = [
        'atk_float_account_id',
        'transaction_type',
        'amount',
        'balance_before',
        'balance_after',
        'reference_type',
        'reference_id',
        'description',
        'created_by',
        'reversed_at',
        'reversed_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(AtkFloatAccount::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reversedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }

    public function syncAccountingJournal(): void
    {
        if (! Schema::hasTable('journals') || ! Schema::hasTable('journal_entries') || ! Schema::hasTable('accounts')) {
            return;
        }

        if (method_exists(Account::class, 'ensureDefaultChart')) {
            Account::ensureDefaultChart();
        }

        DB::transaction(function () {
            // Delete old journal for this transaction
            $journals = Journal::where('source_type', 'atk_float_transaction')
                ->where('source_id', $this->id)
                ->with('entries')
                ->get();

            foreach ($journals as $journal) {
                foreach ($journal->entries as $entry) {
                    $entry->delete();
                }
                $journal->delete();
            }

            $cashAccId = Account::where('code', '1001')->value('id');
            $floatAccId = Account::where('code', '1002')->value('id');
            if (! $cashAccId || ! $floatAccId) {
                return;
            }

            $lines = [];
        // Transaction types that increase float (deposit): 'deposit', 'transfer_in', 'adjustment' (assume adjustment increases balance)
        // Transaction types that decrease float (withdrawal): 'topup', 'ppob', 'withdrawal'
        $isDeposit = in_array($this->transaction_type, ['deposit', 'transfer_in', 'adjustment']);

        if ($isDeposit) {
            // Deposit Float / Adjustment: Debit Float, Kredit Kas
            $lines[] = ['account_id' => $floatAccId, 'debit' => $this->amount, 'credit' => 0, 'unit' => 'ATK'];
            $lines[] = ['account_id' => $cashAccId, 'debit' => 0, 'credit' => $this->amount, 'unit' => 'ATK'];
        } else {
            // Penarikan Float (Top Up/PPOB): Debit Kas, Kredit Float
            $lines[] = ['account_id' => $cashAccId, 'debit' => $this->amount, 'credit' => 0, 'unit' => 'ATK'];
            $lines[] = ['account_id' => $floatAccId, 'debit' => 0, 'credit' => $this->amount, 'unit' => 'ATK'];
        }

            if (count($lines) > 0) {
                $date = $this->created_at?->toDateString() ?? now()->toDateString();
                $poster = app(AccountingPoster::class);
                $poster->post(
                    'ATK-FT-' . ($this->id),
                    $date,
                    $this->description ?: 'Pergerakan Float ATK',
                    $lines,
                    null,
                    'atk_float_transaction',
                    $this->id
                );
            }
        });
    }
}
