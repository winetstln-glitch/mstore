<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Coordinator;
use App\Models\Customer;
use App\Models\Installation;
use App\Models\InventoryItem;
use App\Models\TechnicianAttendance;
use App\Models\Ticket;
use App\Models\Transaction;
use App\Models\Router;
use App\Models\GenieDeviceStatus;
use App\Services\MikrotikService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class DashboardController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [];
    }

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

        // Base Queries for Dashboard Logic (Coordinator / Admin / Finance)
        $customerQuery = Customer::query();
        $ticketQuery = Ticket::query();
        $installationQuery = Installation::query();

        // Filter Logic: Exclude Admin and Finance Staff from filtering
        if (!$user->hasRole('admin') && !$user->hasRole('finance')) {
            $coordinator = Coordinator::where('user_id', $user->id)->first();
            if ($coordinator && $coordinator->region_id) {
                // Filter Customers by Region
                $customerQuery->whereHas('odp', function($q) use ($coordinator) {
                    $q->where('region_id', $coordinator->region_id);
                });
                
                // Filter Tickets (Linked to Customer in Region)
                $ticketQuery->whereHas('customer.odp', function($q) use ($coordinator) {
                    $q->where('region_id', $coordinator->region_id);
                });

                // Filter Installations (Linked to Customer in Region)
                $installationQuery->whereHas('customer.odp', function($q) use ($coordinator) {
                    $q->where('region_id', $coordinator->region_id);
                });
            }
        }

        $stats = [
            'total_customers' => $customerQuery->count(),
            'new_customers_this_month' => $customerQuery->clone()->where('created_at', '>=', now()->startOfMonth())->count(),
            'open_tickets' => $ticketQuery->clone()->whereIn('status', ['open', 'assigned', 'in_progress', 'pending'])->count(),
            'tickets_today' => $ticketQuery->clone()->whereDate('created_at', today())->count(),
            'pending_installations' => $installationQuery->clone()->whereIn('status', ['registered', 'survey', 'approved', 'installation'])->count(),
            'hotspot_active' => 0,
            'pppoe_active' => 0,
            'router_status' => 'offline',
            // GenieACS Stats
            'genie_total' => GenieDeviceStatus::count(),
            'genie_online' => GenieDeviceStatus::where('is_online', true)->count(),
            'genie_offline' => GenieDeviceStatus::where('is_online', false)->count(),
        ];

        // Fetch Live Stats from Router (ID 2 or First Active)
        try {
            $router = Router::find(2) ?? Router::where('is_active', true)->first();
            if ($router) {
                $mikrotik = new MikrotikService($router);
                if ($mikrotik->isConnected()) {
                    $stats['hotspot_active'] = $mikrotik->getHotspotActiveCount();
                    $stats['pppoe_active'] = $mikrotik->getPppoeActiveCount();
                    $stats['router_status'] = 'online';
                }
            }
        } catch (\Exception $e) {
            // Keep defaults if connection fails
        }

        $recentTickets = $ticketQuery->clone()
            ->with(['customer', 'technicians'])
            ->where('status', '!=', 'closed')
            ->latest()
            ->take(5)
            ->get();

        $upcomingInstallations = $installationQuery->clone()
            ->with(['customer', 'technician'])
            ->whereIn('status', ['registered', 'survey', 'approved', 'installation'])
            ->orderBy('plan_date', 'asc')
            ->take(5)
            ->get();

        // Monthly Ticket Recap (Current Year)
        $monthlyTickets = $ticketQuery->clone()
            ->whereYear('created_at', now()->year)
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

        // Inventory & Assets Data
        $inventoryItems = InventoryItem::orderBy('stock', 'asc')->take(5)->get();
        $totalInventoryValue = InventoryItem::sum(DB::raw('stock * price'));
        
        $deployedAssets = Asset::with(['item', 'holder'])
            ->where('status', 'deployed')
            ->whereIn('holder_type', ['App\Models\User', 'App\Models\Coordinator'])
            ->latest()
            ->take(10)
            ->get();

        // Financial Chart Data
        $financialData = [
            'labels' => [],
            'income' => [],
            'expense' => []
        ];
        
        // Fetch Income Data (Collection-based grouping for DB compatibility)
        $incomeData = Transaction::where('type', 'income')
            ->whereYear('transaction_date', now()->year)
            ->get()
            ->groupBy(function($transaction) {
                return $transaction->transaction_date->format('n'); // Group by month number (1-12)
            })
            ->map(function ($transactions) {
                return $transactions->sum('amount');
            });
            
        // Fetch Expense Data
        $expenseData = Transaction::where('type', 'expense')
            ->whereYear('transaction_date', now()->year)
            ->get()
            ->groupBy(function($transaction) {
                return $transaction->transaction_date->format('n'); // Group by month number (1-12)
            })
            ->map(function ($transactions) {
                return $transactions->sum('amount');
            });

        for ($i = 1; $i <= 12; $i++) {
            $financialData['labels'][] = \Carbon\Carbon::create(null, $i, 1)->format('F');
            $financialData['income'][] = $incomeData->get($i, 0);
            $financialData['expense'][] = $expenseData->get($i, 0);
        }

        return view('dashboard', compact('stats', 'recentTickets', 'upcomingInstallations', 'ticketRecap', 'todayAttendance', 'inventoryItems', 'totalInventoryValue', 'financialData', 'deployedAssets'));
    }
}
