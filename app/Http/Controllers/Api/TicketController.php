<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TicketController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Ticket::with(['customer', 'technician']);

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('technician_id')) {
            $query->where('technician_id', $request->input('technician_id'));
        }

        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->input('customer_id'));
        }

        return response()->json($query->latest()->paginate(15));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'subject' => 'required|string|max:255',
            'type' => 'required|string',
            'priority' => 'required|in:low,medium,high',
            'description' => 'nullable|string',
            'location' => 'nullable|string',
        ]);

        $ticket = Ticket::create([
            'ticket_number' => Ticket::generateNumber(),
            ...$validated,
            'status' => 'open',
        ]);

        TicketLog::create([
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'action' => 'created',
            'description' => 'Ticket created',
        ]);

        return response()->json($ticket, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Ticket $ticket)
    {
        return response()->json($ticket->load(['customer', 'technician', 'logs.user']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Ticket $ticket)
    {
        if (!$request->user() || !$request->user()->hasPermission('ticket.edit')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $validated = $request->validate([
            'technician_id' => 'nullable|exists:users,id',
            'subject' => 'sometimes|required|string|max:255',
            'priority' => 'sometimes|required|in:low,medium,high',
            'status' => 'sometimes|required|in:open,assigned,in_progress,pending,solved,closed',
            'description' => 'nullable|string',
            'location' => 'nullable|string',
            'sla_deadline' => 'nullable|date',
        ]);

        $oldStatus = $ticket->status;
        $oldTechnician = $ticket->technician_id;

        $ticket->update($validated);

        if ($ticket->wasChanged('status')) {
            TicketLog::create([
                'ticket_id' => $ticket->id,
                'user_id' => $request->user()->id,
                'action' => 'status_updated',
                'description' => "Status changed from {$oldStatus} to {$ticket->status}",
            ]);

            if ($ticket->status === 'closed') {
                $ticket->update(['closed_at' => now()]);
            }
        }

        if ($ticket->wasChanged('technician_id')) {
             TicketLog::create([
                'ticket_id' => $ticket->id,
                'user_id' => $request->user()->id,
                'action' => 'assigned',
                'description' => "Technician assigned/changed",
            ]);
            
            if ($ticket->status === 'open' && $ticket->technician_id) {
                $ticket->update(['status' => 'assigned']);
            }
        }

        return response()->json($ticket);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Ticket $ticket)
    {
        $ticket->delete();

        return response()->json(['message' => 'Ticket deleted successfully']);
    }
}
