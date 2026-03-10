<?php

namespace App\Models;

use App\Services\AccountingPoster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WashTransaction extends Model
{
    protected $fillable = [
        'user_id', 'transaction_number', 'customer_name', 'vehicle_plate',
        'total_amount', 'payment_method', 'cash_amount', 'change_amount', 'notes',
        'wash_customer_id', 'vehicle_brand', 'discount_amount',
    ];

    public function items()
    {
        return $this->hasMany(WashTransactionItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function syncAccountingJournal(): void
    {
        if (! Schema::hasTable('journals') || ! Schema::hasTable('journal_entries') || ! Schema::hasTable('accounts')) {
            return;
        }

        Account::ensureDefaultChart();
        $total = (float) ($this->total_amount ?? 0);
        $cashCode = $this->payment_method === 'cash' ? '1001' : '1002';
        $cashAccId = Account::where('code', $cashCode)->value('id');
        $revAccId = Account::where('code', '4005')->value('id');

        DB::transaction(function () use ($total, $cashAccId, $revAccId): void {
            $journals = Journal::where('source_type', 'wash_transaction')
                ->where('source_id', $this->id)
                ->with('entries')
                ->get();

            foreach ($journals as $journal) {
                foreach ($journal->entries as $entry) {
                    $entry->delete();
                }
                $journal->delete();
            }

            if ($total <= 0 || ! $cashAccId || ! $revAccId) {
                return;
            }

            $date = $this->created_at?->toDateString() ?? now()->toDateString();
            $poster = app(AccountingPoster::class);
            $poster->post(
                'WASH-'.($this->transaction_number ?: $this->id),
                $date,
                'Wash POS',
                [
                    ['account_id' => $cashAccId, 'debit' => $total, 'credit' => 0, 'unit' => 'MSTORE'],
                    ['account_id' => $revAccId, 'debit' => 0, 'credit' => $total, 'unit' => 'MSTORE'],
                ],
                null,
                'wash_transaction',
                $this->id
            );
        });
    }
}
