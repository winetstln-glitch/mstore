<?php

namespace App\Jobs;

use App\Events\ExpenseApproved;
use App\Events\GeneralTransactionCreated;
use App\Events\InvoicePaidEvent;
use App\Events\AtkTransactionCreated;
use App\Events\WashTransactionCreated;
use App\Models\OutboxEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class OutboxEventProcessorJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [10, 60, 300];

    public function __construct(public int $outboxEventId) {}

    public function handle(): void
    {
        $outboxEvent = OutboxEvent::findOrFail($this->outboxEventId);

        if ($outboxEvent->status === 'processed') {
            return;
        }

        try {
            $this->processEvent($outboxEvent);

            $outboxEvent->update([
                'status' => 'processed',
                'processed_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Outbox event processing failed', [
                'event_id' => $outboxEvent->id,
                'error' => $e->getMessage(),
            ]);

            $outboxEvent->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    protected function processEvent(OutboxEvent $outboxEvent): void
    {
        $eventClass = $this->getEventClass($outboxEvent->event_type);
        if (!$eventClass) {
            return;
        }

        $eventInstance = $this->reconstructEvent($eventClass, $outboxEvent->payload);

        if (class_exists(\App\Listeners\AccountingEventListener::class)) {
            $listener = new \App\Listeners\AccountingEventListener(
                new \App\Services\AccountingPoster()
            );

            $method = 'handle' . class_basename($eventClass);
            if (method_exists($listener, $method)) {
                $listener->$method($eventInstance);
            }
        }
    }

    protected function getEventClass(string $eventType): ?string
    {
        $map = [
            'GeneralTransactionCreated' => GeneralTransactionCreated::class,
            'InvoicePaidEvent' => InvoicePaidEvent::class,
            'WashTransactionCreated' => WashTransactionCreated::class,
            'AtkTransactionCreated' => AtkTransactionCreated::class,
            'ExpenseApproved' => ExpenseApproved::class,
        ];

        return $map[$eventType] ?? null;
    }

    protected function reconstructEvent(string $eventClass, array $payload): object
    {
        $modelClass = $payload['model_class'] ?? null;
        $modelId = $payload['model_id'] ?? null;

        if ($modelClass && $modelId) {
            $model = $modelClass::find($modelId);
            if ($model) {
                return new $eventClass($model);
            }
        }

        throw new \Exception('Could not reconstruct event');
    }
}