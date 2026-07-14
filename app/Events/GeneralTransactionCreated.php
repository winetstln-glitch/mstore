<?php

namespace App\Events;

use App\Models\GeneralTransaction;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GeneralTransactionCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly GeneralTransaction $transaction
    ) {}
}
