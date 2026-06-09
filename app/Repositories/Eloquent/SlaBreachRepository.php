<?php

namespace App\Repositories\Eloquent;

use App\Models\SlaBreach;
use App\Models\SlaRule;
use App\Models\Ticket;
use App\Repositories\Contracts\SlaBreachRepositoryInterface;

class SlaBreachRepository implements SlaBreachRepositoryInterface
{
    public function firstOrCreateBreach(Ticket $ticket, SlaRule $rule, string $currentStatus): SlaBreach
    {
        return SlaBreach::query()->firstOrCreate(
            [
                'ticket_id' => $ticket->id,
                'sla_rule_id' => $rule->id,
            ],
            [
                'breached_at' => now(),
                'current_status' => $currentStatus,
            ]
        );
    }

    public function resolveBreachesForTicket(Ticket $ticket): int
    {
        return SlaBreach::query()
            ->where('ticket_id', $ticket->id)
            ->whereNull('resolved_at')
            ->update([
                'resolved_at' => now(),
                'updated_at' => now(),
            ]);
    }
}

