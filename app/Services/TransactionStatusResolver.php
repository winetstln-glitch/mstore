<?php

namespace App\Services;

use App\Models\Transaction;
use Illuminate\Support\Facades\Schema;

class TransactionStatusResolver
{
    public static function hasStatusColumn(): bool
    {
        return Schema::hasColumn('transactions', 'status');
    }

    public static function isPaid(Transaction $transaction): bool
    {
        if (!self::hasStatusColumn()) {
            // Fallback logic if no status column: consider all transactions paid
            return true;
        }
        return $transaction->status === 'paid';
    }

    public static function isPending(Transaction $transaction): bool
    {
        if (!self::hasStatusColumn()) {
            return false;
        }
        return $transaction->status === 'pending';
    }

    public static function isFailed(Transaction $transaction): bool
    {
        if (!self::hasStatusColumn()) {
            return false;
        }
        return $transaction->status === 'failed';
    }

    public static function markAsPaid(Transaction $transaction): void
    {
        if (!self::hasStatusColumn()) {
            return;
        }
        $transaction->update(['status' => 'paid']);
    }

    public static function markAsFailed(Transaction $transaction): void
    {
        if (!self::hasStatusColumn()) {
            return;
        }
        $transaction->update(['status' => 'failed']);
    }
}
