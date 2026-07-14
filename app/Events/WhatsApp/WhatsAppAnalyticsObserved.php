<?php

namespace App\Events\WhatsApp;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WhatsAppAnalyticsObserved
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly array $attributes,
    ) {}
}

