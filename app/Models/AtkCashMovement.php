<?php

namespace App\Models;

use App\Services\AccountingPoster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AtkCashMovement extends Model
{
    protected $fillable = [
        'cash_id',
        'atk_cash_register_id',
        'atk_transaction_id',
        'movement_type',
        'direction',
        'amount',
        'balance_before',
        'balance_after',
        'reference_type',
        'reference_id',
        'idempotency_key',
        'description',
        'occurred_at',
        'reversed_at',
        'reversed_by',
        'reversal_of_id',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'occurred_at' => 'datetime',
        'reversed_at' => 'datetime',
    ];

    public function cash(): BelongsTo
    {
        return $this->belongsTo(Cash::class);
    }

    public function atkTransaction(): BelongsTo
    {
        return $this->belongsTo(AtkTransaction::class);
    }

    public function register(): BelongsTo
    {
        return $this->belongsTo(AtkCashRegister::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reversedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }

    public function reversalOf(): BelongsTo
    {
        return $this->belongsTo(AtkCashMovement::class, 'reversal_of_id');
    }

    public function reversal(): BelongsTo
    {
        return $this->hasOne(AtkCashMovement::class, 'reversal_of_id');
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
            // Delete old journal for this movement
            $journals = Journal::where('source_type', 'atk_cash_movement')
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
            if (! $cashAccId) {
                return;
            }

            $lines = [];
            $isIncoming = $this->direction === 'in';

            if ($isIncoming) {
                // Kas Masuk: Debit Kas, Kredit sumber
                $lines[] = ['account_id' => $cashAccId, 'debit' => $this->amount, 'credit' => 0, 'unit' => 'ATK'];
                // Tentukan akun kredit berdasarkan movement_type
                if ($this->movement_type === 'owner_loan') {
                    $ownerFundAccId = Account::where('code', '2101')->value('id');
                    if ($ownerFundAccId) {
                        $lines[] = ['account_id' => $ownerFundAccId, 'debit' => 0, 'credit' => $this->amount, 'unit' => 'ATK'];
                    }
                } else {
                    $revenueAccId = Account::where('code', '4003')->value('id');
                    if ($revenueAccId) {
                        $lines[] = ['account_id' => $revenueAccId, 'debit' => 0, 'credit' => $this->amount, 'unit' => 'ATK'];
                    }
                }
            } else {
                // Kas Keluar: Debit tujuan, Kredit Kas
                if ($this->movement_type === 'expense') {
                    $expenseAccId = Account::where('code', '6004')->value('id');
                    if ($expenseAccId) {
                        $lines[] = ['account_id' => $expenseAccId, 'debit' => $this->amount, 'credit' => 0, 'unit' => 'ATK'];
                    }
                } elseif ($this->movement_type === 'owner_repayment') {
                    $ownerFundAccId = Account::where('code', '2101')->value('id');
                    if ($ownerFundAccId) {
                        $lines[] = ['account_id' => $ownerFundAccId, 'debit' => $this->amount, 'credit' => 0, 'unit' => 'ATK'];
                    }
                } else {
                    $floatAccId = Account::where('code', '1002')->value('id');
                    if ($floatAccId) {
                        $lines[] = ['account_id' => $floatAccId, 'debit' => $this->amount, 'credit' => 0, 'unit' => 'ATK'];
                    }
                }
                $lines[] = ['account_id' => $cashAccId, 'debit' => 0, 'credit' => $this->amount, 'unit' => 'ATK'];
            }

            if (count($lines) > 0) {
                $date = $this->occurred_at?->toDateString() ?? $this->created_at?->toDateString() ?? now()->toDateString();
                $poster = app(AccountingPoster::class);
                $poster->post(
                    'ATK-CM-' . ($this->id),
                    $date,
                    $this->description ?: 'Pergerakan Kas ATK',
                    $lines,
                    null,
                    'atk_cash_movement',
                    $this->id
                );
            }
        });
    }
}
