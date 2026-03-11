<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AtkTransaction;
use App\Models\Coordinator;
use App\Models\Customer;
use App\Models\Installation;
use App\Models\InventoryItem;
use App\Models\Setting;
use App\Models\TechnicianAttendance;
use App\Models\Ticket;
use App\Models\Transaction;
use App\Models\WashTransaction;
use App\Services\MixRadiusService;
use App\Services\SystemMetricsService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        if ($user && $user->hasRole('customer')) {
            return redirect()->route('client.dashboard');
        }
        if ($user && $user->hasRole('kasir-atk')) {
            return redirect()->route('atk.dashboard');
        }
        if ($user && $user->hasRole('kasir-wash')) {
            return redirect()->route('wash.dashboard');
        }
        if (! $user || ! $user->hasPermission('dashboard.view')) {
            abort(403);
        }

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
        if (! $user->hasRole('admin') && ! $user->hasRole('finance')) {
            $coordinator = Coordinator::where('user_id', $user->id)->first();
            if ($coordinator && $coordinator->region_id) {
                // Filter Customers by Region
                $customerQuery->whereHas('odp', function ($q) use ($coordinator) {
                    $q->where('region_id', $coordinator->region_id);
                });

                // Filter Tickets (Linked to Customer in Region)
                $ticketQuery->whereHas('customer.odp', function ($q) use ($coordinator) {
                    $q->where('region_id', $coordinator->region_id);
                });

                // Filter Installations (Linked to Customer in Region)
                $installationQuery->whereHas('customer.odp', function ($q) use ($coordinator) {
                    $q->where('region_id', $coordinator->region_id);
                });
            }
        }

        $stats = [
            'total_customers' => $customerQuery->count(),
            'new_customers_this_month' => $customerQuery->clone()->where('created_at', '>=', now()->startOfMonth())->count(),
            'open_tickets' => $ticketQuery->clone()->where('status', 'open')->count(),
            'tickets_today' => $ticketQuery->clone()->whereDate('created_at', today())->count(),
            'pending_installations' => $installationQuery->clone()->whereIn('status', ['registered', 'survey', 'approved'])->count(),
            'atk_today' => AtkTransaction::whereDate('created_at', today())->count(),
            'atk_month_revenue' => AtkTransaction::where('created_at', '>=', now()->startOfMonth())->sum('total_amount'),
            'wash_today' => WashTransaction::whereDate('created_at', today())->count(),
            'wash_month_revenue' => WashTransaction::where('created_at', '>=', now()->startOfMonth())->sum('total_amount'),
        ];

        // Traffic Data (Orders & Tickets per Month)
        $trafficData = [
            'labels' => [],
            'orders' => [],
            'tickets' => [],
        ];

        $atkMonthly = AtkTransaction::whereYear('created_at', now()->year)
            ->get()
            ->groupBy(function ($t) {
                return $t->created_at->format('n');
            })
            ->map->count();

        $washMonthly = WashTransaction::whereYear('created_at', now()->year)
            ->get()
            ->groupBy(function ($t) {
                return $t->created_at->format('n');
            })
            ->map->count();

        $ticketsMonthly = $ticketQuery->clone()
            ->whereYear('created_at', now()->year)
            ->get()
            ->groupBy(function ($ticket) {
                return $ticket->created_at->format('n');
            })
            ->map->count();

        for ($i = 1; $i <= 12; $i++) {
            $trafficData['labels'][] = \Carbon\Carbon::create(null, $i, 1)->format('F');
            $ordersCount = ($atkMonthly->get($i, 0)) + ($washMonthly->get($i, 0));
            $trafficData['orders'][] = $ordersCount;
            $trafficData['tickets'][] = $ticketsMonthly->get($i, 0);
        }

        // Orders Month-over-Month Growth
        $ordersCurrentMonth = AtkTransaction::where('created_at', '>=', now()->startOfMonth())->count()
            + WashTransaction::where('created_at', '>=', now()->startOfMonth())->count();
        $prevMonthStart = now()->subMonthNoOverflow()->startOfMonth();
        $prevMonthEnd = now()->subMonthNoOverflow()->endOfMonth();
        $ordersPrevMonth = AtkTransaction::whereBetween('created_at', [$prevMonthStart, $prevMonthEnd])->count()
            + WashTransaction::whereBetween('created_at', [$prevMonthStart, $prevMonthEnd])->count();
        $ordersGrowthPercent = $ordersPrevMonth > 0
            ? round((($ordersCurrentMonth - $ordersPrevMonth) / $ordersPrevMonth) * 100, 1)
            : ($ordersCurrentMonth > 0 ? 100.0 : 0.0);
        $trafficStats = [
            'orders_growth_percent' => $ordersGrowthPercent,
        ];

        // System Metrics (CPU/RAM/Swap) to power SupportTracker & RevenueSources charts
        $metricsService = new SystemMetricsService;
        $systemMetrics = $metricsService->getMetrics();

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
            ->groupBy(function ($ticket) {
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
            'expense' => [],
        ];

        // Fetch Income Data (Collection-based grouping for DB compatibility)
        $incomeData = Transaction::where('type', 'income')
            ->whereYear('transaction_date', now()->year)
            ->get()
            ->groupBy(function ($transaction) {
                return $transaction->transaction_date->format('n'); // Group by month number (1-12)
            })
            ->map(function ($transactions) {
                return $transactions->sum('amount');
            });

        // Fetch Expense Data
        $expenseData = Transaction::where('type', 'expense')
            ->whereYear('transaction_date', now()->year)
            ->get()
            ->groupBy(function ($transaction) {
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

        $mixRadiusOk = app(MixRadiusService::class)->isAvailable();
        $monitorSummaryRaw = Setting::getValue('network_monitor_summary');
        $monitorSummary = is_string($monitorSummaryRaw) ? json_decode($monitorSummaryRaw, true) : null;
        if (! is_array($monitorSummary)) {
            $monitorSummary = null;
        }
        $monitorHistoryRaw = Setting::getValue('network_monitor_history');
        $monitorHistory = is_string($monitorHistoryRaw) ? json_decode($monitorHistoryRaw, true) : [];
        if (! is_array($monitorHistory)) {
            $monitorHistory = [];
        }
        $dailyLatest = [];
        foreach ($monitorHistory as $item) {
            if (! is_array($item) || empty($item['ran_at'])) {
                continue;
            }
            $dateKey = \Carbon\Carbon::parse($item['ran_at'])->format('Y-m-d');
            $dailyLatest[$dateKey] = [
                'checked' => (int) ($item['checked'] ?? 0),
                'down' => (int) ($item['down'] ?? 0),
                'tickets_created' => (int) ($item['tickets_created'] ?? 0),
                'errors' => (int) ($item['errors'] ?? 0),
            ];
        }
        $monitorTrend = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $monitorTrend[] = [
                'date' => $date,
                'label' => now()->subDays($i)->format('d M'),
                'checked' => $dailyLatest[$date]['checked'] ?? 0,
                'down' => $dailyLatest[$date]['down'] ?? 0,
                'tickets_created' => $dailyLatest[$date]['tickets_created'] ?? 0,
                'errors' => $dailyLatest[$date]['errors'] ?? 0,
            ];
        }

        return view('dashboard', compact(
            'stats',
            'recentTickets',
            'upcomingInstallations',
            'ticketRecap',
            'todayAttendance',
            'inventoryItems',
            'totalInventoryValue',
            'financialData',
            'deployedAssets',
            'trafficData',
            'trafficStats',
            'systemMetrics',
            'mixRadiusOk',
            'monitorSummary',
            'monitorTrend'
        ));
    }
}
