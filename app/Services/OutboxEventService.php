<?php

namespace App\Services;

use App\Models\OutboxEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OutboxEventService
{
    public function createOutboxEvent(
        string $aggregateType,
        string $aggregateId,
        string $eventType,
        array $payload
    ): OutboxEvent {
        $eventHash = $this->generateEventHash($aggregateType, $aggregateId, $eventType, $payload);

        return OutboxEvent::firstOrCreate(
            ['event_hash' => $eventHash],
            [
                'aggregate_type' => $aggregateType,
                'aggregate_id' => $aggregateId,
                'event_type' => $eventType,
                'payload' => $payload,
                'status' => 'pending',
            ]
        );
    }

    protected function generateEventHash(
        string $aggregateType,
        string $aggregateId,
        string $eventType,
        array $payload
    ): string {
        $data = [
            'aggregate_type' => $aggregateType,
            'aggregate_id' => $aggregateId,
            'event_type' => $eventType,
            'payload' => $payload,
        ];

        return hash('sha256', json_encode($data));
    }
}