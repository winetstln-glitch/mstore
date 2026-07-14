<?php

namespace App\Events;

use App\Models\AtkTransaction;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AtkTransactionCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly AtkTransaction $transaction
    ) {}
}
