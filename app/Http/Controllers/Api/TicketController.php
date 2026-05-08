<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketLog;
use App\Traits\SendsNotifications;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TicketController extends Controller
{
    use SendsNotifications;

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

        // Notify Technician Group via Telegram
        app(\App\Services\TelegramService::class)->sendTicketNotification($ticket, 'created');

        // Notify Group via WhatsApp & Telegram
        $customerName = $ticket->customer?->name ?? '-';
        $priorityLabel = match($ticket->priority) {
            'high' => '🔴 TINGGI',
            'medium' => '🟡 SEDANG',
            'low' => '🟢 RENDAH',
            default => strtoupper($ticket->priority)
        };
        
        $waMessage = "🎫 *TIKET BARU (API): {$ticket->ticket_number}*\n\n" .
                     "👤 *Pelanggan:* {$customerName}\n" .
                     "📝 *Subjek:* {$ticket->subject}\n" .
                     "⚡ *Prioritas:* {$priorityLabel}\n" .
                     "🔗 *Detail:* " . route('tickets.show', $ticket) . "\n\n" .
                     "🚀 _Sistem M-Store_";
         
        $this->sendGroupNotification($waMessage, 'ticket', ['whatsapp']);

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
        if (! $request->user() || ! $request->user()->hasPermission('ticket.edit')) {
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

            // Notify Technician Group via Telegram if solved or closed
            if (in_array($ticket->status, ['solved', 'closed'])) {
                app(\App\Services\TelegramService::class)->sendTicketNotification($ticket, 'solved', "Status changed to " . ucfirst($ticket->status));
            }

            // Notify Group via WhatsApp & Telegram
            $statusLabel = match($ticket->status) {
                'open' => 'BUKA 🔓',
                'assigned' => 'DITUGASKAN 👤',
                'in_progress' => 'PROSES 🛠️',
                'pending' => 'PENDING ⏳',
                'solved' => 'SELESAI ✅',
                'closed' => 'DITUTUP 🔒',
                default => strtoupper($ticket->status)
            };
            
            $waMessage = "🎫 *UPDATE STATUS TIKET (API): {$ticket->ticket_number}*\n\n" .
                         "📝 *Subjek:* {$ticket->subject}\n" .
                         "📊 *Status:* {$statusLabel}\n" .
                         "👤 *Oleh:* " . ($request->user()->name ?? 'System') . "\n" .
                         "🔗 *Detail:* " . route('tickets.show', $ticket) . "\n\n" .
                         "🚀 _Sistem M-Store_";
             
            // If we already sent a specialized Telegram notification (for solved/closed), only send to WhatsApp here
            if (in_array($ticket->status, ['solved', 'closed'])) {
                $this->sendGroupNotification($waMessage, 'ticket', ['whatsapp']);
            } else {
                $this->sendGroupNotification($waMessage, 'ticket');
            }
        }

        if ($ticket->wasChanged('technician_id')) {
            TicketLog::create([
                'ticket_id' => $ticket->id,
                'user_id' => $request->user()->id,
                'action' => 'assigned',
                'description' => 'Technician assigned/changed',
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
