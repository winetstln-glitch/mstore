<?php

namespace App\Traits;

use App\Events\GeneralTransactionCreated;
use App\Models\BusinessUnit;
use App\Models\GeneralTransaction;

trait PostsToGeneralLedger
{
    public static function bootPostsToGeneralLedger(): void
    {
        static::created(function ($model) {
            // Skip for WashTransaction and AtkTransaction - they have their own syncAccountingJournal
            if (method_exists($model, 'syncAccountingJournal')) {
                return;
            }

            $businessUnitCode = $model->getBusinessUnitCode();
            $transactionType = $model->getTransactionType();
            $amount = $model->getTransactionAmount();
            $description = $model->getTransactionDescription();

            $businessUnit = BusinessUnit::firstOrCreate(
                ['code' => $businessUnitCode],
                ['name' => $model->getBusinessUnitName(), 'type' => $model->getBusinessUnitType()]
            );

            // Check if GeneralTransaction already exists for this reference
            $generalTransaction = GeneralTransaction::firstOrCreate(
                [
                    'reference_type' => get_class($model),
                    'reference_id' => $model->id,
                ],
                [
                    'business_unit_id' => $businessUnit->id,
                    'transaction_type' => $transactionType,
                    'amount' => $amount,
                    'status' => 'posted',
                    'description' => $description,
                    'created_by' => auth()->id(),
                ]
            );

            event(new GeneralTransactionCreated($generalTransaction));
        });
    }

    public function getBusinessUnitCode(): string
    {
        return property_exists($this, 'businessUnitCode') ? $this->businessUnitCode : 'GENERAL';
    }

    public function getBusinessUnitName(): string
    {
        return property_exists($this, 'businessUnitName') ? $this->businessUnitName : 'General Business';
    }

    public function getBusinessUnitType(): string
    {
        return property_exists($this, 'businessUnitType') ? $this->businessUnitType : 'SERVICE';
    }

    public function getTransactionType(): string
    {
        return property_exists($this, 'transactionType') ? $this->transactionType : 'transaction';
    }

    public function getTransactionAmount(): float
    {
        return (float)($this->amount ?? $this->total_amount ?? 0);
    }

    public function getTransactionDescription(): string
    {
        return 'Transaksi ' . class_basename($this);
    }
}
