<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketLog;
use Illuminate\Http\Request;

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
                try {
                    $this->sendTelegramTicketNotification($ticket, 'solved', "Status changed to " . ucfirst($ticket->status));
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Failed to send Telegram solved notification from API: ' . $e->getMessage());
                }
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
     * Send Telegram notification for ticket events.
     * Replicated from TicketWebController for now to avoid large refactoring.
     */
    protected function sendTelegramTicketNotification(Ticket $ticket, string $type = 'created', ?string $customDescription = null): void
    {
        try {
            $telegramService = new \App\Services\TelegramService;
            $customerName = $ticket->customer ? $ticket->customer->name : 'N/A';
            $locationLink = $ticket->location ? 'https://maps.google.com/?q='.urlencode($ticket->location) : '#';

            $settingKey = $type === 'solved' ? 'telegram_ticket_solved_template' : 'telegram_ticket_template';
            $templateSetting = \App\Models\Setting::where('key', $settingKey)->first();
            $template = $templateSetting ? $templateSetting->value : null;

            $technicianNames = $ticket->technicians->pluck('name')->join(', ');
            if (empty($technicianNames)) {
                $technicianNames = '-';
            }

            $coordinatorName = $ticket->coordinator ? $ticket->coordinator->name : '-';

            if (empty($template)) {
                if ($type === 'solved') {
                    $template = "✅ *TIKET SELESAI (TICKET SOLVED)*\n\n".
                               "🆔 *No:* `{ticket_number}`\n".
                               "📝 *Subject:* `{subject}`\n".
                               "👤 *Customer:* `{customer_name}`\n".
                               "👷 *Teknisi:* `{technicians}`\n".
                               "👔 *Koordinator:* `{coordinator}`\n".
                               "📍 *Lokasi:* `{location}`\n".
                               "⚠️ *Prioritas:* `{priority}`\n".
                               "📄 *Keterangan Selesai:* `{description}`\n\n".
                               "Tiket telah diselesaikan oleh teknisi.\n".
                               '[Lihat Lokasi]({location_link})';
                } else {
                    $template = "🔔 *TIKET BARU (NEW TICKET)*\n\n".
                               "🆔 *No:* `{ticket_number}`\n".
                               "📝 *Subject:* `{subject}`\n".
                               "👤 *Customer:* `{customer_name}`\n".
                               "👷 *Teknisi:* `{technicians}`\n".
                               "👔 *Koordinator:* `{coordinator}`\n".
                               "📍 *Lokasi:* `{location}`\n".
                               "⚠️ *Prioritas:* `{priority}`\n".
                               "📄 *Deskripsi:* `{description}`\n\n".
                               "Silakan cek aplikasi untuk detail lebih lanjut.\n".
                               '[Lihat Lokasi]({location_link})';
                }
            }

            $description = $customDescription ?? $ticket->description;

            $replacements = [
                '{ticket_number}' => "`".\App\Services\TelegramService::escape($ticket->ticket_number)."`",
                '{subject}' => \App\Services\TelegramService::escape($ticket->subject),
                '{customer_name}' => \App\Services\TelegramService::escape($customerName),
                '{technicians}' => \App\Services\TelegramService::escape($technicianNames),
                '{coordinator}' => \App\Services\TelegramService::escape($coordinatorName),
                '{location}' => \App\Services\TelegramService::escape($ticket->location ?? '-'),
                '{priority}' => \App\Services\TelegramService::escape(ucfirst($ticket->priority)),
                '{description}' => \App\Services\TelegramService::escape($description ?? '-'),
                '{location_link}' => $locationLink,
            ];

            $message = str_replace(array_keys($replacements), array_values($replacements), $template);
            $telegramService->sendToTechnicianGroup($message);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to send Telegram {$type} notification: ".$e->getMessage());
        }
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
