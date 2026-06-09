<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class EscalationQueueController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:sla.escalation.view', only: ['index']),
        ];
    }

    public function index()
    {
        $noUpdateSince = now()->subHours(12);

        $tickets = Ticket::query()
            ->with('customer:id,name')
            ->whereNotIn('status', ['closed', 'solved'])
            ->where(function ($q) use ($noUpdateSince) {
                $q->whereNotNull('sla_status')
                    ->orWhereNull('technician_id')
                    ->orWhere('updated_at', '<', $noUpdateSince);
            })
            ->orderByRaw("CASE WHEN sla_status IS NOT NULL THEN 1 ELSE 2 END")
            ->latest('created_at')
            ->paginate(30);

        return view('sla.escalation_queue', [
            'tickets' => $tickets,
            'noUpdateSince' => $noUpdateSince,
        ]);
    }
}

