<?php

namespace App\Models;

use App\Services\AccountingPoster;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'coordinator_id',
        'investor_id',
        'type',
        'category',
        'amount',
        'description',
        'transaction_date',
        'reference_number',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::created(function (self $transaction): void {
            $transaction->syncAccountingJournal();
        });

        static::updated(function (self $transaction): void {
            $transaction->syncAccountingJournal();
        });

        static::deleted(function (self $transaction): void {
            $transaction->deleteAccountingJournal();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function coordinator(): BelongsTo
    {
        return $this->belongsTo(Coordinator::class);
    }

    public function investor(): BelongsTo
    {
        return $this->belongsTo(Investor::class);
    }

    public function syncAccountingJournal(): void
    {
        if (! Schema::hasTable('journals') || ! Schema::hasTable('journal_entries') || ! Schema::hasTable('accounts')) {
            return;
        }

        Account::ensureDefaultChart();

        $lines = $this->buildJournalLines();
        if (count($lines) === 0) {
            $this->deleteAccountingJournal();

            return;
        }

        DB::transaction(function () use ($lines): void {
            $journals = Journal::where('source_type', 'finance_transaction')
                ->where('source_id', $this->id)
                ->with('entries')
                ->get();

            foreach ($journals as $journal) {
                foreach ($journal->entries as $entry) {
                    $entry->delete();
                }
                $journal->delete();
            }

            $date = $this->transaction_date?->toDateString() ?? now()->toDateString();
            $description = $this->description ?: $this->category;
            $poster = app(AccountingPoster::class);
            $poster->post(
                'FIN-'.$this->id.'-'.now()->format('His'),
                $date,
                $description,
                $lines,
                null,
                'finance_transaction',
                $this->id
            );
        });
    }

    public function deleteAccountingJournal(): void
    {
        if (! Schema::hasTable('journals') || ! Schema::hasTable('journal_entries')) {
            return;
        }

        $journals = Journal::where('source_type', 'finance_transaction')
            ->where('source_id', $this->id)
            ->with('entries')
            ->get();

        foreach ($journals as $journal) {
            foreach ($journal->entries as $entry) {
                $entry->delete();
            }
            $journal->delete();
        }
    }

    private function buildJournalLines(): array
    {
        $amount = (float) $this->amount;
        if ($amount <= 0) {
            return [];
        }

        $codes = $this->resolveAccountCodes();
        $accounts = Account::whereIn('code', array_values($codes))->pluck('id', 'code');
        $debitCode = $codes['debit'] ?? null;
        $creditCode = $codes['credit'] ?? null;

        if (! $debitCode || ! $creditCode) {
            return [];
        }

        $debitId = $accounts->get($debitCode);
        $creditId = $accounts->get($creditCode);

        if (! $debitId || ! $creditId) {
            return [];
        }

        return [
            [
                'account_id' => $debitId,
                'debit' => $amount,
                'credit' => 0,
                'unit' => 'MSTORE',
                'memo' => $this->category,
                'cost_center' => $this->coordinator_id ? 'COORD-'.$this->coordinator_id : null,
            ],
            [
                'account_id' => $creditId,
                'debit' => 0,
                'credit' => $amount,
                'unit' => 'MSTORE',
                'memo' => $this->category,
                'cost_center' => $this->coordinator_id ? 'COORD-'.$this->coordinator_id : null,
            ],
        ];
    }

    private function resolveAccountCodes(): array
    {
        $type = strtolower((string) $this->type);
        $category = strtolower(trim((string) $this->category));
        $reference = strtolower((string) ($this->reference_number ?? ''));

        if ($type === 'transfer') {
            return ['debit' => '1002', 'credit' => '1001'];
        }

        if ($type === 'income') {
            if ($category === 'member income' || $category === 'voucher income') {
                return ['debit' => '1001', 'credit' => '4001'];
            }

            if ($this->investor_id) {
                return ['debit' => '1001', 'credit' => '3001'];
            }

            return ['debit' => '1001', 'credit' => '4006'];
        }

        if ($type === 'expense') {
            if (str_contains($reference, 'atk-exp-') || str_contains($reference, 'wash-exp-')) {
                return [];
            }

            if (str_contains($reference, 'inv-in-') || str_contains($reference, 'inv-add-') || $category === 'pembelian alat') {
                return ['debit' => '1201', 'credit' => '1001'];
            }

            if (str_contains($reference, 'inv-out-')) {
                return ['debit' => '5001', 'credit' => '1201'];
            }

            if ($category === 'salary') {
                return ['debit' => '6003', 'credit' => '1001'];
            }

            if ($category === 'coordinator commission') {
                return ['debit' => '6008', 'credit' => '1001'];
            }

            if ($category === 'investor profit share' || $category === 'investor cash fund') {
                return ['debit' => '6009', 'credit' => '1001'];
            }

            if ($category === 'isp payment' || $category === 'operational') {
                return ['debit' => '6001', 'credit' => '1001'];
            }

            if ($category === 'transport') {
                return ['debit' => '6006', 'credit' => '1001'];
            }

            if ($category === 'consumption') {
                return ['debit' => '6011', 'credit' => '1001'];
            }

            if ($category === 'repair') {
                return ['debit' => '6005', 'credit' => '1001'];
            }

            if ($category === 'tool fund') {
                return ['debit' => '6010', 'credit' => '1001'];
            }

            if ($category === 'pengeluaran pengurus') {
                return ['debit' => '6010', 'credit' => '1001'];
            }

            if ($this->investor_id) {
                return ['debit' => '3001', 'credit' => '1001'];
            }

            return ['debit' => '6012', 'credit' => '1001'];
        }

        return [];
    }
}
