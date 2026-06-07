<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\User;
use App\Models\TechnicianAssignment;
use Illuminate\Support\Facades\Log;

class TechnicianAssignmentService
{
    /**
     * Assign technician automatically
     */
    public function autoAssign(Ticket $ticket): ?TechnicianAssignment
    {
        $technicians = $this->getAvailableTechnicians();

        if ($technicians->isEmpty()) {
            Log::warning("AutoAssign: No available technicians for ticket {$ticket->id}");
            return null;
        }

        $scored = $this->scoreTechnicians($technicians, $ticket);

        $bestTechnician = $scored->first();

        return $this->assignTechnician($ticket, $bestTechnician['technician'], $bestTechnician);
    }

    /**
     * Get available technicians
     */
    protected function getAvailableTechnicians()
    {
        return User::whereHas('roles', function($q) {
            $q->whereIn('name', ['teknisi', 'teknisi lapangan', 'admin']);
        })
        ->where('status', 'active')
        ->where('is_present', true)
        ->where('on_leave', false)
        ->get();
    }

    /**
     * Score technicians
     */
    protected function scoreTechnicians($technicians, Ticket $ticket): array
    {
        $scored = [];

        foreach ($technicians as $tech) {
            $score = 0;
            $details = [];

            // Score 1: Ticket load (lower is better, max 40 points)
            $activeTickets = $tech->assignedTickets()
                ->whereIn('status', ['open', 'in_progress', 'pending'])
                ->count();
            $loadScore = max(0, 40 - ($activeTickets * 5));
            $score += $loadScore;
            $details['ticket_load'] = $activeTickets;
            $details['load_score'] = $loadScore;

            // Score 2: Distance (if customer has location, max 30 points)
            $distanceScore = 30; // Default if no distance data
            $details['distance'] = 'unknown';
            $details['distance_score'] = $distanceScore;
            $score += $distanceScore;

            // Score 3: Availability and skill match (max 30 points)
            $availabilityScore = 30;
            $details['availability_score'] = $availabilityScore;
            $score += $availabilityScore;

            $scored[] = [
                'technician' => $tech,
                'score' => $score,
                'details' => $details,
            ];
        }

        usort($scored, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        return $scored;
    }

    /**
     * Assign technician to ticket
     */
    public function assignTechnician(Ticket $ticket, User $technician, array $scoring = null): TechnicianAssignment
    {
        $assignment = TechnicianAssignment::create([
            'ticket_id' => $ticket->id,
            'technician_id' => $technician->id,
            'assignment_key' => 'ASSIGN-' . date('YmdHis') . '-' . strtoupper(str()->random(6)),
            'status' => 'assigned',
            'score' => $scoring['score'] ?? null,
            'scoring_details' => $scoring['details'] ?? null,
            'assigned_at' => now(),
        ]);

        $ticket->update([
            'technician_id' => $technician->id,
            'status' => 'assigned',
        ]);

        Log::info("AutoAssign: Assigned ticket {$ticket->id} to technician {$technician->id}");

        // TODO: Trigger notification to technician (WhatsApp, email, etc.)
        event(new \App\Events\TicketAssigned($ticket, $technician, $assignment));

        return $assignment;
    }
}
