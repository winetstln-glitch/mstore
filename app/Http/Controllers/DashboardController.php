<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use App\Models\Asset;
use App\Models\AtkTransaction;
use App\Models\Coordinator;
use App\Models\Customer;
use App\Models\GenieDeviceStatus;
use App\Models\Installation;
use App\Models\InventoryItem;
use App\Models\Setting;
use App\Models\TechnicianAttendance;
use App\Models\TechnicianDailySchedule;
use App\Models\TechnicianSchedule;
use App\Models\Ticket;
use App\Models\Transaction;
use App\Models\User;
use App\Models\WashTransaction;
use App\Services\MixRadiusService;
use App\Services\SystemMetricsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    protected function monitorLogsData(int $limit = 20): array
    {
        $threshold = (int) Setting::getValue('genieacs_online_threshold_minutes', 15);

        return GenieDeviceStatus::with('customer:id,name')
            ->orderByDesc('updated_at')
            ->take($limit)
            ->get()
            ->map(function (GenieDeviceStatus $log) use ($threshold) {
                $isFresh = $log->updated_at && $log->updated_at->gte(now()->subMinutes($threshold));
                $isOnline = (bool) $log->is_online && $isFresh;
                $notifyDown = $log->last_notified_down_at ? $log->last_notified_down_at->format('d M H:i') : null;
                $notifyUp = $log->last_notified_up_at ? $log->last_notified_up_at->format('d M H:i') : null;
                $notifyText = $notifyUp ? 'UP: '.$notifyUp : ($notifyDown ? 'DOWN: '.$notifyDown : '-');
                $customerName = $log->customer->name ?? ('#'.$log->customer_id);
                $statusText = $isOnline ? 'ONLINE' : 'OFFLINE';
                $reasonText = (string) ($log->last_reason ?? '-');
                if (! $isFresh) {
                    $reasonText = 'Status stale (update terakhir lewat 15 menit)';
                }
                $searchText = strtolower(implode(' ', [
                    $customerName,
                    (string) ($log->onu_serial ?? ''),
                    (string) ($log->tr069_ip ?? ''),
                    $reasonText,
                    $statusText,
                ]));

                return [
                    'updated_at' => $log->updated_at?->format('d M Y H:i') ?? '-',
                    'customer_name' => $customerName,
                    'onu_serial' => $log->onu_serial ?: '-',
                    'status' => $statusText,
                    'status_key' => strtolower($statusText),
                    'tr069_ip' => $log->tr069_ip ?: '-',
                    'notify_text' => $notifyText,
                    'reason_short' => Str::limit($reasonText, 70),
                    'search_text' => $searchText,
                ];
            })
            ->values()
            ->all();
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        if ($user && $user->hasRole('customer')) {
            return redirect()->route('client.dashboard');
        }
        if ($user && $user->hasRole('kasir-atk') && $user->hasPermission('atk.view')) {
            return redirect()->route('atk.dashboard');
        }
        if (
            $user &&
            ($user->hasRole('kasir-wash') || $user->hasRole('karyawan-wash')) &&
            $user->hasPermission('wash.view')
        ) {
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
            $attendanceOverview = $this->buildRoleAttendanceOverview('technician');
            $shiftSchedule = $this->getTodayShiftSchedule($user->id);
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

            return view('technician.dashboard', compact(
                'stats',
                'activeTickets',
                'pendingInstallations',
                'todayAttendance',
                'attendanceOverview',
                'shiftSchedule'
            ));
        }

        // Base Queries for Dashboard Logic (Coordinator / Admin / Finance)
        $customerQuery = Customer::query();
        $ticketQuery = Ticket::query();
        $installationQuery = Installation::query();
        $currentYear = now()->year;
        $currentMonthStart = now()->copy()->startOfMonth();
        $prevMonthStart = now()->copy()->subMonthNoOverflow()->startOfMonth();
        $prevMonthEnd = now()->copy()->subMonthNoOverflow()->endOfMonth();

        // Filter Logic: Exclude Admin, Administrator, and Finance Staff from filtering
        if (! $user->hasRole('admin') && ! $user->hasRole('administrator') && ! $user->hasRole('finance')) {
            $coordinator = Coordinator::select('id', 'region_id')->where('user_id', $user->id)->first();
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

        $technicianIds = User::query()
            ->where('is_active', true)
            ->whereHas('role', function ($q) {
                $q->where('name', 'technician');
            })
            ->pluck('id');
        $washEmployeeIds = User::query()
            ->where('is_active', true)
            ->whereHas('role', function ($q) {
                $q->where('name', 'karyawan-wash');
            })
            ->pluck('id');
        $today = today();
        $tomorrow = today()->addDay();

        $technicianPresentToday = TechnicianAttendance::where('clock_in', '>=', $today)
            ->where('clock_in', '<', $tomorrow)
            ->whereIn('status', ['present', 'late'])
            ->whereIn('user_id', $technicianIds)
            ->distinct('user_id')
            ->count('user_id');
        $washEmployeePresentToday = TechnicianAttendance::where('clock_in', '>=', $today)
            ->where('clock_in', '<', $tomorrow)
            ->whereIn('status', ['present', 'late'])
            ->whereIn('user_id', $washEmployeeIds)
            ->distinct('user_id')
            ->count('user_id');

        $attendanceRole = (string) $request->query('attendance_role', 'technician');
        if (! in_array($attendanceRole, ['technician', 'karyawan-wash'], true)) {
            $attendanceRole = 'technician';
        }
        $attendanceState = (string) $request->query('attendance_state', 'present');
        if (! in_array($attendanceState, ['present', 'not_present'], true)) {
            $attendanceState = 'present';
        }
        $attendanceDateInput = (string) $request->query('attendance_date', now()->toDateString());
        try {
            $attendanceDateObj = Carbon::createFromFormat('Y-m-d', $attendanceDateInput);
            $attendanceDateStart = $attendanceDateObj->copy()->startOfDay();
            $attendanceDateEnd = $attendanceDateObj->copy()->endOfDay();
            $attendanceDate = $attendanceDateObj->toDateString();
        } catch (\Throwable $e) {
            $attendanceDateStart = now()->startOfDay();
            $attendanceDateEnd = now()->endOfDay();
            $attendanceDate = now()->toDateString();
        }
        $attendanceDateLabel = $attendanceDateStart->translatedFormat('d M Y');
        $selectedRoleIds = $attendanceRole === 'karyawan-wash' ? $washEmployeeIds : $technicianIds;
        $attendanceByUser = TechnicianAttendance::query()
            ->where('clock_in', '>=', $attendanceDateStart)
            ->where('clock_in', '<=', $attendanceDateEnd)
            ->whereIn('user_id', $selectedRoleIds)
            ->orderByDesc('clock_in')
            ->get()
            ->groupBy('user_id')
            ->map(fn ($rows) => $rows->first());
        $presentStatuses = ['present', 'late'];
        $presentUserIds = $attendanceByUser
            ->filter(fn ($attendance) => in_array((string) $attendance->status, $presentStatuses, true))
            ->keys()
            ->values()
            ->all();
        $attendanceEmployeesQuery = User::query()
            ->with('role')
            ->where('is_active', true)
            ->whereHas('role', function ($q) use ($attendanceRole) {
                $q->where('name', $attendanceRole);
            });
        if ($attendanceState === 'present') {
            if ($presentUserIds === []) {
                $attendanceEmployees = collect();
            } else {
                $attendanceEmployees = $attendanceEmployeesQuery
                    ->whereIn('id', $presentUserIds)
                    ->orderBy('name')
                    ->get();
            }
        } else {
            $attendanceEmployees = $attendanceEmployeesQuery
                ->when($presentUserIds !== [], fn ($q) => $q->whereNotIn('id', $presentUserIds))
                ->orderBy('name')
                ->get();
        }
        $technicianTaskSummary = collect();
        if ($attendanceRole === 'technician' && $attendanceEmployees->isNotEmpty()) {
            $technicianIdsForTable = $attendanceEmployees->pluck('id')->values()->all();
            $activeTicketRows = DB::table('ticket_user')
                ->join('tickets', 'tickets.id', '=', 'ticket_user.ticket_id')
                ->whereIn('ticket_user.user_id', $technicianIdsForTable)
                ->whereIn('tickets.status', ['open', 'assigned', 'in_progress', 'pending'])
                ->orderByDesc('tickets.updated_at')
                ->get(['ticket_user.user_id', 'tickets.id']);
            $activeTicketIdsByUser = $activeTicketRows
                ->groupBy('user_id')
                ->map(fn (Collection $rows) => (int) optional($rows->first())->id);
            $activeTicketCounts = DB::table('ticket_user')
                ->join('tickets', 'tickets.id', '=', 'ticket_user.ticket_id')
                ->select('ticket_user.user_id', DB::raw('COUNT(DISTINCT tickets.id) as total'))
                ->whereIn('ticket_user.user_id', $technicianIdsForTable)
                ->whereIn('tickets.status', ['open', 'assigned', 'in_progress', 'pending'])
                ->groupBy('ticket_user.user_id')
                ->pluck('total', 'ticket_user.user_id');
            $activeInstallationCounts = Installation::query()
                ->select('technician_id', DB::raw('COUNT(*) as total'))
                ->whereIn('technician_id', $technicianIdsForTable)
                ->whereIn('status', ['registered', 'survey', 'approved', 'installation'])
                ->groupBy('technician_id')
                ->pluck('total', 'technician_id');
            $activeInstallationIdsByUser = Installation::query()
                ->whereIn('technician_id', $technicianIdsForTable)
                ->whereIn('status', ['registered', 'survey', 'approved', 'installation'])
                ->orderByDesc('updated_at')
                ->get(['id', 'technician_id'])
                ->groupBy('technician_id')
                ->map(fn ($rows) => (int) optional($rows->first())->id);
            $technicianTaskSummary = $attendanceEmployees->mapWithKeys(function ($employee) use ($activeTicketCounts, $activeInstallationCounts, $activeTicketIdsByUser, $activeInstallationIdsByUser) {
                $ticketCount = (int) ($activeTicketCounts[$employee->id] ?? 0);
                $installationCount = (int) ($activeInstallationCounts[$employee->id] ?? 0);
                $totalTask = $ticketCount + $installationCount;

                return [
                    $employee->id => [
                        'active_ticket_id' => (int) ($activeTicketIdsByUser[$employee->id] ?? 0),
                        'active_installation_id' => (int) ($activeInstallationIdsByUser[$employee->id] ?? 0),
                        'ticket_active' => $ticketCount,
                        'installation_active' => $installationCount,
                        'total_active' => $totalTask,
                        'label' => $totalTask > 0 ? 'Bertugas' : 'Standby',
                    ],
                ];
            });
        }

        $stats = [
            'total_customers' => $customerQuery->count(),
            'new_customers_this_month' => $customerQuery->clone()->where('created_at', '>=', $currentMonthStart)->count(),
            'open_tickets' => $ticketQuery->clone()->where('status', 'open')->count(),
            'tickets_today' => $ticketQuery->clone()->where('created_at', '>=', $today)->count(),
            'pending_installations' => $installationQuery->clone()->whereIn('status', ['registered', 'survey', 'approved'])->count(),
            'atk_today' => AtkTransaction::where('created_at', '>=', $today)->count(),
            'atk_month_revenue' => AtkTransaction::where('created_at', '>=', $currentMonthStart)->sum('total_amount'),
            'wash_today' => WashTransaction::where('created_at', '>=', $today)->count(),
            'wash_month_revenue' => WashTransaction::where('created_at', '>=', $currentMonthStart)->sum('total_amount'),
            'technician_total' => $technicianIds->count(),
            'technician_present_today' => $technicianPresentToday,
            'technician_not_present_today' => max($technicianIds->count() - $technicianPresentToday, 0),
            'wash_employee_total' => $washEmployeeIds->count(),
            'wash_employee_present_today' => $washEmployeePresentToday,
            'wash_employee_not_present_today' => max($washEmployeeIds->count() - $washEmployeePresentToday, 0),
            'total_olts' => \App\Models\OLT::count(),
            'online_olts' => \App\Models\OLT::where('status', 'online')->count(),
            'total_onts' => \App\Models\ONT::withoutTrashed()->count(),
            'online_onts' => \App\Models\ONT::withoutTrashed()->where('oper_status', 'online')->count(),
        ];

        $presenceCutoff = now()->subSeconds(90);
        $onlineTechnicians = User::query()
            ->where('is_active', true)
            ->where('last_seen_at', '>=', $presenceCutoff)
            ->whereHas('role', fn ($query) => $query->where('name', 'technician'))
            ->count();
        $onlineWashEmployees = User::query()
            ->where('is_active', true)
            ->where('last_seen_at', '>=', $presenceCutoff)
            ->whereHas('role', fn ($query) => $query->where('name', 'karyawan-wash'))
            ->count();

        // Traffic Data (Orders & Tickets per Month)
        $trafficData = [
            'labels' => [],
            'orders' => [],
            'tickets' => [],
        ];

        $atkMonthly = $this->aggregateCountByMonth(AtkTransaction::query(), 'created_at', $currentYear);
        $washMonthly = $this->aggregateCountByMonth(WashTransaction::query(), 'created_at', $currentYear);
        $ticketsMonthly = $this->aggregateCountByMonth($ticketQuery->clone(), 'created_at', $currentYear);

        for ($i = 1; $i <= 12; $i++) {
            $trafficData['labels'][] = \Carbon\Carbon::create(null, $i, 1)->format('F');
            $ordersCount = ($atkMonthly->get($i, 0)) + ($washMonthly->get($i, 0));
            $trafficData['orders'][] = $ordersCount;
            $trafficData['tickets'][] = $ticketsMonthly->get($i, 0);
        }

        // Orders Month-over-Month Growth
        $ordersCurrentMonth = AtkTransaction::where('created_at', '>=', $currentMonthStart)->count()
            + WashTransaction::where('created_at', '>=', $currentMonthStart)->count();
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
        $monthlyTicketTotals = $this->aggregateCountByMonth($ticketQuery->clone(), 'created_at', $currentYear);
        $monthlyTicketResolved = $this->aggregateCountByMonth(
            $ticketQuery->clone()->whereIn('status', ['solved', 'closed']),
            'created_at',
            $currentYear
        );
        $monthlyTicketOpen = $this->aggregateCountByMonth(
            $ticketQuery->clone()->whereIn('status', ['open', 'assigned', 'in_progress', 'pending']),
            'created_at',
            $currentYear
        );

        $ticketRecap = [];
        for ($i = 1; $i <= 12; $i++) {
            $monthName = \Carbon\Carbon::create(null, $i, 1)->format('F');

            $ticketRecap[] = [
                'month' => $monthName,
                'total' => (int) ($monthlyTicketTotals->get($i, 0)),
                'resolved' => (int) ($monthlyTicketResolved->get($i, 0)),
                'open' => (int) ($monthlyTicketOpen->get($i, 0)),
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
        $incomeData = $this->aggregateSumByMonth(
            Transaction::query()->where('type', 'income'),
            'transaction_date',
            'amount',
            $currentYear
        );
        $expenseData = $this->aggregateSumByMonth(
            Transaction::query()->where('type', 'expense'),
            'transaction_date',
            'amount',
            $currentYear
        );

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
            'monitorTrend',
            'attendanceRole',
            'attendanceState',
            'attendanceDate',
            'attendanceDateLabel',
            'attendanceEmployees',
            'attendanceByUser',
            'technicianTaskSummary',
            'onlineTechnicians',
            'onlineWashEmployees'
        ));
    }

    public function monitorLogs()
    {
        $user = Auth::user();
        if (! $user || ! $user->hasPermission('dashboard.view')) {
            abort(403);
        }

        return response()->json([
            'logs' => $this->monitorLogsData(),
        ]);
    }

    protected function aggregateCountByMonth(Builder $query, string $dateColumn, int $year): Collection
    {
        return $this->aggregateByMonth($query, $dateColumn, 'COUNT(*)', 'total', $year);
    }

    protected function aggregateSumByMonth(Builder $query, string $dateColumn, string $sumColumn, int $year): Collection
    {
        $qualifiedColumn = $query->getModel()->qualifyColumn($sumColumn);

        return $this->aggregateByMonth($query, $dateColumn, 'COALESCE(SUM('.$qualifiedColumn.'), 0)', 'total', $year);
    }

    protected function aggregateByMonth(Builder $query, string $dateColumn, string $aggregateExpression, string $alias, int $year): Collection
    {
        $qualifiedDateColumn = $query->getModel()->qualifyColumn($dateColumn);
        $monthExpression = $this->monthExpression($qualifiedDateColumn);

        return $query
            ->whereYear($dateColumn, $year)
            ->selectRaw($monthExpression.' as month_key, '.$aggregateExpression.' as '.$alias)
            ->groupBy('month_key')
            ->pluck($alias, 'month_key')
            ->mapWithKeys(fn ($value, $month) => [(int) $month => $value]);
    }

    protected function monthExpression(string $qualifiedDateColumn): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => "CAST(strftime('%m', {$qualifiedDateColumn}) AS INTEGER)",
            'pgsql' => "CAST(EXTRACT(MONTH FROM {$qualifiedDateColumn}) AS INTEGER)",
            default => "MONTH({$qualifiedDateColumn})",
        };
    }

    public function buildRoleAttendanceOverview(string $roleName): array
    {
        $roleUserIds = User::query()
            ->where('is_active', true)
            ->whereHas('role', function ($query) use ($roleName) {
                $query->where('name', $roleName);
            })
            ->pluck('id');

        $presentCount = 0;
        if ($roleUserIds->isNotEmpty()) {
            $presentCount = TechnicianAttendance::query()
                ->whereDate('clock_in', today())
                ->whereIn('status', ['present', 'late'])
                ->whereIn('user_id', $roleUserIds)
                ->distinct('user_id')
                ->count('user_id');
        }

        $totalEmployees = $roleUserIds->count();

        return [
            'role' => $roleName,
            'total' => $totalEmployees,
            'present' => $presentCount,
            'not_present' => max($totalEmployees - $presentCount, 0),
        ];
    }

    public function getTodayShiftSchedule(int $userId): ?TechnicianDailySchedule
    {
        $daily = TechnicianDailySchedule::query()
            ->where('user_id', $userId)
            ->whereDate('date', today())
            ->first();
        if ($daily) {
            return $daily;
        }

        $weekly = TechnicianSchedule::query()
            ->where('user_id', $userId)
            ->where('year', now()->weekYear)
            ->where('week_number', now()->weekOfYear)
            ->first();
        if (! $weekly) {
            return null;
        }

        $fallback = new TechnicianDailySchedule;
        $fallback->user_id = $userId;
        $fallback->date = today();
        $fallback->status = (string) ($weekly->status ?: TechnicianSchedule::STATUS_OFF);
        $fallback->notes = $weekly->notes;

        return $fallback;
    }
}
