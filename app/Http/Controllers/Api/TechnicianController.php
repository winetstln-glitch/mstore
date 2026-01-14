<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TechnicianController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = $request->user();

        $activeTickets = $user->tickets()
            ->whereIn('status', ['assigned', 'in_progress'])
            ->with('customer')
            ->latest()
            ->get();

        $pendingInstallations = $user->installations()
            ->whereIn('status', ['survey', 'installation'])
            ->with('customer')
            ->latest()
            ->get();

        return response()->json([
            'active_tickets' => $activeTickets,
            'pending_installations' => $pendingInstallations,
            'stats' => [
                'tickets_today' => $user->tickets()->whereDate('created_at', today())->count(),
                'tickets_total' => $user->tickets()->count(),
                'installations_total' => $user->installations()->count(),
            ]
        ]);
    }

    public function history(Request $request)
    {
        $user = $request->user();

        $tickets = $user->tickets()
            ->whereIn('status', ['solved', 'closed'])
            ->with('customer')
            ->latest()
            ->paginate(10);

        return response()->json($tickets);
    }
}
