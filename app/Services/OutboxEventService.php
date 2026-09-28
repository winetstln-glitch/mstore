C:\Users\Lenovo\.ssh\app-nms-deploy<?php

namespace App\Services;

use App\Models\OutboxEvent;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
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

        $user = Auth::user();
        $attributes = [
            'aggregate_type' => $aggregateType,
            'aggregate_id' => $aggregateId,
            'event_type' => $eventType,
            'payload' => $payload,
            'status' => 'pending',
        ];

        if ($user && $user->company_id) {
            $attributes['company_id'] = $user->company_id;
            if (isset($user->company_branch_id) && $user->company_branch_id) {
                $attributes['company_branch_id'] = $user->company_branch_id;
            }
        }

        try {
            return OutboxEvent::withoutGlobalScopes()->firstOrCreate(
                ['event_hash' => $eventHash],
                $attributes
            );
        } catch (QueryException $e) {
            if (
                $e->errorInfo[1] === 1062
                && str_contains($e->getMessage(), 'outbox_events_event_hash_unique')
            ) {
                return OutboxEvent::withoutGlobalScopes()
                    ->where('event_hash', $eventHash)
                    ->firstOrFail();
            }
            throw $e;
        }
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