<?php

namespace App\Http\Controllers;

use App\Models\Coordinator;
use App\Models\Customer;
use App\Models\Installation;
use App\Models\TechnicianAttendance;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Get User's Attendance for Today
        $todayAttendance = TechnicianAttendance::where('user_id', $user->id)
            ->whereDate('clock_in', today())
            ->first();

        if ($user->hasRole('technician')) {
            $stats = [
                'assigned_tickets' => $user->tickets()->whereIn('status', ['assigned', 'in_progress'])->count(),
                'assigned_installations' => $user->installations()->whereIn('status', ['assigned', 'survey'])->count(),
                'completed_tickets_today' => $user->tickets()->where('status', 'solved')->whereDate('tickets.updated_at', today())->count(),
            ];
            
            $activeTickets = $user->tickets()
                ->with('customer')
                ->whereIn('status', ['assigned', 'in_progress'])
                ->orderByRaw("CASE priority WHEN 'high' THEN 1 WHEN 'medium' THEN 2 WHEN 'low' THEN 3 END")
                ->latest()
                ->take(10)
                ->get();

            $pendingInstallations = $user->installations()
                ->with('customer')
                ->whereIn('status', ['registered', 'survey', 'approved', 'installation'])
                ->orderBy('plan_date', 'asc')
                ->take(10)
                ->get();

            return view('technician.dashboard', compact('stats', 'activeTickets', 'pendingInstallations', 'todayAttendance'));
        }

        $stats = [
            'total_customers' => Customer::count(),
            'new_customers_this_month' => Customer::where('created_at', '>=', now()->startOfMonth())->count(),
            'open_tickets' => Ticket::where('status', 'open')->count(),
            'tickets_today' => Ticket::whereDate('created_at', today())->count(),
            'pending_installations' => Installation::whereIn('status', ['registered', 'survey', 'approved'])->count(),
        ];

        // Filter Dashboard Stats for Coordinator
        if (!$user->hasRole('admin')) {
            $coordinator = Coordinator::where('user_id', $user->id)->first();
            if ($coordinator && $coordinator->region_id) {
                // Filter Customers
                $customerQuery = Customer::whereHas('odp', function($q) use ($coordinator) {
                    $q->where('region_id', $coordinator->region_id);
                });
                
                $stats['total_customers'] = $customerQuery->count();
                $stats['new_customers_this_month'] = $customerQuery->where('created_at', '>=', now()->startOfMonth())->count();
                
                // Filter Tickets (Linked to Customer in Region)
                $ticketQuery = Ticket::whereHas('customer.odp', function($q) use ($coordinator) {
                    $q->where('region_id', $coordinator->region_id);
                });

                $stats['open_tickets'] = $ticketQuery->clone()->where('status', 'open')->count();
                $stats['tickets_today'] = $ticketQuery->clone()->whereDate('created_at', today())->count();
                
                // Filter Installations (Linked to Customer in Region)
                // Assuming installation has customer_id or similar
                $installationQuery = Installation::whereHas('customer.odp', function($q) use ($coordinator) {
                    $q->where('region_id', $coordinator->region_id);
                });
                
                $stats['pending_installations'] = $installationQuery->whereIn('status', ['registered', 'survey', 'approved'])->count();
            }
        }

        $recentTickets = Ticket::with(['customer', 'technicians'])
            ->where('status', '!=', 'closed')
            ->latest()
            ->take(5)
            ->get();

        $upcomingInstallations = Installation::with(['customer', 'technician'])
            ->whereIn('status', ['registered', 'survey', 'approved', 'installation'])
            ->orderBy('plan_date', 'asc')
            ->take(5)
            ->get();

        // Monthly Ticket Recap (Current Year)
        $monthlyTickets = Ticket::whereYear('created_at', now()->year)
            ->get()
            ->groupBy(function($ticket) {
                return $ticket->created_at->format('m');
            });

        $ticketRecap = [];
        for ($i = 1; $i <= 12; $i++) {
            $monthNum = str_pad($i, 2, '0', STR_PAD_LEFT);
            $monthName = \Carbon\Carbon::create(null, $i, 1)->format('F');
            $ticketsInMonth = $monthlyTickets->get($monthNum, collect());
            
            $ticketRecap[] = [
                'month' => $monthName,
                'total' => $ticketsInMonth->count(),
                'resolved' => $ticketsInMonth->whereIn('status', ['solved', 'closed'])->count(),
                'open' => $ticketsInMonth->whereIn('status', ['open', 'assigned', 'in_progress', 'pending'])->count(),
            ];
        }

        return view('dashboard', compact('stats', 'recentTickets', 'upcomingInstallations', 'ticketRecap', 'todayAttendance'));
    }
}
