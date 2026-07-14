<?php

namespace App\Http\Controllers;

use App\Models\BusinessUnit;
use App\Models\DailySummary;
use App\Models\MonthlySummary;
use App\Models\YearlySummary;
use App\Services\SummaryService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ConsolidatedDashboardController extends Controller
{
    public function __construct(
        protected SummaryService $summaryService
    ) {
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $isAdmin = $user->hasRole('admin') || $user->hasRole('finance');

        $filters = $request->only([
            'business_unit_id', 'branch_id', 'profit_center_id', 'cost_center_id',
            'year', 'month', 'date'
        ]);

        $businessUnits = BusinessUnit::all();

        $selectedYear = $filters['year'] ?? now()->year;
        $selectedMonth = $filters['month'] ?? now()->month;
        $selectedDate = isset($filters['date']) ? Carbon::parse($filters['date']) : now();

        $dailySummary = DailySummary::where('summary_date', $selectedDate->toDateString())
            ->when($filters['business_unit_id'] ?? null, fn($q, $id) => $q->where('business_unit_id', $id))
            ->when($filters['branch_id'] ?? null, fn($q, $id) => $q->where('branch_id', $id))
            ->when($filters['profit_center_id'] ?? null, fn($q, $id) => $q->where('profit_center_id', $id))
            ->when($filters['cost_center_id'] ?? null, fn($q, $id) => $q->where('cost_center_id', $id))
            ->first();

        $monthlySummary = MonthlySummary::where('year', $selectedYear)
            ->where('month', $selectedMonth)
            ->when($filters['business_unit_id'] ?? null, fn($q, $id) => $q->where('business_unit_id', $id))
            ->when($filters['branch_id'] ?? null, fn($q, $id) => $q->where('branch_id', $id))
            ->when($filters['profit_center_id'] ?? null, fn($q, $id) => $q->where('profit_center_id', $id))
            ->when($filters['cost_center_id'] ?? null, fn($q, $id) => $q->where('cost_center_id', $id))
            ->first();

        $yearlySummary = YearlySummary::where('year', $selectedYear)
            ->when($filters['business_unit_id'] ?? null, fn($q, $id) => $q->where('business_unit_id', $id))
            ->when($filters['branch_id'] ?? null, fn($q, $id) => $q->where('branch_id', $id))
            ->when($filters['profit_center_id'] ?? null, fn($q, $id) => $q->where('profit_center_id', $id))
            ->when($filters['cost_center_id'] ?? null, fn($q, $id) => $q->where('cost_center_id', $id))
            ->first();

        $chartData = $this->getChartData($selectedYear, $filters);

        return view('dashboard.consolidated', compact(
            'isAdmin',
            'businessUnits',
            'filters',
            'dailySummary',
            'monthlySummary',
            'yearlySummary',
            'chartData',
            'selectedYear',
            'selectedMonth',
            'selectedDate'
        ));
    }

    public function generateSummary(Request $request)
    {
        $type = $request->input('type'); // daily, monthly, yearly
        $filters = $request->only([
            'business_unit_id', 'branch_id', 'profit_center_id', 'cost_center_id',
            'year', 'month', 'date'
        ]);

        try {
            if ($type === 'daily') {
                $date = isset($filters['date']) ? Carbon::parse($filters['date']) : now();
                $this->summaryService->generateDailySummary($date, $filters);
            } elseif ($type === 'monthly') {
                $year = $filters['year'] ?? now()->year;
                $month = $filters['month'] ?? now()->month;
                $this->summaryService->generateMonthlySummary($year, $month, $filters);
            } elseif ($type === 'yearly') {
                $year = $filters['year'] ?? now()->year;
                $this->summaryService->generateYearlySummary($year, $filters);
            }

            return redirect()->back()->with('success', 'Summary berhasil di-generate!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal generate summary: ' . $e->getMessage());
        }
    }

    protected function getChartData(int $year, array $filters = []): array
    {
        $monthlyData = MonthlySummary::where('year', $year)
            ->when($filters['business_unit_id'] ?? null, fn($q, $id) => $q->where('business_unit_id', $id))
            ->when($filters['branch_id'] ?? null, fn($q, $id) => $q->where('branch_id', $id))
            ->when($filters['profit_center_id'] ?? null, fn($q, $id) => $q->where('profit_center_id', $id))
            ->when($filters['cost_center_id'] ?? null, fn($q, $id) => $q->where('cost_center_id', $id))
            ->orderBy('month')
            ->get();

        $labels = [];
        $incomeData = [];
        $expenseData = [];
        $profitData = [];

        for ($i = 1; $i <= 12; $i++) {
            $labels[] = Carbon::create()->month($i)->formatLocalized('%B');
            $monthly = $monthlyData->firstWhere('month', $i);
            $incomeData[] = $monthly ? (float)$monthly->total_income : 0;
            $expenseData[] = $monthly ? (float)$monthly->total_expense : 0;
            $profitData[] = $monthly ? (float)$monthly->total_profit : 0;
        }

        return [
            'labels' => $labels,
            'income' => $incomeData,
            'expense' => $expenseData,
            'profit' => $profitData,
        ];
    }
}