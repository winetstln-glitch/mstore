<?php

namespace App\Repositories\Contracts;

use App\Models\SlaBreach;
use App\Models\SlaRule;
use App\Models\Ticket;

interface SlaBreachRepositoryInterface
{
    public function firstOrCreateBreach(Ticket $ticket, SlaRule $rule, string $currentStatus): SlaBreach;

    public function resolveBreachesForTicket(Ticket $ticket): int;
}

