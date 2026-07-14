<?php

namespace App\Listeners\WhatsApp;

use App\Events\WhatsApp\WhatsAppAnalyticsObserved;
use App\Repositories\Contracts\WhatsAppAnalyticsEventRepositoryInterface;
use Illuminate\Contracts\Queue\ShouldQueue;

class RecordWhatsAppAnalyticsObserved implements ShouldQueue
{
    public function __construct(
        private readonly WhatsAppAnalyticsEventRepositoryInterface $repository,
    ) {}

    public function handle(WhatsAppAnalyticsObserved $event): void
    {
        $attributes = $event->attributes;
        if (! is_array($attributes)) {
            return;
        }

        $this->repository->create([
            'occurred_at' => $attributes['occurred_at'] ?? now(),
            'direction' => $attributes['direction'] ?? 'unknown',
            'phone_number' => $attributes['phone_number'] ?? 'unknown',
            'whatsapp_session_id' => $attributes['whatsapp_session_id'] ?? null,
            'intent' => $attributes['intent'] ?? null,
            'used_ai' => (bool) ($attributes['used_ai'] ?? false),
            'is_fallback' => (bool) ($attributes['is_fallback'] ?? false),
            'ticket_id' => $attributes['ticket_id'] ?? null,
            'payment_transaction_id' => $attributes['payment_transaction_id'] ?? null,
            'voucher_payment_id' => $attributes['voucher_payment_id'] ?? null,
            'meta' => $attributes['meta'] ?? null,
        ]);
    }
}

