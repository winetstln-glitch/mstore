<?php

namespace App\Services;

use App\Models\Company;
use App\Models\ConsolidationItem;
use App\Models\ConsolidationReport;
use App\Models\IntercompanyTransaction;
use App\Models\JournalEntry;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ConsolidationEngineService
{
    public function getConsolidatedReport(string $startDate, string $endDate): array
    {
        $companies = Company::where('is_active', true)->get();
        $consolidatedData = [];

        foreach ($companies as $company) {
            // Get General Transactions count
            $transactionCount = \App\Models\GeneralTransaction::where('company_id', $company->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count();

            // Get total debit and credit from Journal Entries
            $debitTotal = \App\Models\JournalEntry::where('company_id', $company->id)
                ->whereHas('journal', function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('created_at', [$startDate, $endDate]);
                })
                ->sum('debit');

            $creditTotal = \App\Models\JournalEntry::where('company_id', $company->id)
                ->whereHas('journal', function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('created_at', [$startDate, $endDate]);
                })
                ->sum('credit');

            $consolidatedData[$company->id] = [
                'transactions' => $transactionCount,
                'debit' => $debitTotal,
                'credit' => $creditTotal,
            ];
        }

        return $consolidatedData;
    }

    public function generateReport(string $startDate, string $endDate): ConsolidationReport
    {
        return $this->generateConsolidationReport('financial', \Carbon\Carbon::parse($startDate), \Carbon\Carbon::parse($endDate));
    }

    public function generateConsolidationReport(
        string $reportType,
        Carbon $startDate,
        Carbon $endDate,
        string $currency = 'IDR'
    ): ConsolidationReport {
        return DB::transaction(function () use ($reportType, $startDate, $endDate, $currency) {
            $report = ConsolidationReport::create([
                'report_type' => $reportType,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'currency' => $currency,
                'status' => 'draft',
            ]);

            $companies = Company::where('is_active', true)->get();

            foreach ($companies as $company) {
                $this->addCompanyDataToReport($report, $company, $startDate, $endDate);
            }

            $this->applyIntercompanyEliminations($report);
            $this->calculateConsolidatedTotals($report);

            return $report->refresh();
        });
    }

    protected function addCompanyDataToReport(
        ConsolidationReport $report,
        Company $company,
        Carbon $startDate,
        Carbon $endDate
    ): void {
        $entries = JournalEntry::where('company_id', $company->id)
            ->whereHas('journal', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('created_at', [$startDate, $endDate]);
            })
            ->with('account')
            ->get();

        $groupedEntries = $entries->groupBy('account.code');

        foreach ($groupedEntries as $accountCode => $accountEntries) {
            $account = $accountEntries->first()->account;
            $totalAmount = $accountEntries->sum('debit') - $accountEntries->sum('credit');

            ConsolidationItem::create([
                'consolidation_report_id' => $report->id,
                'company_id' => $company->id,
                'account_code' => $accountCode,
                'account_name' => $account->name,
                'amount' => $totalAmount,
                'eliminated_amount' => 0,
                'consolidated_amount' => $totalAmount,
                'item_type' => $account->type ?? 'default',
            ]);
        }
    }

    protected function applyIntercompanyEliminations(ConsolidationReport $report): void
    {
        $ictEliminations = IntercompanyTransaction::whereIn('status', ['matched', 'settled'])
            ->whereBetween('created_at', [$report->start_date, $report->end_date])
            ->get();

        $totalEliminations = 0;

        foreach ($ictEliminations as $ict) {
            $totalEliminations += $ict->amount;

            $items = $report->items()
                ->whereIn('company_id', [$ict->from_company_id, $ict->to_company_id])
                ->get();

            foreach ($items as $item) {
                $eliminationAmount = $item->company_id == $ict->from_company_id 
                    ? $ict->amount 
                    : -$ict->amount;

                $item->update([
                    'eliminated_amount' => $item->eliminated_amount + $eliminationAmount,
                    'consolidated_amount' => $item->amount + ($item->eliminated_amount),
                ]);
            }
        }

        $report->update([
            'intercompany_eliminations' => $totalEliminations,
        ]);
    }

    protected function calculateConsolidatedTotals(ConsolidationReport $report): void
    {
        $totalRevenue = 0;
        $totalExpense = 0;

        foreach ($report->items as $item) {
            if (in_array($item->item_type, ['revenue', 'income'])) {
                $totalRevenue += $item->consolidated_amount;
            } elseif (in_array($item->item_type, ['expense', 'cost'])) {
                $totalExpense += $item->consolidated_amount;
            }
        }

        $report->update([
            'total_revenue' => $totalRevenue,
            'total_expense' => $totalExpense,
            'consolidated_profit' => $totalRevenue - $totalExpense,
            'status' => 'final',
        ]);
    }
}