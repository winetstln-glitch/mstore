<?php

namespace App\Services;

use App\Models\DailySummary;
use App\Models\GeneralTransaction;
use App\Models\MonthlySummary;
use App\Models\YearlySummary;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SummaryService
{
    public function generateDailySummary(Carbon $date, array $filters = []): DailySummary
    {
        return DB::transaction(function () use ($date, $filters) {
            $summary = DailySummary::updateOrCreate(
                [
                    'business_unit_id' => $filters['business_unit_id'] ?? null,
                    'branch_id' => $filters['branch_id'] ?? null,
                    'profit_center_id' => $filters['profit_center_id'] ?? null,
                    'cost_center_id' => $filters['cost_center_id'] ?? null,
                    'summary_date' => $date->toDateString(),
                ],
                $this->calculateDailyStats($date, $filters)
            );

            return $summary;
        });
    }

    protected function calculateDailyStats(Carbon $date, array $filters = []): array
    {
        $query = GeneralTransaction::whereDate('created_at', $date)->where('status', 'posted');

        if (isset($filters['business_unit_id'])) {
            $query->where('business_unit_id', $filters['business_unit_id']);
        }
        if (isset($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }
        if (isset($filters['profit_center_id'])) {
            $query->where('profit_center_id', $filters['profit_center_id']);
        }
        if (isset($filters['cost_center_id'])) {
            $query->where('cost_center_id', $filters['cost_center_id']);
        }

        $transactions = $query->get();
        $totalIncome = 0;
        $totalExpense = 0;

        foreach ($transactions as $tx) {
            if (in_array($tx->transaction_type, ['invoice', 'wash', 'atk'])) {
                $totalIncome += $tx->amount;
            } elseif ($tx->transaction_type === 'expense') {
                $totalExpense += $tx->amount;
            }
        }

        $totalTransactions = $transactions->count();
        $totalCustomersServed = $transactions->unique('reference_id')->count();

        return [
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'total_profit' => $totalIncome - $totalExpense,
            'total_transactions' => $totalTransactions,
            'total_customers_served' => $totalCustomersServed,
        ];
    }

    public function generateMonthlySummary(int $year, int $month, array $filters = []): MonthlySummary
    {
        return DB::transaction(function () use ($year, $month, $filters) {
            $summary = MonthlySummary::updateOrCreate(
                [
                    'business_unit_id' => $filters['business_unit_id'] ?? null,
                    'branch_id' => $filters['branch_id'] ?? null,
                    'profit_center_id' => $filters['profit_center_id'] ?? null,
                    'cost_center_id' => $filters['cost_center_id'] ?? null,
                    'year' => $year,
                    'month' => $month,
                ],
                $this->calculateMonthlyStats($year, $month, $filters)
            );

            return $summary;
        });
    }

    protected function calculateMonthlyStats(int $year, int $month, array $filters = []): array
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $query = GeneralTransaction::whereBetween('created_at', [$startDate, $endDate])->where('status', 'posted');

        if (isset($filters['business_unit_id'])) {
            $query->where('business_unit_id', $filters['business_unit_id']);
        }
        if (isset($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }
        if (isset($filters['profit_center_id'])) {
            $query->where('profit_center_id', $filters['profit_center_id']);
        }
        if (isset($filters['cost_center_id'])) {
            $query->where('cost_center_id', $filters['cost_center_id']);
        }

        $transactions = $query->get();
        $totalIncome = 0;
        $totalExpense = 0;

        foreach ($transactions as $tx) {
            if (in_array($tx->transaction_type, ['invoice', 'wash', 'atk'])) {
                $totalIncome += $tx->amount;
            } elseif ($tx->transaction_type === 'expense') {
                $totalExpense += $tx->amount;
            }
        }

        $totalTransactions = $transactions->count();
        $totalCustomersServed = $transactions->unique('reference_id')->count();

        return [
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'total_profit' => $totalIncome - $totalExpense,
            'total_transactions' => $totalTransactions,
            'total_customers_served' => $totalCustomersServed,
        ];
    }

    public function generateYearlySummary(int $year, array $filters = []): YearlySummary
    {
        return DB::transaction(function () use ($year, $filters) {
            $summary = YearlySummary::updateOrCreate(
                [
                    'business_unit_id' => $filters['business_unit_id'] ?? null,
                    'branch_id' => $filters['branch_id'] ?? null,
                    'profit_center_id' => $filters['profit_center_id'] ?? null,
                    'cost_center_id' => $filters['cost_center_id'] ?? null,
                    'year' => $year,
                ],
                $this->calculateYearlyStats($year, $filters)
            );

            return $summary;
        });
    }

    protected function calculateYearlyStats(int $year, array $filters = []): array
    {
        $startDate = Carbon::create($year, 1, 1)->startOfYear();
        $endDate = $startDate->copy()->endOfYear();

        $query = GeneralTransaction::whereBetween('created_at', [$startDate, $endDate])->where('status', 'posted');

        if (isset($filters['business_unit_id'])) {
            $query->where('business_unit_id', $filters['business_unit_id']);
        }
        if (isset($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }
        if (isset($filters['profit_center_id'])) {
            $query->where('profit_center_id', $filters['profit_center_id']);
        }
        if (isset($filters['cost_center_id'])) {
            $query->where('cost_center_id', $filters['cost_center_id']);
        }

        $transactions = $query->get();
        $totalIncome = 0;
        $totalExpense = 0;

        foreach ($transactions as $tx) {
            if (in_array($tx->transaction_type, ['invoice', 'wash', 'atk'])) {
                $totalIncome += $tx->amount;
            } elseif ($tx->transaction_type === 'expense') {
                $totalExpense += $tx->amount;
            }
        }

        $totalTransactions = $transactions->count();
        $totalCustomersServed = $transactions->unique('reference_id')->count();

        return [
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'total_profit' => $totalIncome - $totalExpense,
            'total_transactions' => $totalTransactions,
            'total_customers_served' => $totalCustomersServed,
        ];
    }
}