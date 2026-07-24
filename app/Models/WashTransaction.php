<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\PostsToGeneralLedger;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WashTransaction extends Model
{
    use Auditable, PostsToGeneralLedger;

    public $businessUnitCode = 'WASH';
    public $businessUnitName = 'Wash & Detailing';
    public $businessUnitType = 'SERVICE';
    public $transactionType = 'wash';

    protected $fillable = [
        'user_id', 'transaction_number', 'customer_name', 'vehicle_plate',
        'total_amount', 'payment_method', 'cash_amount', 'change_amount', 'notes',
        'wash_customer_id', 'wash_member_id', 'vehicle_brand', 'discount_amount', 'member_discount_amount',
        'status',
        'kasbon_type', 'kasbon_user_id', 'kasbon_name', 'kasbon_settled',
        'queue_number',
        'profit_center_id',
        'cost_center_id',
        'wash_shift_session_id',
        'wash_cash_register_id',
        'posted_at',
        'reversed_at',
        'loyalty_counted_at'
    ];

    protected $appends = [
        'queue_priority_code',
        'queue_priority_label',
        'queue_priority_sort',
        'queue_tier_sequence',
        'queue_display',
        'queue_service_order_today',
        'issued_reward_voucher',
    ];

    public function kasbonUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'kasbon_user_id');
    }

    public function items()
    {
        return $this->hasMany(WashTransactionItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function washCustomer(): BelongsTo
    {
        return $this->belongsTo(WashCustomer::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(WashMember::class, 'wash_member_id');
    }

    public function profitCenter(): BelongsTo
    {
        return $this->belongsTo(ProfitCenter::class);
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function shiftSession(): BelongsTo
    {
        return $this->belongsTo(WashShiftSession::class);
    }

    public function cashRegister(): BelongsTo
    {
        return $this->belongsTo(WashCashRegister::class);
    }

    public function pointTransactions(): HasMany
    {
        return $this->hasMany(WashPointTransaction::class);
    }

    public function generalTransactions()
    {
        return $this->morphMany(GeneralTransaction::class, 'reference');
    }

    public function getIssuedRewardVoucherAttribute()
    {
        return WashRewardVoucher::where('vehicle_plate', $this->vehicle_plate)
            ->where('meta->issued_from_transaction_id', $this->id)
            ->first();
    }

    public function getQueuePriorityCodeAttribute(): string
    {
        return $this->resolveQueuePriorityMeta()['code'];
    }

    public function getQueuePriorityLabelAttribute(): string
    {
        return $this->resolveQueuePriorityMeta()['label'];
    }

    public function getQueuePrioritySortAttribute(): int
    {
        return $this->resolveQueuePriorityMeta()['sort'];
    }

    public function getQueueTierSequenceAttribute(): int
    {
        if (! $this->exists || ! $this->created_at || ! $this->id) {
            return (int) ($this->queue_number ?? 0);
        }

        $ids = $this->dailyQueueTierIds();
        $index = array_search($this->id, $ids, true);

        return $index === false ? (int) ($this->queue_number ?? 0) : $index + 1;
    }

    public function getQueueDisplayAttribute(): string
    {
        return $this->queue_priority_code.'-'.str_pad((string) $this->queue_tier_sequence, 3, '0', STR_PAD_LEFT);
    }

    public function getQueueServiceOrderTodayAttribute(): int
    {
        if (! $this->exists || ! $this->created_at || ! $this->id) {
            return (int) ($this->queue_number ?? 0);
        }

        $ids = $this->dailyServiceOrderIds();
        $index = array_search($this->id, $ids, true);

        return $index === false ? (int) ($this->queue_number ?? 0) : $index + 1;
    }

    private function resolveQueuePriorityMeta(): array
    {
        $this->loadMissing('member.level');

        $levelCode = strtolower((string) ($this->member?->level?->code ?? 'bronze'));

        return match ($levelCode) {
            'platinum' => ['code' => 'P', 'label' => 'Platinum Priority', 'sort' => 1, 'codes' => ['platinum']],
            'gold' => ['code' => 'G', 'label' => 'Gold Priority', 'sort' => 2, 'codes' => ['gold']],
            'silver' => ['code' => 'S', 'label' => 'Silver Queue', 'sort' => 3, 'codes' => ['silver']],
            default => ['code' => 'B', 'label' => 'Bronze Queue', 'sort' => 4, 'codes' => ['bronze']],
        };
    }

    private function dailyQueueTierIds(): array
    {
        $priorityCode = $this->queue_priority_code;

        return $this->buildDailyQueueTierIds($priorityCode);
    }

    private function dailyServiceOrderIds(): array
    {
        return $this->buildDailyServiceOrderIds();
    }

    private function buildDailyQueueTierIds(string $priorityCode): array
    {
        $query = self::query()
            ->with('member.level')
            ->whereBetween('created_at', [
                Carbon::parse($this->created_at)->startOfDay(),
                Carbon::parse($this->created_at)->endOfDay(),
            ])
            ->orderBy('created_at')
            ->orderBy('id');

        $this->applyQueuePriorityFilter($query, $priorityCode);

        return $query->get()->pluck('id')->all();
    }

    private function buildDailyServiceOrderIds(): array
    {
        $transactions = self::query()
            ->with('member.level')
            ->whereBetween('created_at', [
                Carbon::parse($this->created_at)->startOfDay(),
                Carbon::parse($this->created_at)->endOfDay(),
            ])
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        return $transactions
            ->sortBy([
                fn (self $transaction) => $transaction->queue_priority_sort,
                fn (self $transaction) => $transaction->created_at?->getTimestamp() ?? 0,
                fn (self $transaction) => (int) $transaction->id,
            ])
            ->pluck('id')
            ->values()
            ->all();
    }

    private function applyQueuePriorityFilter($query, string $priorityCode): void
    {
        match ($priorityCode) {
            'P' => $query->whereHas('member.level', fn ($levelQuery) => $levelQuery->where('code', 'platinum')),
            'G' => $query->whereHas('member.level', fn ($levelQuery) => $levelQuery->where('code', 'gold')),
            'S' => $query->whereHas('member.level', fn ($levelQuery) => $levelQuery->where('code', 'silver')),
            default => $query->where(function ($bronzeQuery) {
                $bronzeQuery->whereDoesntHave('member.level')
                    ->orWhereHas('member.level', fn ($levelQuery) => $levelQuery->whereNotIn('code', ['platinum', 'gold', 'silver']));
            }),
        };
    }

    public function syncAccountingJournal(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('journals') || ! \Illuminate\Support\Facades\Schema::hasTable('journal_entries') || ! \Illuminate\Support\Facades\Schema::hasTable('accounts')) {
            return;
        }

        $cashCode = $this->payment_method === 'voucher' ? '4005' : ($this->payment_method === 'cash' ? '1001' : '1002');
        $cashAccId = \App\Models\Account::where('code', $cashCode)->value('id');
        $revAccId = \App\Models\Account::where('code', '4005')->value('id');

        if ($cashAccId && $revAccId && $this->total_amount > 0 && $this->payment_method !== 'kasbon') {
            \Illuminate\Support\Facades\DB::transaction(function () use ($cashAccId, $revAccId) {
                // Delete old journals for this transaction
                $journals = \App\Models\Journal::where('source_type', 'wash_transaction')
                    ->where('source_id', $this->id)
                    ->with('entries')
                    ->get();

                foreach ($journals as $journal) {
                    foreach ($journal->entries as $entry) {
                        $entry->delete();
                    }
                    $journal->delete();
                }

                $poster = app(\App\Services\AccountingPoster::class);
                $poster->post(
                    'WASH-' . $this->transaction_number,
                    $this->created_at?->toDateString() ?? now()->toDateString(),
                    'Wash POS',
                    [
                        ['account_id' => $cashAccId, 'debit' => $this->total_amount, 'credit' => 0, 'unit' => 'MSTORE'],
                        ['account_id' => $revAccId, 'debit' => 0, 'credit' => $this->total_amount, 'unit' => 'MSTORE'],
                    ],
                    null,
                    'wash_transaction',
                    $this->id
                );
            });
        }
    }
}
