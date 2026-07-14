<?php

namespace App\Services;

use App\Models\GeneralTransaction;
use App\Models\OutboxEvent;
use App\Jobs\OutboxEventProcessorJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GeneralTransactionService
{
    public function __construct(
        protected OutboxEventService $outboxEventService
    ) {}

    public function createTransaction(array $data): GeneralTransaction
    {
        return DB::transaction(function () use ($data) {
            // Auto-set company_id from authenticated user if not provided
            if (empty($data['company_id']) && auth()->check() && auth()->user()->company_id) {
                $data['company_id'] = auth()->user()->company_id;
            }
            
            $transaction = GeneralTransaction::create($data);

            $outboxEvent = $this->outboxEventService->createOutboxEvent(
                aggregateType: GeneralTransaction::class,
                aggregateId: $transaction->id,
                eventType: 'GeneralTransactionCreated',
                payload: [
                    'model_class' => GeneralTransaction::class,
                    'model_id' => $transaction->id,
                    'transaction_data' => $data,
                ]
            );

            OutboxEventProcessorJob::dispatch($outboxEvent->id);

            return $transaction;
        });
    }
}