<?php

namespace App\Observers;

use App\Models\WashTransaction;
use App\Services\Wash\WashCommissionService;
use Illuminate\Support\Facades\Log;

class WashTransactionObserver
{
    public function created(WashTransaction $transaction): void
    {
        try {
            $status = strtolower((string) ($transaction->status ?? ''));
            if (in_array($status, ['posted', 'paid', 'lunas', 'selesai', 'done'], true)) {
                app(WashCommissionService::class)->calculateAndStoreForTransaction($transaction);
            }
        } catch (\Throwable $e) {
            Log::error('WashTransactionObserver::created commission failed', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function updated(WashTransaction $transaction): void
    {
        try {
            $service = app(WashCommissionService::class);
            $status = strtolower((string) ($transaction->status ?? ''));
            $wasPosted = $transaction->getOriginal('status');
            $wasPostedLower = $wasPosted ? strtolower((string) $wasPosted) : '';

            $isValidForCommission = in_array($status, ['posted', 'paid', 'lunas', 'selesai', 'done'], true);
            $wasValidForCommission = in_array($wasPostedLower, ['posted', 'paid', 'lunas', 'selesai', 'done'], true);

            if ($wasValidForCommission && !$isValidForCommission) {
                $service->voidForTransaction($transaction, 'status_changed_from_posted_to_' . $status);
                return;
            }

            if ($isValidForCommission) {
                $service->recalcForTransaction($transaction);
            }
        } catch (\Throwable $e) {
            Log::error('WashTransactionObserver::updated commission failed', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function deleted(WashTransaction $transaction): void
    {
        try {
            app(WashCommissionService::class)->voidForTransaction($transaction, 'transaction_deleted');
        } catch (\Throwable $e) {
            Log::error('WashTransactionObserver::deleted commission failed', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
