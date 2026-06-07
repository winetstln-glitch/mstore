<?php

namespace App\Events;

use App\Models\Ticket;
use App\Models\User;
use App\Models\TechnicianAssignment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketAssigned
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $ticket;
    public $technician;
    public $assignment;

    public function __construct(Ticket $ticket, User $technician, TechnicianAssignment $assignment)
    {
        $this->ticket = $ticket;
        $this->technician = $technician;
        $this->assignment = $assignment;
    }
}
