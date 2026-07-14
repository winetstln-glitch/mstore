<?php

namespace App\Models;

use App\Services\AccountingPoster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OwnerFund extends Model
{
    protected $table = 'owner_funds';

    protected $fillable = [
        'transaction_code',
        'transaction_date',
        'type',
        'amount',
        'balance',
        'description',
        'created_by',
        'approved_by',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance' => 'decimal:2',
        'transaction_date' => 'date',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
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
            // Delete old journal for this fund
            $journals = Journal::where('source_type', 'owner_fund')
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
            $ownerPayableAccId = Account::where('code', '2101')->value('id');
            if (! $cashAccId || ! $ownerPayableAccId) {
                return;
            }

            $lines = [];

            if ($this->type === 'loan') {
                // Dana Talangan Masuk: Debit Kas, Kredit Utang Pemilik
                $lines[] = ['account_id' => $cashAccId, 'debit' => $this->amount, 'credit' => 0, 'unit' => 'ATK'];
                $lines[] = ['account_id' => $ownerPayableAccId, 'debit' => 0, 'credit' => $this->amount, 'unit' => 'ATK'];
            } elseif ($this->type === 'repayment') {
                // Pengembalian Dana: Debit Utang Pemilik, Kredit Kas
                $lines[] = ['account_id' => $ownerPayableAccId, 'debit' => $this->amount, 'credit' => 0, 'unit' => 'ATK'];
                $lines[] = ['account_id' => $cashAccId, 'debit' => 0, 'credit' => $this->amount, 'unit' => 'ATK'];
            }

            if (count($lines) > 0) {
                $date = $this->transaction_date?->toDateString() ?? now()->toDateString();
                $poster = app(AccountingPoster::class);
                $poster->post(
                    $this->transaction_code ?: 'ATK-OF-' . $this->id,
                    $date,
                    $this->description ?: 'Dana Talangan Pemilik',
                    $lines,
                    null,
                    'owner_fund',
                    $this->id
                );
            }
        });
    }
}
