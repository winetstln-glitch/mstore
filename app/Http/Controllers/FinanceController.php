<?php

namespace App\Http\Controllers;

use App\Models\Coordinator;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\InventoryTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Common\Entity\Row;

class FinanceController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:finance.view', only: ['index', 'show', 'coordinatorDetail', 'downloadCoordinatorPdf', 'profitLoss', 'downloadProfitLossPdf', 'downloadProfitLossExcel', 'managerReport', 'downloadManagerReportPdf', 'downloadManagerReportExcel', 'materialReport', 'exportAccounting', 'settings']),
            new Middleware('permission:finance.manage', only: ['create', 'store', 'edit', 'update', 'destroy']),
        ];
    }

    public function downloadIncomeBreakdownPdf()
    {
        if (!Auth::user()->hasRole('admin') && !Auth::user()->hasRole('finance')) {
            abort(403, 'Unauthorized action.');
        }

        $incomeBreakdowns = [];
        $recentIncomes = Transaction::where('type', 'income')
            ->whereIn('category', ['Member Income', 'Voucher Income'])
            ->with('coordinator')
            ->latest('transaction_date')
            ->take(10)
            ->get();

        foreach ($recentIncomes as $inc) {
            $com = Transaction::where('reference_number', 'COM-' . $inc->id)->value('amount') ?? 0;
            $isp = Transaction::where('reference_number', 'ISP-' . $inc->id)->value('amount') ?? 0;
            $tool = Transaction::where('reference_number', 'TOOL-' . $inc->id)->value('amount') ?? 0;
            $cash = Transaction::where('reference_number', 'INV-CASH-' . $inc->id)->value('amount') ?? 0;
            $shares = Transaction::where('reference_number', 'INV-' . $inc->id)->sum('amount');
            
            // Fetch Investors linked to this transaction
            $investorNames = Transaction::where('reference_number', 'INV-' . $inc->id)
                ->with('investor')
                ->get()
                ->pluck('investor.name')
                ->unique()
                ->implode(', ');
            
            $netBalance = $inc->amount - $com - $isp - $tool;
            $managerIncome = $com + $isp + $tool;

            // Cascading remainders for display
            $remaining1 = $inc->amount - $com;
            $remaining2 = $remaining1 - $isp;
            $remaining3 = $remaining2 - $tool; // Should equal netBalance

            $incomeBreakdowns[] = (object) [
                'id' => $inc->id,
                'date' => $inc->transaction_date,
                'coordinator_name' => $inc->coordinator->name ?? '-',
                'gross_amount' => $inc->amount,
                'commission' => $com,
                'isp_share' => $isp,
                'tool_fund' => $tool,
                'manager_income' => $managerIncome,
                'net_balance' => $netBalance,
                'remaining_1' => $remaining1,
                'remaining_2' => $remaining2,
                'remaining_3' => $remaining3,
                'cash_fund' => $cash,
                'investor_share' => $shares,
                'investor_names' => $investorNames
            ];
        }

        $coordRate = Setting::getValue('commission_coordinator_percent', 15);
        $ispRate = Setting::getValue('commission_isp_percent', 25);
        $toolRate = Setting::getValue('commission_tool_percent', 20);
        $investorCashRate = Setting::getValue('investor_cash_percent', 5);
        $managerRate = $coordRate + $ispRate + $toolRate;

        // Convert array to Collection for view methods like sum()
        $incomeBreakdowns = collect($incomeBreakdowns);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('finance.income_breakdown_pdf', compact('incomeBreakdowns', 'coordRate', 'ispRate', 'toolRate', 'investorCashRate', 'managerRate'));
        $pdf->setPaper('a4', 'landscape');
        
        return $pdf->stream('income_breakdown.pdf', ['Attachment' => false]);
    }

    public function downloadInvestorSharePdf(Request $request)
    {
        if (!Auth::user()->hasRole('admin') && !Auth::user()->hasRole('finance')) {
            abort(403, 'Unauthorized action.');
        }

        $coordinatorSummaries = [];
        $coordinators = \App\Models\Coordinator::all();

        foreach ($coordinators as $coordinator) {
            $grossRevenue = Transaction::where('coordinator_id', $coordinator->id)
                ->where('type', 'income')
                ->whereIn('category', ['Member Income', 'Voucher Income'])
                ->sum('amount');

            $commission = Transaction::where('coordinator_id', $coordinator->id)
                ->where('category', 'Coordinator Commission')
                ->sum('amount');

            $ispShare = Transaction::where('coordinator_id', $coordinator->id)
                ->where('category', 'ISP Payment')
                ->sum('amount');

            $toolFund = Transaction::where('coordinator_id', $coordinator->id)
                ->where('category', 'Tool Fund')
                ->sum('amount');

            $investorShareByCoordinator = Transaction::where('coordinator_id', $coordinator->id)
                ->where('category', 'Investor Profit Share')
                ->sum('amount');

            $investorCashByCoordinator = Transaction::where('coordinator_id', $coordinator->id)
                ->where('category', 'Investor Cash Fund')
                ->sum('amount');

            $deposited = Transaction::where('coordinator_id', $coordinator->id)
                ->where('category', 'Deposit to Company')
                ->sum('amount');

            // Calculate Total Expenses (including Inventory)
            $totalExpenses = Transaction::where('coordinator_id', $coordinator->id)
                ->where('type', 'expense')
                ->whereNotIn('category', [
                    'Coordinator Commission',
                    'ISP Payment',
                    'Tool Fund',
                    'Investor Profit Share',
                    'Investor Cash Fund',
                    'Pembayaran ISP',
                    'Pembelian Alat',
                    'Deposit to Company'
                ])
                ->sum('amount');

            // Calculate Inventory Expenses to Exclude
            $inventoryExpenses = Transaction::where('coordinator_id', $coordinator->id)
                ->where(function($q) {
                    $q->where('category', 'Ambil Barang')
                      ->orWhere(function($sub) {
                          $sub->where('category', 'Pengeluaran Pengurus')
                              ->where('reference_number', 'like', 'INV-OUT-%');
                      });
                })
                ->sum('amount');
            
            $expenses = $totalExpenses - $inventoryExpenses;
            
            $netBalance = $grossRevenue - $commission - $expenses - $deposited;

            $coordinatorSummaries[] = (object) [
                'id' => $coordinator->id,
                'name' => $coordinator->name,
                'gross_revenue' => $grossRevenue,
                'commission' => $commission,
                'isp_share' => $ispShare,
                'tools_cost' => $toolFund,
                'investor_share' => $investorShareByCoordinator,
                'investor_cash' => $investorCashByCoordinator,
                'expenses' => $expenses,
                'deposited' => $deposited,
                'net_balance' => $netBalance,
            ];
        }

        $investorDetailsByCoordinator = [];
        
        $investorRows = DB::table('transactions')
            ->join('investors', 'transactions.investor_id', '=', 'investors.id')
            ->select(
                'transactions.coordinator_id',
                'transactions.investor_id',
                'investors.name as investor_name',
                DB::raw('SUM(CASE WHEN transactions.category = "Investor Profit Share" THEN transactions.amount ELSE 0 END) as profit_share'),
                DB::raw('SUM(CASE WHEN transactions.category = "Investor Cash Fund" THEN transactions.amount ELSE 0 END) as cash_fund')
            )
            ->whereNotNull('transactions.coordinator_id')
            ->whereNotNull('transactions.investor_id');

        if ($request->has('month')) {
            $investorRows->whereMonth('transactions.transaction_date', date('m', strtotime($request->month)))
                ->whereYear('transactions.transaction_date', date('Y', strtotime($request->month)));
        }

        $investorRows = $investorRows
            ->groupBy('transactions.coordinator_id', 'transactions.investor_id', 'investors.name')
            ->get();

        foreach ($investorRows as $row) {
            $investorDetailsByCoordinator[$row->coordinator_id][] = [
                'investor_id' => $row->investor_id,
                'investor_name' => $row->investor_name,
                'profit_share' => $row->profit_share,
                'cash_fund' => $row->cash_fund,
            ];
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('finance.investor_share_pdf', compact('coordinatorSummaries', 'investorDetailsByCoordinator'));
        
        return $pdf->stream('investor_share_per_coordinator.pdf', ['Attachment' => false]);
    }

    public function investorReport(Request $request)
    {
        if (!Auth::user()->hasRole('admin') && !Auth::user()->hasRole('finance') && !Auth::user()->hasRole('investor')) {
            abort(403, 'Unauthorized action.');
        }

        // Filter by month if provided, default to current year-month
        $selectedMonth = $request->input('month', now()->format('Y-m'));
        $coordinatorId = $request->input('coordinator_id');
        $date = \Carbon\Carbon::parse($selectedMonth);
        
        $query = Transaction::query()
            ->whereYear('transaction_date', $date->year)
            ->whereMonth('transaction_date', $date->month);

        if ($coordinatorId) {
            $query->where('coordinator_id', $coordinatorId);
        }

        $transactions = $query->get();
        $coordinators = Coordinator::all();

        // 1. Gross Revenue (Pendapatan Pengurus)
        $grossRevenue = $transactions->where('type', 'income')
            ->whereIn('category', ['Member Income', 'Voucher Income'])
            ->sum('amount');

        // 2. Fixed Deductions (Cascade Calculation)
        $coordRate = 15;
        $ispRate = 25;
        $mgmtRate = 20;

        // Cascade: 
        // 1. Gross - 15% Comm = Rem1
        // 2. Rem1 - 25% ISP = Rem2
        // 3. Rem2 - 20% Mgmt = Rem3
        
        $commission = $grossRevenue * ($coordRate / 100);
        $rem1 = $grossRevenue - $commission;
        
        $ispShare = $rem1 * ($ispRate / 100);
        $rem2 = $rem1 - $ispShare;
        
        $toolFund = $rem2 * ($mgmtRate / 100);
        $rem3 = $rem2 - $toolFund;

        // 3. Operational Expenses (Server, Ambil Barang, Cash Expenses)
        // User: "sisa dikurangi pengeluaran server didalam nya ambil matrial di kantor dan pengeluaran pengurus cash"
        
        // Server Expenses
        $serverExpenses = $transactions->where('type', 'expense')->where('category', 'Operational')->sum('amount');
        
        // Inventory Expenses (Ambil Barang + INV-OUT)
        $inventoryExpenses = $transactions->filter(function ($t) {
            return $t->category === 'Ambil Barang' || 
                   ($t->category === 'Pengeluaran Pengurus' && str_starts_with($t->reference_number ?? '', 'INV-OUT-'));
        })->sum('amount');

        // Cash Expenses (Excluding Allocations and Inventory)
        $cashExpensesRaw = $transactions->where('type', 'expense')
            ->whereNotIn('category', [
                'Coordinator Commission', 'ISP Payment', 'Tool Fund', 'Investor Profit Share', 'Investor Cash Fund',
                'Deposit to Company', 'Pembayaran ISP', 'Pembelian Alat', 'Operational',
                'Ambil Barang'
            ])
            ->filter(function ($t) {
                return !($t->category === 'Pengeluaran Pengurus' && str_starts_with($t->reference_number ?? '', 'INV-OUT-'));
            })
            ->sum('amount');

        $operationalExpenses = $serverExpenses + $inventoryExpenses + $cashExpensesRaw;

        // 4. Net Profit Before Cash Fund
        $netBeforeCashFund = $rem3 - $operationalExpenses;

        // 5. Investor Cash Fund (5%)
        $investorCashFund = $netBeforeCashFund * 0.05;

        // 6. Net Profit for Investors
        $netProfit = $netBeforeCashFund - $investorCashFund;

        // 7. Investor Split
        $investors = collect();
        $coordinatorName = null;
        if ($coordinatorId) {
            $coordinator = Coordinator::find($coordinatorId);
            $coordinatorName = $coordinator->name ?? null;
            // Get investors for this coordinator, excluding the coordinator themselves if listed
            $investors = \App\Models\Investor::where('coordinator_id', $coordinatorId)
                ->where('name', '!=', $coordinatorName)
                ->get();
        } else {
            // Get all investors
            $investors = \App\Models\Investor::all();
        }
        
        $investorCount = $investors->count();
        if ($investorCount == 0) $investorCount = 1;

        $profitPerInvestor = $netProfit / $investorCount;

        return view('finance.investor_report', compact(
            'selectedMonth', 
            'grossRevenue', 
            'commission', 
            'ispShare', 
            'toolFund', 
            'operationalExpenses', 
            'investorCashFund', 
            'netProfit', 
            'investorCount', 
            'profitPerInvestor',
            'coordinators',
            'coordinatorId',
            'investors'
        ));
    }

    public function downloadInvestorReportPdf(Request $request)
    {
        if (!Auth::user()->hasRole('admin') && !Auth::user()->hasRole('finance') && !Auth::user()->hasRole('investor')) {
            abort(403, 'Unauthorized action.');
        }

        $selectedMonth = $request->input('month', now()->format('Y-m'));
        $coordinatorId = $request->input('coordinator_id');
        $date = \Carbon\Carbon::parse($selectedMonth);
        
        $query = Transaction::query()
            ->whereYear('transaction_date', $date->year)
            ->whereMonth('transaction_date', $date->month);

        if ($coordinatorId) {
            $query->where('coordinator_id', $coordinatorId);
        }

        $transactions = $query->get();

        $grossRevenue = $transactions->where('type', 'income')
            ->whereIn('category', ['Member Income', 'Voucher Income'])
            ->sum('amount');

        $coordRate = 15;
        $ispRate = 25;
        $mgmtRate = 20;

        $commission = $grossRevenue * ($coordRate / 100);
        $rem1 = $grossRevenue - $commission;
        
        $ispShare = $rem1 * ($ispRate / 100);
        $rem2 = $rem1 - $ispShare;
        
        $toolFund = $rem2 * ($mgmtRate / 100);
        $rem3 = $rem2 - $toolFund;

        $serverExpenses = $transactions->where('type', 'expense')->where('category', 'Operational')->sum('amount');
        
        $inventoryExpenses = $transactions->filter(function ($t) {
            return $t->category === 'Ambil Barang' || 
                   ($t->category === 'Pengeluaran Pengurus' && str_starts_with($t->reference_number ?? '', 'INV-OUT-'));
        })->sum('amount');

        $cashExpensesRaw = $transactions->where('type', 'expense')
            ->whereNotIn('category', [
                'Coordinator Commission', 'ISP Payment', 'Tool Fund', 'Investor Profit Share', 'Investor Cash Fund',
                'Deposit to Company', 'Pembayaran ISP', 'Pembelian Alat', 'Operational',
                'Ambil Barang'
            ])
            ->filter(function ($t) {
                return !($t->category === 'Pengeluaran Pengurus' && str_starts_with($t->reference_number ?? '', 'INV-OUT-'));
            })
            ->sum('amount');

        $operationalExpenses = $serverExpenses + $inventoryExpenses + $cashExpensesRaw;

        $netBeforeCashFund = $rem3 - $operationalExpenses;

        $investorCashFund = $netBeforeCashFund * 0.05;

        $netProfit = $netBeforeCashFund - $investorCashFund;

        $investors = collect();
        $coordinatorName = null;
        if ($coordinatorId) {
            $coordinator = Coordinator::find($coordinatorId);
            $coordinatorName = $coordinator->name ?? null;
            $investors = \App\Models\Investor::where('coordinator_id', $coordinatorId)
                ->where('name', '!=', $coordinatorName)
                ->get();
        } else {
             $investors = \App\Models\Investor::all();
        }
        
        $investorCount = $investors->count();
        if ($investorCount == 0) $investorCount = 1;
        
        $profitPerInvestor = $netProfit / $investorCount;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('finance.investor_report_pdf', compact(
            'selectedMonth', 
            'grossRevenue', 
            'commission', 
            'ispShare', 
            'toolFund', 
            'operationalExpenses', 
            'investorCashFund', 
            'netProfit', 
            'investorCount', 
            'profitPerInvestor',
            'coordinatorName',
            'investors',
            'coordinatorId'
        ));
        
        return $pdf->stream('investor_report.pdf', ['Attachment' => false]);
    }

    public function developerReport(Request $request)
    {
        if (!Auth::user()->hasRole('admin') && !Auth::user()->hasRole('finance')) {
            abort(403, 'Unauthorized action.');
        }

        $selectedMonth = $request->input('month', now()->format('Y-m'));
        $date = \Carbon\Carbon::parse($selectedMonth);
        
        $query = Transaction::query()
            ->whereYear('transaction_date', $date->year)
            ->whereMonth('transaction_date', $date->month);

        $transactions = $query->get();

        // Calculate Gross Revenue for Cascade Logic
        $grossRevenue = $transactions->where('type', 'income')
            ->whereIn('category', ['Member Income', 'Voucher Income'])
            ->sum('amount');

        // Cascade Logic (Same as Investor Report)
        $coordRate = 15;
        $ispRate = 25;
        $mgmtRate = 20;

        $commission = $grossRevenue * ($coordRate / 100);
        $rem1 = $grossRevenue - $commission;
        
        $calculatedIspShare = $rem1 * ($ispRate / 100);
        $rem2 = $rem1 - $calculatedIspShare;
        
        $calculatedMgmtShare = $rem2 * ($mgmtRate / 100);

        // Income
        // 1. ISP Share (Calculated via Cascade)
        $ispIncome = $calculatedIspShare;
        
        // 2. Management Fee (Calculated via Cascade)
        $mgmtIncome = $calculatedMgmtShare;
        
        // 3. Material Sales
        $materialIncome = $transactions->where('category', 'Penjualan Material')->sum('amount');
        
        // 4. Wash Service
        $washIncome = $transactions->where('category', 'Jasa Cuci')->sum('amount');
        
        // 5. ATK Sales
        $atkIncome = $transactions->where('category', 'Penjualan ATK')->sum('amount');

        $totalIncome = $ispIncome + $mgmtIncome + $materialIncome + $washIncome + $atkIncome;

        // Expenses
        // 1. Internet (ISP) Payment
        $ispExpense = $transactions->where('category', 'Pembayaran ISP')->sum('amount');
        
        // 2. Salary (Gaji)
        $salaryExpense = $transactions->where('category', 'Gaji')->sum('amount');
        
        // 3. Material Purchase
        $materialExpense = $transactions->where('category', 'Pembelian Material')->sum('amount');
        
        // 4. Tool Purchase
        $toolExpense = $transactions->where('category', 'Pembelian Alat')->sum('amount');
        
        // 5. Wash Expenses (Salary & Others)
        $washExpense = $transactions->where('category', 'Pengeluaran Cuci')->sum('amount');

        $totalExpenses = $ispExpense + $salaryExpense + $materialExpense + $toolExpense + $washExpense;

        $netProfit = $totalIncome - $totalExpenses;

        return view('finance.developer_report', compact(
            'selectedMonth',
            'ispIncome',
            'mgmtIncome',
            'materialIncome',
            'washIncome',
            'atkIncome',
            'totalIncome',
            'ispExpense',
            'salaryExpense',
            'materialExpense',
            'toolExpense',
            'washExpense',
            'totalExpenses',
            'netProfit'
        ));
    }

    public function coordinatorDetail(Coordinator $coordinator, Request $request)
    {
        if (!Auth::user()->hasRole('admin') && !Auth::user()->hasRole('finance')) {
            if ($coordinator->user_id !== Auth::id()) {
                abort(403, 'Unauthorized action.');
            }
        }

        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));

        // 1. Ambil Transaksi Keuangan
        $query = Transaction::where('coordinator_id', $coordinator->id)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc');

        $transactions = $query->get();

        // 2. Hitung Summary Keuangan
        $grossRevenue = $transactions->where('type', 'income')
            ->whereIn('category', ['Member Income', 'Voucher Income'])
            ->sum('amount');

        $commission = $transactions->where('category', 'Coordinator Commission')->sum('amount');
        $deposited = $transactions->where('category', 'Deposit to Company')->sum('amount');
        
        // PERBAIKAN: Hitung ISP & Tool Fund agar tidak undefined
        $ispShare = $transactions->where('category', 'ISP Payment')->sum('amount');
        $toolFund = $transactions->where('category', 'Tool Fund')->sum('amount');

        // 3. Hitung TOTAL Expenses (Manual)
        $totalExpensesRaw = $transactions->where('type', 'expense')
            ->whereNotIn('category', [
                'Coordinator Commission',
                'ISP Payment',
                'Tool Fund',
                'Investor Profit Share',
                'Investor Cash Fund',
                'Pembayaran ISP',
                'Pembelian Alat',
                'Deposit to Company'
            ])
            ->sum('amount');

        // 4. PISAHKAN: Biaya Barang (Ambil Barang)
        $inventoryExpenses = $transactions->filter(function ($t) {
            return $t->category === 'Ambil Barang' || 
                   ($t->category === 'Pengeluaran Pengurus' && str_starts_with($t->reference_number ?? '', 'INV-OUT-'));
        })->sum('amount');

        // 5. PISAHKAN: Biaya Tunai (Ops & Beli Luar)
        $cashExpenses = $totalExpensesRaw - $inventoryExpenses;

        // 6. Ambil Detail Biaya Operasional (Untuk Tabel View)
        $operationalExpenses = Transaction::where('coordinator_id', $coordinator->id)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('type', 'expense')
            ->whereNotIn('category', [
                'Coordinator Commission',
                'ISP Payment',
                'Tool Fund',
                'Investor Profit Share',
                'Investor Cash Fund',
                'Pembayaran ISP',
                'Pembelian Alat',
                'Deposit to Company',
                'Ambil Barang' // Exclude dari list operasional tunai
            ])
            ->orderBy('transaction_date', 'desc')
            ->get();

        // 7. Ambil Detail Pengambilan Barang (Untuk Tabel View)
        $inventoryItems = \App\Models\InventoryTransaction::with('item')
            ->where('coordinator_id', $coordinator->id)
            ->where('type', 'out')
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->get();

        // Hitung total nilai barang keluar
        $inventoryUsageValue = $inventoryItems->sum(function($t) {
            return $t->quantity * ($t->item->price ?? 0);
        });

        // 8. Perhitungan Akhir
        $netIncome = $grossRevenue - $cashExpenses - $commission;
        $netBalance = $netIncome; 

        $toolRate = Setting::getValue('commission_tool_percent', 20);
        $investorCash = $transactions->where('category', 'Investor Cash Fund')->sum('amount');

        // 9. Perbaikan untuk compact()
        // Kita buat variabel baru agar compact() hanya menerima string
        $expenses = $cashExpenses; 
        $totalExpenses = $cashExpenses; 

        return view('finance.coordinator_detail', compact(
            'coordinator',
            'transactions',
            'grossRevenue',
            'commission',
            'ispShare',
            'toolFund',
            'investorCash',
            'totalExpenses', 
            'expenses' ,
            'cashExpenses',
            'inventoryExpenses',
            'inventoryUsageValue',
            'operationalExpenses',
            'inventoryItems',
            'netIncome',
            'netBalance',
            'startDate',
            'endDate',
            'toolRate',
            'deposited'
        ));
    }

      public function downloadCoordinatorPdf(Coordinator $coordinator, Request $request)
    {
        if (!Auth::user()->hasRole('admin') && !Auth::user()->hasRole('finance')) {
            if ($coordinator->user_id !== Auth::id()) {
                abort(403, 'Unauthorized action.');
            }
        }

        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));

        $query = Transaction::where('coordinator_id', $coordinator->id)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->orderBy('transaction_date', 'asc'); 

        $transactions = $query->get();

        $memberIncome = $transactions->where('type', 'income')
            ->where('category', 'Member Income')
            ->sum('amount');

        $voucherIncome = $transactions->where('type', 'income')
            ->where('category', 'Voucher Income')
            ->sum('amount');

        $grossRevenue = $memberIncome + $voucherIncome;

        $commission = $transactions->where('category', 'Coordinator Commission')->sum('amount');
        $deposited = $transactions->where('category', 'Deposit to Company')->sum('amount');

        // --- UPDATE LOGIC HERE ---
        $totalExpenses = $transactions->where('type', 'expense')
            ->whereNotIn('category', [
                'Coordinator Commission',
                'ISP Payment',
                'Tool Fund',
                'Investor Profit Share',
                'Investor Cash Fund',
                'Pembayaran ISP',
                'Pembelian Alat',
                'Deposit to Company'
            ])
            ->sum('amount');

        // Hitung Inventory & Cash
        $inventoryExpenses = $transactions->filter(function ($t) {
            return $t->category === 'Ambil Barang' || 
                   ($t->category === 'Pengeluaran Pengurus' && str_starts_with($t->reference_number ?? '', 'INV-OUT-'));
        })->sum('amount');
        $cashExpenses = $totalExpenses - $inventoryExpenses;
        // -----------------------

        $netBalance = $grossRevenue - $commission - $cashExpenses - $deposited;

        $toolRate = Setting::getValue('commission_tool_percent', 20);

        $investorDetails = DB::table('transactions')
            ->join('investors', 'transactions.investor_id', '=', 'investors.id')
            ->select(
                'transactions.investor_id',
                'investors.name as investor_name',
                DB::raw('SUM(CASE WHEN transactions.category = "Investor Profit Share" THEN transactions.amount ELSE 0 END) as profit_share'),
                DB::raw('SUM(CASE WHEN transactions.category = "Investor Cash Fund" THEN transactions.amount ELSE 0 END) as cash_fund')
            )
            ->where('transactions.coordinator_id', $coordinator->id)
            ->whereBetween('transactions.transaction_date', [$startDate, $endDate])
            ->whereNotNull('transactions.investor_id')
            ->groupBy('transactions.investor_id', 'investors.name')
            ->get();

        $expenses = $cashExpenses;
        $managerName = Auth::user()->name;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('finance.coordinator_pdf', compact(
            'coordinator',
            'transactions',
            'memberIncome',
            'voucherIncome',
            'grossRevenue',
            'commission',
            'expenses', // Kirim cashExpenses agar view PDF menampilkan angka yang benar
            'netBalance',
            'startDate',
            'endDate',
            'toolRate',
            'investorDetails',
            'deposited',
            'managerName'
        ));

        $pdf->setPaper('a4', 'landscape');

        return $pdf->stream('Laporan_Keuangan_' . str_replace(' ', '_', $coordinator->name) . '_' . $startDate . '_sd_' . $endDate . '.pdf', ['Attachment' => false]);
    }

    public function update(Request $request, Transaction $transaction)
    {
        if (!Auth::user()->hasRole('admin') && !Auth::user()->hasRole('finance')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'type' => 'required|in:income,expense,transfer',
            'category' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'transaction_date' => 'required|date',
            'description' => 'nullable|string',
            'coordinator_id' => 'nullable|exists:coordinators,id',
            'investor_id' => 'nullable|exists:investors,id',
            'reference_number' => 'nullable|string',
        ]);

        DB::transaction(function () use ($transaction, $validated) {
            $transaction->update($validated);

            // Sync Related Transactions (Commission, ISP, Tools, Investor)
            $commission = Transaction::where('reference_number', 'COM-' . $transaction->id)->first();
            $ispPayment = Transaction::where('reference_number', 'ISP-' . $transaction->id)->first();
            $toolFund = Transaction::where('reference_number', 'TOOL-' . $transaction->id)->first();
            $investorSharesQuery = Transaction::where('reference_number', 'INV-' . $transaction->id)
                ->where('category', 'Investor Profit Share');
            $hasInvestorShares = $investorSharesQuery->exists();
            $investorCash = Transaction::where('reference_number', 'INV-CASH-' . $transaction->id)->first();

            $isEligible = $validated['type'] === 'income' && 
                          !empty($validated['coordinator_id']) && 
                          in_array($validated['category'], ['Member Income', 'Voucher Income']);

            if ($commission || $ispPayment || $toolFund || $hasInvestorShares || $investorCash) {
                if (!$isEligible) {
                        // Delete all if no longer eligible
                        if ($commission) $commission->delete();
                        if ($ispPayment) $ispPayment->delete();
                        if ($toolFund) $toolFund->delete();
                        $investorSharesQuery->delete();
                        Transaction::where('reference_number', 'INV-CASH-' . $transaction->id)->delete();
                    } else {
                    // Update amounts
                    $coordRate = Setting::getValue('commission_coordinator_percent', 15);
                    $ispRate = Setting::getValue('commission_isp_percent', 25);
                    $toolRate = Setting::getValue('commission_tool_percent', 15);

                    // Cascade Calculation
                    $gross = $validated['amount'];
                    $coordAmount = $gross * ($coordRate / 100);
                    $rem1 = $gross - $coordAmount;
                    $ispAmount = $rem1 * ($ispRate / 100);
                    $rem2 = $rem1 - $ispAmount;
                    $toolAmount = $rem2 * ($toolRate / 100);
                    $rem3 = $rem2 - $toolAmount;

                    $investorCashPercent = Setting::getValue('investor_cash_percent', 5);
                    $investorCashAmount = $rem3 * ($investorCashPercent / 100);
                    $rem4 = $rem3 - $investorCashAmount;

                    // Investor receives 100% of remaining after cash fund
                    $investorDistributableAmount = $rem4;

                    // Update Coordinator Commission
                    if ($commission) {
                        $commission->update([
                            'amount' => $coordAmount,
                            'transaction_date' => $validated['transaction_date'],
                            'coordinator_id' => $validated['coordinator_id'],
                            'description' => $coordRate . '% share for coordinator from transaction #' . $transaction->id,
                        ]);
                    }

                    // Update ISP Payment
                    if ($ispPayment) {
                        $ispPayment->update([
                            'amount' => $ispAmount,
                            'transaction_date' => $validated['transaction_date'],
                            'coordinator_id' => $validated['coordinator_id'],
                            'description' => $ispRate . '% ISP share from transaction #' . $transaction->id,
                        ]);
                    } else {
                        // Create if missing (migration)
                         Transaction::create([
                            'user_id' => Auth::id(),
                            'type' => 'expense',
                            'category' => 'ISP Payment',
                            'amount' => $ispAmount,
                            'transaction_date' => $validated['transaction_date'],
                            'description' => $ispRate . '% ISP share from transaction #' . $transaction->id,
                            'coordinator_id' => $validated['coordinator_id'],
                            'reference_number' => 'ISP-' . $transaction->id,
                        ]);
                    }

                    // Update Tool Fund
                    if ($toolFund) {
                        $toolFund->update([
                            'amount' => $toolAmount,
                            'transaction_date' => $validated['transaction_date'],
                            'coordinator_id' => $validated['coordinator_id'],
                            'description' => $toolRate . '% Tool fund from transaction #' . $transaction->id,
                        ]);
                    } else {
                        // Create if missing (migration)
                        Transaction::create([
                            'user_id' => Auth::id(),
                            'type' => 'expense',
                            'category' => 'Tool Fund',
                            'amount' => $toolAmount,
                            'transaction_date' => $validated['transaction_date'],
                            'description' => $toolRate . '% Tool fund from transaction #' . $transaction->id,
                            'coordinator_id' => $validated['coordinator_id'],
                            'reference_number' => 'TOOL-' . $transaction->id,
                        ]);
                    }

                    // Investor Shares
                    $investorSharesQuery->delete();
                    Transaction::where('reference_number', 'INV-CASH-' . $transaction->id)->delete();

                    $singleInvestorId = $validated['investor_id'] ?? null;
                    if ($singleInvestorId) {
                        if ($investorDistributableAmount > 0) {
                            Transaction::create([
                                'user_id' => Auth::id(),
                                'type' => 'expense',
                                'category' => 'Investor Profit Share',
                                'amount' => $investorDistributableAmount,
                                'transaction_date' => $validated['transaction_date'],
                                'description' => '100% Profit Share from transaction #' . $transaction->id,
                                'coordinator_id' => $validated['coordinator_id'],
                                'investor_id' => $singleInvestorId,
                                'reference_number' => 'INV-' . $transaction->id,
                            ]);
                        }
                        if ($investorCashAmount > 0) {
                            Transaction::create([
                                'user_id' => Auth::id(),
                                'type' => 'expense',
                                'category' => 'Investor Cash Fund',
                                'amount' => $investorCashAmount,
                                'transaction_date' => $validated['transaction_date'],
                                'description' => $investorCashPercent . '% Uang Kas Pengurus from transaction #' . $transaction->id,
                                'coordinator_id' => $validated['coordinator_id'],
                                'reference_number' => 'INV-CASH-' . $transaction->id,
                            ]);
                        }
                    } else {
                        $coordinatorInvestors = \App\Models\Investor::where('coordinator_id', $validated['coordinator_id'])->get();
                        if ($coordinatorInvestors->count() === 1 && $investorDistributableAmount > 0) {
                            $investor = $coordinatorInvestors->first();
                            Transaction::create([
                                'user_id' => Auth::id(),
                                'type' => 'expense',
                                'category' => 'Investor Profit Share',
                                'amount' => $investorDistributableAmount,
                                'transaction_date' => $validated['transaction_date'],
                                'description' => '100% Profit Share from transaction #' . $transaction->id,
                                'coordinator_id' => $validated['coordinator_id'],
                                'investor_id' => $investor->id,
                                'reference_number' => 'INV-' . $transaction->id,
                            ]);
                            if ($investorCashAmount > 0) {
                                Transaction::create([
                                    'user_id' => Auth::id(),
                                    'type' => 'expense',
                                    'category' => 'Investor Cash Fund',
                                    'amount' => $investorCashAmount,
                                    'transaction_date' => $validated['transaction_date'],
                                    'description' => $investorCashPercent . '% Uang Kas Pengurus from transaction #' . $transaction->id,
                                    'coordinator_id' => $validated['coordinator_id'],
                                    'reference_number' => 'INV-CASH-' . $transaction->id,
                                ]);
                            }
                        } elseif ($coordinatorInvestors->count() > 1 && $investorDistributableAmount > 0) {
                            $count = $coordinatorInvestors->count();
                            $baseShare = round($investorDistributableAmount / $count, 2);
                            $allocated = 0;
                            foreach ($coordinatorInvestors as $index => $investor) {
                                if ($index === $count - 1) {
                                    $amount = $investorDistributableAmount - $allocated;
                                } else {
                                    $amount = $baseShare;
                                    $allocated += $amount;
                                }

                                Transaction::create([
                                    'user_id' => Auth::id(),
                                    'type' => 'expense',
                                    'category' => 'Investor Profit Share',
                                    'amount' => $amount,
                                    'transaction_date' => $validated['transaction_date'],
                                    'description' => '100% Profit Share from transaction #' . $transaction->id,
                                    'coordinator_id' => $validated['coordinator_id'],
                                    'investor_id' => $investor->id,
                                    'reference_number' => 'INV-' . $transaction->id,
                                ]);
                            }
                            if ($investorCashAmount > 0) {
                                Transaction::create([
                                    'user_id' => Auth::id(),
                                    'type' => 'expense',
                                    'category' => 'Investor Cash Fund',
                                    'amount' => $investorCashAmount,
                                    'transaction_date' => $validated['transaction_date'],
                                    'description' => $investorCashPercent . '% Uang Kas Pengurus from transaction #' . $transaction->id,
                                    'coordinator_id' => $validated['coordinator_id'],
                                    'reference_number' => 'INV-CASH-' . $transaction->id,
                                ]);
                            }
                        }
                    }
                }
            } else {
                // If they didn't exist but now should
                if ($isEligible) {
                    $coordRate = Setting::getValue('commission_coordinator_percent', 15);
                    $ispRate = Setting::getValue('commission_isp_percent', 25);
                    $toolRate = Setting::getValue('commission_tool_percent', 15);

                    // Cascade Calculation
                    $gross = $validated['amount'];
                    $coordAmount = $gross * ($coordRate / 100);
                    $rem1 = $gross - $coordAmount;
                    $ispAmount = $rem1 * ($ispRate / 100);
                    $rem2 = $rem1 - $ispAmount;
                    $toolAmount = $rem2 * ($toolRate / 100);
                    $rem3 = $rem2 - $toolAmount;
                    
                    $investorCashPercent = Setting::getValue('investor_cash_percent', 5);
                    $investorCashAmount = $rem3 * ($investorCashPercent / 100);
                    $rem4 = $rem3 - $investorCashAmount;
                    $investorDistributableAmount = $rem4;

                    // Create Coordinator Commission
                    Transaction::create([
                        'user_id' => Auth::id(),
                        'type' => 'expense',
                        'category' => 'Coordinator Commission',
                        'amount' => $coordAmount,
                        'transaction_date' => $validated['transaction_date'],
                        'description' => $coordRate . '% share for coordinator from transaction #' . $transaction->id,
                        'coordinator_id' => $validated['coordinator_id'],
                        'reference_number' => 'COM-' . $transaction->id,
                    ]);

                    // Create ISP Payment
                    Transaction::create([
                        'user_id' => Auth::id(),
                        'type' => 'expense',
                        'category' => 'ISP Payment',
                        'amount' => $ispAmount,
                        'transaction_date' => $validated['transaction_date'],
                        'description' => $ispRate . '% ISP share from transaction #' . $transaction->id,
                        'coordinator_id' => $validated['coordinator_id'],
                        'reference_number' => 'ISP-' . $transaction->id,
                    ]);

                    // Create Tool Fund
                    Transaction::create([
                        'user_id' => Auth::id(),
                        'type' => 'expense',
                        'category' => 'Tool Fund',
                        'amount' => $toolAmount,
                        'transaction_date' => $validated['transaction_date'],
                        'description' => $toolRate . '% Tool fund from transaction #' . $transaction->id,
                        'coordinator_id' => $validated['coordinator_id'],
                        'reference_number' => 'TOOL-' . $transaction->id,
                    ]);

                    $singleInvestorId = $validated['investor_id'] ?? null;
                    if ($singleInvestorId) {
                        if ($investorDistributableAmount > 0) {
                            Transaction::create([
                                'user_id' => Auth::id(),
                                'type' => 'expense',
                                'category' => 'Investor Profit Share',
                                'amount' => $investorDistributableAmount,
                                'transaction_date' => $validated['transaction_date'],
                                'description' => '100% Profit Share from transaction #' . $transaction->id,
                                'coordinator_id' => $validated['coordinator_id'],
                                'investor_id' => $singleInvestorId,
                                'reference_number' => 'INV-' . $transaction->id,
                            ]);
                        }
                        if ($investorCashAmount > 0) {
                            Transaction::create([
                                'user_id' => Auth::id(),
                                'type' => 'expense',
                                'category' => 'Investor Cash Fund',
                                'amount' => $investorCashAmount,
                                'transaction_date' => $validated['transaction_date'],
                                'description' => $investorCashPercent . '% Uang Kas Pengurus from transaction #' . $transaction->id,
                                'coordinator_id' => $validated['coordinator_id'],
                                'reference_number' => 'INV-CASH-' . $transaction->id,
                            ]);
                        }
                    } else {
                        $coordinatorInvestors = \App\Models\Investor::where('coordinator_id', $validated['coordinator_id'])->get();
                        if ($coordinatorInvestors->count() === 1 && $investorDistributableAmount > 0) {
                            $investor = $coordinatorInvestors->first();
                            Transaction::create([
                                'user_id' => Auth::id(),
                                'type' => 'expense',
                                'category' => 'Investor Profit Share',
                                'amount' => $investorDistributableAmount,
                                'transaction_date' => $validated['transaction_date'],
                                'description' => '100% Profit Share from transaction #' . $transaction->id,
                                'coordinator_id' => $validated['coordinator_id'],
                                'investor_id' => $investor->id,
                                'reference_number' => 'INV-' . $transaction->id,
                            ]);
                            if ($investorCashAmount > 0) {
                                Transaction::create([
                                    'user_id' => Auth::id(),
                                    'type' => 'expense',
                                    'category' => 'Investor Cash Fund',
                                    'amount' => $investorCashAmount,
                                    'transaction_date' => $validated['transaction_date'],
                                    'description' => $investorCashPercent . '% Uang Kas Pengurus from transaction #' . $transaction->id,
                                    'coordinator_id' => $validated['coordinator_id'],
                                    'reference_number' => 'INV-CASH-' . $transaction->id,
                                ]);
                            }
                        } elseif ($coordinatorInvestors->count() > 1 && $investorDistributableAmount > 0) {
                            $count = $coordinatorInvestors->count();
                            $baseShare = round($investorDistributableAmount / $count, 2);
                            $allocated = 0;
                            foreach ($coordinatorInvestors as $index => $investor) {
                                if ($index === $count - 1) {
                                    $amount = $investorDistributableAmount - $allocated;
                                } else {
                                    $amount = $baseShare;
                                    $allocated += $amount;
                                }

                                Transaction::create([
                                    'user_id' => Auth::id(),
                                    'type' => 'expense',
                                    'category' => 'Investor Profit Share',
                                    'amount' => $amount,
                                    'transaction_date' => $validated['transaction_date'],
                                    'description' => '100% Profit Share from transaction #' . $transaction->id,
                                    'coordinator_id' => $validated['coordinator_id'],
                                    'investor_id' => $investor->id,
                                    'reference_number' => 'INV-' . $transaction->id,
                                ]);
                            }
                            if ($investorCashAmount > 0) {
                                Transaction::create([
                                    'user_id' => Auth::id(),
                                    'type' => 'expense',
                                    'category' => 'Investor Cash Fund',
                                    'amount' => $investorCashAmount,
                                    'transaction_date' => $validated['transaction_date'],
                                    'description' => $investorCashPercent . '% Uang Kas Pengurus from transaction #' . $transaction->id,
                                    'coordinator_id' => $validated['coordinator_id'],
                                    'reference_number' => 'INV-CASH-' . $transaction->id,
                                ]);
                            }
                        }
                    }
                }
            }
        });

        return back()->with('success', __('Transaction updated successfully.'));
    }

    public function destroy(Transaction $transaction)
    {
        if (!Auth::user()->hasRole('admin') && !Auth::user()->hasRole('finance')) {
            abort(403, 'Unauthorized action.');
        }

        DB::transaction(function () use ($transaction) {
            // Delete associated transactions
            Transaction::where('reference_number', 'COM-' . $transaction->id)->delete();
            Transaction::where('reference_number', 'ISP-' . $transaction->id)->delete();
            Transaction::where('reference_number', 'TOOL-' . $transaction->id)->delete();
            Transaction::where('reference_number', 'INV-' . $transaction->id)->delete();
            Transaction::where('reference_number', 'INV-CASH-' . $transaction->id)->delete();
            
            $transaction->delete();
        });

        return back()->with('success', __('Transaction deleted successfully.'));
    }

    public function bulkDestroy(Request $request)
    {
        if (!Auth::user()->hasRole('admin') && !Auth::user()->hasRole('finance')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:transactions,id',
        ]);

        DB::transaction(function () use ($request) {
            foreach ($request->ids as $id) {
                $transaction = Transaction::find($id);
                if ($transaction) {
                    // Delete associated transactions
                    Transaction::where('reference_number', 'COM-' . $transaction->id)->delete();
                    Transaction::where('reference_number', 'ISP-' . $transaction->id)->delete();
                    Transaction::where('reference_number', 'TOOL-' . $transaction->id)->delete();
                    Transaction::where('reference_number', 'INV-' . $transaction->id)->delete();
                    Transaction::where('reference_number', 'INV-CASH-' . $transaction->id)->delete();
                    
                    $transaction->delete();
                }
            }
        });

        return redirect()->route('finance.index')->with('success', __('Selected transactions deleted successfully.'));
    }

    public function index(Request $request)
    {
        $query = Transaction::with(['user', 'coordinator'])->latest('transaction_date');

        $userCoordinator = null;
        if (!Auth::user()->hasRole('admin') && !Auth::user()->hasRole('finance')) {
            $userCoordinator = Coordinator::where('user_id', Auth::id())->first();
            
            if ($userCoordinator) {
                $query->where('coordinator_id', $userCoordinator->id);
            } else {
                $query->where('user_id', Auth::id());
            }
        }

        if ($request->has('type') && $request->type) {
            $query->where('type', $request->type);
        }
        
        if ($request->has('category') && $request->category) {
            $query->where('category', $request->category);
        }

        if ($request->has('coordinator_id') && $request->coordinator_id) {
            $query->where('coordinator_id', $request->coordinator_id);
        }

        if ($request->has('month')) {
            $query->whereMonth('transaction_date', date('m', strtotime($request->month)))
                  ->whereYear('transaction_date', date('Y', strtotime($request->month)));
        }

        $transactions = $query->paginate(15);
        
        // Calculate Totals
        $totalsQuery = Transaction::query();
        if (!Auth::user()->hasRole('admin') && !Auth::user()->hasRole('finance')) {
            if ($userCoordinator) {
                $totalsQuery->where('coordinator_id', $userCoordinator->id);
            } else {
                $totalsQuery->where('user_id', Auth::id());
            }
        }

        if ($request->has('month')) {
            $totalsQuery->whereMonth('transaction_date', date('m', strtotime($request->month)))
                  ->whereYear('transaction_date', date('Y', strtotime($request->month)));
        }

        $totalIncome = (clone $totalsQuery)->where('type', 'income')->sum('amount');
        $totalExpense = (clone $totalsQuery)->where('type', 'expense')
            ->whereNotIn('category', ['Pembayaran ISP', 'Pembelian Alat', 'Ambil Barang'])
            ->sum('amount');
        $balance = $totalIncome - $totalExpense;
        
        $ispAllocations = (clone $totalsQuery)->where('category', 'ISP Payment')->sum('amount');
        $ispUsages = (clone $totalsQuery)->where('category', 'Pembayaran ISP')->sum('amount');
        $totalIspShare = $ispAllocations - $ispUsages;

        $toolAllocations = (clone $totalsQuery)->where('category', 'Tool Fund')->sum('amount');
        $toolUsages = (clone $totalsQuery)->where('category', 'Pembelian Alat')->sum('amount');
        $totalToolFund = $toolAllocations - $toolUsages;
        
        $coordShare = (clone $totalsQuery)->where('category', 'Coordinator Commission')->sum('amount');
        $investorShare = (clone $totalsQuery)->where('category', 'Investor Profit Share')->sum('amount');
        $investorCashShare = (clone $totalsQuery)->where('category', 'Investor Cash Fund')->sum('amount');
        $totalInvestorShare = $investorShare + $investorCashShare;
        
        $totalCompanyGrossShare = $totalIncome - $coordShare - $ispAllocations - $toolAllocations - $totalInvestorShare;

        $totalGeneralExpenses = (clone $totalsQuery)->where('type', 'expense')
            ->whereNotIn('category', [
                'Coordinator Commission',
                'ISP Payment',
                'Tool Fund',
                'Investor Profit Share',
                'Investor Cash Fund',
                'Pembayaran ISP',
                'Pembelian Alat',
                'Ambil Barang'
            ])->sum('amount');

        $investorCapital = (clone $totalsQuery)->whereNotNull('investor_id')->where('type', 'income')->sum('amount');
        $investorWithdrawals = (clone $totalsQuery)->whereNotNull('investor_id')->where('type', 'expense')->sum('amount');
        $totalInvestorFunds = $investorCapital - $investorWithdrawals;

        $monthlyIncome = collect();
        if (!Auth::user()->hasRole('admin') && !Auth::user()->hasRole('finance') && $userCoordinator) {
            $monthlyIncome = Transaction::selectRaw('strftime("%Y-%m", transaction_date) as ym, SUM(amount) as total')
                ->where('coordinator_id', $userCoordinator->id)
                ->where('type', 'income')
                ->whereIn('category', ['Member Income', 'Voucher Income'])
                ->groupBy('ym')
                ->orderBy('ym')
                ->get();
        }

        if (Auth::user()->hasRole('admin') || Auth::user()->hasRole('finance')) {
            $coordinators = Coordinator::all();
        } else {
            $coordinators = Coordinator::where('user_id', Auth::id())->get();
        }
        
        // --- HITUNG SUMMARY PER KOORDINATOR ---
        $coordinatorSummaries = [];
        foreach ($coordinators as $coordinator) {
            $grossRevenue = Transaction::where('coordinator_id', $coordinator->id)
                ->where('type', 'income')
                ->whereIn('category', ['Member Income', 'Voucher Income'])
                ->sum('amount');

            $commission = Transaction::where('coordinator_id', $coordinator->id)
                ->where('category', 'Coordinator Commission')
                ->sum('amount');

            $ispShare = Transaction::where('coordinator_id', $coordinator->id)
                ->where('category', 'ISP Payment')
                ->sum('amount');

            $toolFund = Transaction::where('coordinator_id', $coordinator->id)
                ->where('category', 'Tool Fund')
                ->sum('amount');

            $investorShareByCoordinator = Transaction::where('coordinator_id', $coordinator->id)
                ->where('category', 'Investor Profit Share')
                ->sum('amount');

            $investorCashByCoordinator = Transaction::where('coordinator_id', $coordinator->id)
                ->where('category', 'Investor Cash Fund')
                ->sum('amount');

            $deposited = Transaction::where('coordinator_id', $coordinator->id)
                ->where('category', 'Deposit to Company')
                ->sum('amount');

            // Hitung TOTAL Expenses (Termasuk Barang Ambil)
            $totalExpenses = Transaction::where('coordinator_id', $coordinator->id)
                ->where('type', 'expense')
                ->whereNotIn('category', [
                    'Coordinator Commission',
                    'ISP Payment',
                    'Tool Fund',
                    'Investor Profit Share',
                    'Investor Cash Fund',
                    'Pembayaran ISP',
                    'Pembelian Alat',
                    'Deposit to Company'
                ])
                ->sum('amount');

            // PISAHKAN: Hitung Inventory (Ambil Barang & Pengeluaran INV-OUT)
            $inventoryExpenses = Transaction::where('coordinator_id', $coordinator->id)
                ->where(function($q) {
                    $q->where('category', 'Ambil Barang')
                      ->orWhere(function($sub) {
                          $sub->where('category', 'Pengeluaran Pengurus')
                              ->where('reference_number', 'like', 'INV-OUT-%');
                      });
                })
                ->sum('amount');

            // PISAHKAN: Hitung Cash Expenses (Ops & Beli Luar)
            $cashExpenses = $totalExpenses - $inventoryExpenses;

            // Hitung Net Balance Berdasarkan Cash
            $netBalance = $grossRevenue - $commission - $cashExpenses - $deposited;

            $coordinatorSummaries[] = (object) [
                'id' => $coordinator->id,
                'name' => $coordinator->name,
                'gross_revenue' => $grossRevenue,
                'commission' => $commission,
                'isp_share' => $ispShare,
                'tools_cost' => $toolFund,
                'investor_share' => $investorShareByCoordinator,
                'investor_cash' => $investorCashByCoordinator,
                'expenses' => $cashExpenses, // Update ini ke cash expenses agar tabel dashboard benar
                'cash_expenses' => $cashExpenses, // Tambahkan untuk view baru
                'inventory_expenses' => $inventoryExpenses, // Tambahkan untuk info
                'deposited' => $deposited,
                'net_balance' => $netBalance,
            ];
        }
        // ---------------------------------------------

        $investorDetailsByCoordinator = [];
        if (Auth::user()->hasRole('admin') || Auth::user()->hasRole('finance')) {
            $investorRows = DB::table('transactions')
                ->join('investors', 'transactions.investor_id', '=', 'investors.id')
                ->select(
                    'transactions.coordinator_id',
                    'transactions.investor_id',
                    'investors.name as investor_name',
                    DB::raw('SUM(CASE WHEN transactions.category = "Investor Profit Share" THEN transactions.amount ELSE 0 END) as profit_share'),
                    DB::raw('SUM(CASE WHEN transactions.category = "Investor Cash Fund" THEN transactions.amount ELSE 0 END) as cash_fund')
                )
                ->whereNotNull('transactions.coordinator_id')
                ->whereNotNull('transactions.investor_id');

            if ($request->has('month')) {
                $investorRows->whereMonth('transactions.transaction_date', date('m', strtotime($request->month)))
                    ->whereYear('transactions.transaction_date', date('Y', strtotime($request->month)));
            }

            $investorRows = $investorRows
                ->groupBy('transactions.coordinator_id', 'transactions.investor_id', 'investors.name')
                ->get();

            foreach ($investorRows as $row) {
                $investorDetailsByCoordinator[$row->coordinator_id][] = [
                    'investor_id' => $row->investor_id,
                    'investor_name' => $row->investor_name,
                    'profit_share' => $row->profit_share,
                    'cash_fund' => $row->cash_fund,
                ];
            }
        }

        // Fetch Income Breakdown Details for Admin Dashboard
        $incomeBreakdowns = [];
        if (Auth::user()->hasRole('admin') || Auth::user()->hasRole('finance')) {
            $recentIncomes = Transaction::where('type', 'income')
                ->whereIn('category', ['Member Income', 'Voucher Income'])
                ->with('coordinator')
                ->latest('transaction_date')
                ->take(10)
                ->get();

            foreach ($recentIncomes as $inc) {
                $com = Transaction::where('reference_number', 'COM-' . $inc->id)->value('amount') ?? 0;
                $isp = Transaction::where('reference_number', 'ISP-' . $inc->id)->value('amount') ?? 0;
                $tool = Transaction::where('reference_number', 'TOOL-' . $inc->id)->value('amount') ?? 0;
                $cash = Transaction::where('reference_number', 'INV-CASH-' . $inc->id)->value('amount') ?? 0;
                $shares = Transaction::where('reference_number', 'INV-' . $inc->id)->sum('amount');
                
                $investorNames = Transaction::where('reference_number', 'INV-' . $inc->id)
                    ->with('investor')
                    ->get()
                    ->pluck('investor.name')
                    ->unique()
                    ->implode(', ');
                
                $netBalance = $inc->amount - $com - $isp - $tool;
                $managerIncome = $com + $isp + $tool;

                $remaining1 = $inc->amount - $com;
                $remaining2 = $remaining1 - $isp;
                $remaining3 = $remaining2 - $tool; 

                $incomeBreakdowns[] = (object) [
                    'id' => $inc->id,
                    'date' => $inc->transaction_date,
                    'coordinator_name' => $inc->coordinator->name ?? '-',
                    'gross_amount' => $inc->amount,
                    'commission' => $com,
                    'isp_share' => $isp,
                    'tool_fund' => $tool,
                    'manager_income' => $managerIncome,
                    'net_balance' => $netBalance,
                    'remaining_1' => $remaining1,
                    'remaining_2' => $remaining2,
                    'remaining_3' => $remaining3,
                    'cash_fund' => $cash,
                    'investor_share' => $shares,
                    'investor_names' => $investorNames
                ];
            }
        }

        // Fetch Settings for Dynamic Labels
        $coordRate = Setting::getValue('commission_coordinator_percent', 15);
        $ispRate = Setting::getValue('commission_isp_percent', 25);
        $toolRate = Setting::getValue('commission_tool_percent', 20);
        $investorCashRate = Setting::getValue('investor_cash_percent', 5);
        $managerRate = $coordRate + $ispRate + $toolRate;

        // Fetch Investors
        $investors = [];
        if (Auth::user()->hasRole('admin') || Auth::user()->hasRole('finance')) {
            $investors = \App\Models\Investor::all();
        } else {
            if ($userCoordinator) {
                $investors = \App\Models\Investor::where('coordinator_id', $userCoordinator->id)->get();
            }
        }

        return view('finance.index', compact(
            'transactions',
            'totalIncome',
            'totalExpense',
            'balance',
            'coordinators',
            'coordinatorSummaries',
            'totalIspShare',
            'totalToolFund',
            'investors',
            'totalInvestorFunds',
            'totalGeneralExpenses',
            'totalCompanyGrossShare',
            'toolRate',
            'managerRate',
            'coordRate',
            'ispRate',
            'investorCashRate',
            'investorDetailsByCoordinator',
            'monthlyIncome',
            'incomeBreakdowns'
        ));
    }
    
    private function buildProfitLossData(?string $month, ?int $coordinatorId = null): array
    {
        $query = Transaction::query();
        
        if ($month) {
            $query->whereMonth('transaction_date', date('m', strtotime($month)))
                  ->whereYear('transaction_date', date('Y', strtotime($month)));
        }

        if ($coordinatorId) {
            $query->where('coordinator_id', $coordinatorId);
        }

        $memberIncome = (clone $query)->where('category', 'Member Income')->sum('amount');
        $voucherIncome = (clone $query)->where('category', 'Voucher Income')->sum('amount');
        $otherIncome = (clone $query)->where('type', 'income')
            ->whereNotIn('category', ['Member Income', 'Voucher Income'])
            ->sum('amount');
        
        $totalRevenue = $memberIncome + $voucherIncome + $otherIncome;

        $coordCommission = (clone $query)->where('category', 'Coordinator Commission')->sum('amount');
        $ispPayment = (clone $query)->where('category', 'ISP Payment')->sum('amount');
        $toolFund = (clone $query)->where('category', 'Tool Fund')->sum('amount');
        
        $totalCOGS = $coordCommission + $ispPayment + $toolFund;

        $grossProfit = $totalRevenue - $totalCOGS;

        $operatingExpenses = (clone $query)->where('type', 'expense')
            ->whereNotIn('category', [
                'Coordinator Commission', 
                'ISP Payment', 
                'Tool Fund', 
                'Pembayaran ISP', 
                'Pembelian Alat', 
                'Investor Cash Fund',
                'Investor Profit Share',
                'Deposit to Company',
                'Ambil Barang'
            ])
            ->sum('amount');

        $serverExpenses = (clone $query)->where('type', 'expense')->where('category', 'Operational')->sum('amount');
        $transportExpenses = (clone $query)->where('type', 'expense')->where('category', 'Transport')->sum('amount');
        $consumptionExpenses = (clone $query)->where('type', 'expense')->where('category', 'Consumption')->sum('amount');
        $repairExpenses = (clone $query)->where('type', 'expense')->where('category', 'Repair')->sum('amount');

        $netProfitBeforeCash = $grossProfit - $operatingExpenses;

        $investorCashPercent = Setting::getValue('investor_cash_percent', 5);
        $investorCashReserve = $netProfitBeforeCash > 0 ? $netProfitBeforeCash * ($investorCashPercent / 100) : 0;
        $netProfitAfterCash = $netProfitBeforeCash - $investorCashReserve;

        $knownOperatingExpenses = $serverExpenses + $transportExpenses + $consumptionExpenses + $repairExpenses;
        
        $excludedCategories = [
            'Coordinator Commission', 'ISP Payment', 'Tool Fund', 
            'Pembayaran ISP', 'Pembelian Alat', 'Investor Cash Fund',
            'Operational', 'Transport', 'Consumption', 'Repair',
            'Investor Profit Share', 'Deposit to Company', 'Ambil Barang'
        ];

        $otherExpensesBreakdown = (clone $query)
            ->where('type', 'expense')
            ->whereNotIn('category', $excludedCategories)
            ->select('category', DB::raw('SUM(amount) as total'))
            ->groupBy('category')
            ->pluck('total', 'category');

        $otherOperatingExpenses = $operatingExpenses - $knownOperatingExpenses;

        $coordRate = Setting::getValue('commission_coordinator_percent', 15);
        $ispRate = Setting::getValue('commission_isp_percent', 25); // Adjusted to 25 based on user input
        $toolRate = Setting::getValue('commission_tool_percent', 20); // Adjusted to 20 based on user input

        // Calculate effective rates based on actual data
        $coordEffectiveRate = ($totalRevenue > 0) ? ($coordCommission / $totalRevenue) * 100 : 0;
        $ispEffectiveRate = ($totalRevenue > 0) ? ($ispPayment / $totalRevenue) * 100 : 0;
        $toolEffectiveRate = ($totalRevenue > 0) ? ($toolFund / $totalRevenue) * 100 : 0;

        return [
            'memberIncome' => $memberIncome,
            'voucherIncome' => $voucherIncome,
            'otherIncome' => $otherIncome,
            'totalRevenue' => $totalRevenue,
            'coordCommission' => $coordCommission,
            'ispPayment' => $ispPayment,
            'toolFund' => $toolFund,
            'totalCOGS' => $totalCOGS,
            'grossProfit' => $grossProfit,
            'operatingExpenses' => $operatingExpenses,
            'serverExpenses' => $serverExpenses,
            'transportExpenses' => $transportExpenses,
            'consumptionExpenses' => $consumptionExpenses,
            'repairExpenses' => $repairExpenses,
            'otherOperatingExpenses' => $otherOperatingExpenses,
            'otherExpensesBreakdown' => $otherExpensesBreakdown,
            'netProfit' => $netProfitBeforeCash, // Keep standard Net Profit definition for main display
            'netProfitAfterCash' => $netProfitAfterCash, // Pass this for investor balance check
            'investorCashPercent' => $investorCashPercent,
            'investorCashReserve' => $investorCashReserve,
            'investorShareAfterCash' => $netProfitAfterCash,
            'coordRate' => $coordRate, // Use fixed setting rate for display as requested
            'ispRate' => $ispRate, // Use fixed setting rate for display
            'toolRate' => $toolRate, // Use fixed setting rate for display
            'coordSettingRate' => $coordRate, 
            'ispSettingRate' => $ispRate,
            'toolSettingRate' => $toolRate,
        ];
    }

    private function buildManagerReportData(?string $month, ?string $coordinatorId = null): array
    {
        $query = Transaction::whereNotNull('coordinator_id');
        
        if ($month) {
            $query->whereMonth('transaction_date', date('m', strtotime($month)))
                  ->whereYear('transaction_date', date('Y', strtotime($month)));
        }

        if ($coordinatorId) {
            $query->where('coordinator_id', $coordinatorId);
        }

        $memberIncome = (clone $query)->where('type', 'income')->where('category', 'Member Income')->sum('amount');
        $voucherIncome = (clone $query)->where('type', 'income')->where('category', 'Voucher Income')->sum('amount');
        $totalRevenue = $memberIncome + $voucherIncome;

        $coordRate = Setting::getValue('commission_coordinator_percent', 15);
        $coordCommission = (clone $query)->where('category', 'Coordinator Commission')->sum('amount');
        
        // Expenses logic - Exclude non-cash inventory items (Ambil Barang / INV-OUT)
        $expenses = (clone $query)->where('type', 'expense')
            ->whereNotIn('category', [
                'Coordinator Commission',
                'ISP Payment',
                'Tool Fund',
                'Investor Profit Share',
                'Investor Cash Fund',
                'Pembayaran ISP',
                'Pembelian Alat',
                'Deposit to Company',
                'Ambil Barang'
            ])
            ->where(function($q) {
                $q->whereNull('reference_number')
                  ->orWhere('reference_number', 'not like', 'INV-OUT-%');
            })
            ->sum('amount');

        $transportExpenses = (clone $query)->where('type', 'expense')->where('category', 'Transport')->sum('amount');
        $consumptionExpenses = (clone $query)->where('type', 'expense')->where('category', 'Consumption')->sum('amount');
        $repairExpenses = (clone $query)->where('type', 'expense')->where('category', 'Repair')->sum('amount');
        
        // Detailed breakdown of "Other" expenses to identify the large amount
        $excludedCategories = [
            'Coordinator Commission', 'ISP Payment', 'Tool Fund', 
            'Investor Profit Share', 'Investor Cash Fund', 
            'Pembayaran ISP', 'Pembelian Alat', 'Deposit to Company',
            'Transport', 'Consumption', 'Repair'
        ];

        $otherExpensesBreakdown = (clone $query)
            ->where('type', 'expense')
            ->whereNotIn('category', $excludedCategories)
            ->select('category', DB::raw('SUM(amount) as total'))
            ->groupBy('category')
            ->pluck('total', 'category');

        $otherOperatingExpenses = $otherExpensesBreakdown->sum();

        $deposited = (clone $query)->where('category', 'Deposit to Company')->sum('amount');

        $netBalance = $totalRevenue - $coordCommission - $expenses - $deposited;

        // Breakdown expenses for display if needed, but total is what matters for the formula
        $operatingExpenses = $expenses;

        return [
            'memberIncome' => $memberIncome,
            'voucherIncome' => $voucherIncome,
            'totalRevenue' => $totalRevenue,
            'coordCommission' => $coordCommission,
            'operatingExpenses' => $operatingExpenses,
            'transportExpenses' => $transportExpenses,
            'consumptionExpenses' => $consumptionExpenses,
            'repairExpenses' => $repairExpenses,
            'otherOperatingExpenses' => $otherOperatingExpenses,
            'otherExpensesBreakdown' => $otherExpensesBreakdown, // Pass breakdown to view
            'deposited' => $deposited,
            'netBalance' => $netBalance,
            'coordRate' => $coordRate,
        ];
    }

    public function profitLoss(Request $request)
    {
        if (!Auth::user()->hasRole('admin') && !Auth::user()->hasRole('finance')) {
            abort(403, 'Unauthorized action.');
        }

        $month = $request->input('month');
        $coordinatorId = $request->input('coordinator_id');
        
        $data = $this->buildProfitLossData($month, $coordinatorId);
        $coordinators = Coordinator::all();

        return view('finance.profit_loss', array_merge($data, [
            'month' => $month,
            'coordinators' => $coordinators,
            'selectedCoordinatorId' => $coordinatorId
        ]));
    }

    public function managerReport(Request $request)
    {
        if (!Auth::user()->hasRole('admin') && !Auth::user()->hasRole('finance')) {
            abort(403, 'Unauthorized action.');
        }

        $month = $request->input('month');
        $coordinatorId = $request->input('coordinator_id');
        
        $data = $this->buildManagerReportData($month, $coordinatorId);
        $coordinators = Coordinator::all();

        return view('finance.manager_report', array_merge($data, [
            'month' => $month,
            'coordinators' => $coordinators,
            'selectedCoordinatorId' => $coordinatorId
        ]));
    }

    public function downloadManagerReportPdf(Request $request)
    {
        if (!Auth::user()->hasRole('admin') && !Auth::user()->hasRole('finance')) {
            abort(403, 'Unauthorized action.');
        }

        $month = $request->input('month');
        $data = $this->buildManagerReportData($month);

        $coordinatorSummaries = [];
        $coordinators = Coordinator::all();
        foreach ($coordinators as $coordinator) {
            $coordQuery = Transaction::where('coordinator_id', $coordinator->id);
            if ($month) {
                $coordQuery->whereMonth('transaction_date', date('m', strtotime($month)))
                    ->whereYear('transaction_date', date('Y', strtotime($month)));
            }

            $grossRevenue = (clone $coordQuery)
                ->where('type', 'income')
                ->whereIn('category', ['Member Income', 'Voucher Income'])
                ->sum('amount');

            $commission = (clone $coordQuery)
                ->where('category', 'Coordinator Commission')
                ->sum('amount');

            $ispShare = (clone $coordQuery)
                ->where('category', 'ISP Payment')
                ->sum('amount');

            $toolFund = (clone $coordQuery)
                ->where('category', 'Tool Fund')
                ->sum('amount');

            $investorShareByCoordinator = (clone $coordQuery)
                ->where('category', 'Investor Profit Share')
                ->sum('amount');

            $investorCashByCoordinator = (clone $coordQuery)
                ->where('category', 'Investor Cash Fund')
                ->sum('amount');

            $deposited = (clone $coordQuery)
                ->where('category', 'Deposit to Company')
                ->sum('amount');

            $expenses = (clone $coordQuery)
                ->where('type', 'expense')
                ->whereNotIn('category', [
                    'Coordinator Commission',
                    'ISP Payment',
                    'Tool Fund',
                    'Investor Profit Share',
                    'Investor Cash Fund',
                    'Pembayaran ISP',
                    'Pembelian Alat',
                    'Deposit to Company',
                    'Ambil Barang'
                ])
                ->where(function($q) {
                    $q->whereNull('reference_number')
                      ->orWhere('reference_number', 'not like', 'INV-OUT-%');
                })
                ->sum('amount');

            $netBalance = $grossRevenue - $commission - $expenses - $deposited;

            $coordinatorSummaries[] = (object) [
                'name' => $coordinator->name,
                'gross_revenue' => $grossRevenue,
                'commission' => $commission,
                'isp_share' => $ispShare,
                'tools_cost' => $toolFund,
                'investor_share' => $investorShareByCoordinator,
                'investor_cash' => $investorCashByCoordinator,
                'expenses' => $expenses,
                'deposited' => $deposited,
                'net_balance' => $netBalance,
            ];
        }

        $investorSummaries = DB::table('transactions')
            ->join('investors', 'transactions.investor_id', '=', 'investors.id')
            ->select(
                'transactions.investor_id',
                'investors.name as investor_name',
                DB::raw('SUM(CASE WHEN transactions.category = "Investor Profit Share" THEN transactions.amount ELSE 0 END) as profit_share'),
                DB::raw('SUM(CASE WHEN transactions.category = "Investor Cash Fund" THEN transactions.amount ELSE 0 END) as cash_fund'),
                DB::raw('SUM(CASE WHEN transactions.type = "income" THEN transactions.amount ELSE 0 END) as capital'),
                DB::raw('SUM(CASE WHEN transactions.type = "expense" THEN transactions.amount ELSE 0 END) as withdrawals')
            )
            ->whereNotNull('transactions.investor_id');

        if ($month) {
            $investorSummaries->whereMonth('transactions.transaction_date', date('m', strtotime($month)))
                ->whereYear('transactions.transaction_date', date('Y', strtotime($month)));
        }

        $investorSummaries = $investorSummaries
            ->groupBy('transactions.investor_id', 'investors.name')
            ->get();

        $managerName = Auth::user()->name;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('finance.manager_report_pdf', array_merge($data, [
            'month' => $month,
            'coordinatorSummaries' => $coordinatorSummaries,
            'investorSummaries' => $investorSummaries,
            'managerName' => $managerName,
        ]));
        
        $pdf->setPaper('a4', 'portrait');
        
        $fileName = 'Neraca_Awal';
        if ($month) {
            $fileName .= '_' . date('Y_m', strtotime($month));
        }
        $fileName .= '.pdf';
        
        return $pdf->stream($fileName, ['Attachment' => false]);
    }

    public function downloadManagerReportExcel(Request $request)
    {
        if (!Auth::user()->hasRole('admin') && !Auth::user()->hasRole('finance')) {
            abort(403, 'Unauthorized action.');
        }

        $month = $request->input('month');
        $data = $this->buildManagerReportData($month);

        $coordinatorSummaries = [];
        $coordinators = Coordinator::all();
        foreach ($coordinators as $coordinator) {
            $coordQuery = Transaction::where('coordinator_id', $coordinator->id);
            if ($month) {
                $coordQuery->whereMonth('transaction_date', date('m', strtotime($month)))
                    ->whereYear('transaction_date', date('Y', strtotime($month)));
            }

            $grossRevenue = (clone $coordQuery)
                ->where('type', 'income')
                ->whereIn('category', ['Member Income', 'Voucher Income'])
                ->sum('amount');

            $commission = (clone $coordQuery)
                ->where('category', 'Coordinator Commission')
                ->sum('amount');

            $ispShare = (clone $coordQuery)
                ->where('category', 'ISP Payment')
                ->sum('amount');

            $toolFund = (clone $coordQuery)
                ->where('category', 'Tool Fund')
                ->sum('amount');

            $investorShareByCoordinator = (clone $coordQuery)
                ->where('category', 'Investor Profit Share')
                ->sum('amount');

            $investorCashByCoordinator = (clone $coordQuery)
                ->where('category', 'Investor Cash Fund')
                ->sum('amount');

            $deposited = (clone $coordQuery)
                ->where('category', 'Deposit to Company')
                ->sum('amount');

            $expenses = (clone $coordQuery)
                ->where('type', 'expense')
                ->whereNotIn('category', [
                    'Coordinator Commission',
                    'ISP Payment',
                    'Tool Fund',
                    'Investor Profit Share',
                    'Investor Cash Fund',
                    'Pembayaran ISP',
                    'Pembelian Alat',
                    'Deposit to Company'
                ])
                ->sum('amount');

            $netBalance = $grossRevenue - $commission - $expenses - $deposited;

            $coordinatorSummaries[] = (object) [
                'name' => $coordinator->name,
                'gross_revenue' => $grossRevenue,
                'commission' => $commission,
                'isp_share' => $ispShare,
                'tools_cost' => $toolFund,
                'investor_share' => $investorShareByCoordinator,
                'investor_cash' => $investorCashByCoordinator,
                'expenses' => $expenses,
                'deposited' => $deposited,
                'net_balance' => $netBalance,
            ];
        }

        $investorSummaries = DB::table('transactions')
            ->join('investors', 'transactions.investor_id', '=', 'investors.id')
            ->select(
                'transactions.investor_id',
                'investors.name as investor_name',
                DB::raw('SUM(CASE WHEN transactions.category = "Investor Profit Share" THEN transactions.amount ELSE 0 END) as profit_share'),
                DB::raw('SUM(CASE WHEN transactions.category = "Investor Cash Fund" THEN transactions.amount ELSE 0 END) as cash_fund'),
                DB::raw('SUM(CASE WHEN transactions.type = "income" THEN transactions.amount ELSE 0 END) as capital'),
                DB::raw('SUM(CASE WHEN transactions.type = "expense" THEN transactions.amount ELSE 0 END) as withdrawals')
            )
            ->whereNotNull('transactions.investor_id');

        if ($month) {
            $investorSummaries->whereMonth('transactions.transaction_date', date('m', strtotime($month)))
                ->whereYear('transactions.transaction_date', date('Y', strtotime($month)));
        }

        $investorSummaries = $investorSummaries
            ->groupBy('transactions.investor_id', 'investors.name')
            ->get();

        $fileName = 'laporan_pengurus';
        if ($month) {
            $fileName .= '_' . date('Y_m', strtotime($month));
        }
        $fileName .= '.xlsx';

        return response()->streamDownload(function () use ($data, $coordinatorSummaries, $investorSummaries) {
            $writer = new Writer();
            $writer->openToFile('php://output');

            $writer->addRow(Row::fromValues([
                'Item',
                'Amount',
            ]));

            $writer->addRow(Row::fromValues(['Pendapatan Member', $data['memberIncome']]));
            $writer->addRow(Row::fromValues(['Pendapatan Voucher', $data['voucherIncome']]));
            $writer->addRow(Row::fromValues(['Total Pendapatan', $data['totalRevenue']]));
            $writer->addRow(Row::fromValues(['Komisi Pengurus', -1 * $data['coordCommission']]));
            $writer->addRow(Row::fromValues(['Pengeluaran Transportasi', -1 * $data['transportExpenses']]));
            $writer->addRow(Row::fromValues(['Pengeluaran Konsumsi', -1 * $data['consumptionExpenses']]));
            $writer->addRow(Row::fromValues(['Pengeluaran Perbaikan', -1 * $data['repairExpenses']]));
            $writer->addRow(Row::fromValues(['Pengeluaran Lainnya', -1 * $data['otherOperatingExpenses']]));
            $writer->addRow(Row::fromValues(['Total Pengeluaran Pengurus', -1 * $data['operatingExpenses']]));
            $writer->addRow(Row::fromValues(['Sudah Disetor', -1 * $data['deposited']]));
            $writer->addRow(Row::fromValues(['Sisa Saldo (Wajib Setor)', $data['netBalance']]));

            $writer->addRow(Row::fromValues([]));
            $writer->addRow(Row::fromValues(['Ringkasan per Pengurus']));
            $writer->addRow(Row::fromValues([
                'Pengurus',
                'Pendapatan Member + Voucher',
                'Komisi Pengurus',
                'Iuran Internet',
                'Manajemen',
                'Bagian Investor Setelah Dana Kas',
                'Dana Kas Investor',
                'Pengeluaran Pengurus',
                'Sudah Disetor',
                'Sisa Saldo (Wajib Setor)',
            ]));

            foreach ($coordinatorSummaries as $row) {
                $writer->addRow(Row::fromValues([
                    $row->name,
                    $row->gross_revenue,
                    -1 * $row->commission,
                    -1 * $row->isp_share,
                    -1 * $row->tools_cost,
                    -1 * $row->investor_share,
                    -1 * $row->investor_cash,
                    -1 * $row->expenses,
                    -1 * $row->deposited,
                    $row->net_balance,
                ]));
            }

            $writer->addRow(Row::fromValues([]));
            $writer->addRow(Row::fromValues(['Ringkasan Pembagian Investor']));
            $writer->addRow(Row::fromValues([
                'Investor',
                'Bagian Investor Setelah Dana Kas',
                'Dana Kas Investor',
                'Total Pembagian',
                'Saldo Bersih Investor',
            ]));

            foreach ($investorSummaries as $row) {
                $totalShare = $row->profit_share + $row->cash_fund;
                $netBalance = $row->capital - $row->withdrawals;

                $writer->addRow(Row::fromValues([
                    $row->investor_name,
                    -1 * $row->profit_share,
                    -1 * $row->cash_fund,
                    -1 * $totalShare,
                    $netBalance,
                ]));
            }

            $writer->close();
        }, $fileName);
    }

    public function downloadProfitLossPdf(Request $request)
    {
        if (!Auth::user()->hasRole('admin') && !Auth::user()->hasRole('finance')) {
            abort(403, 'Unauthorized action.');
        }

        $month = $request->input('month');
        $coordinatorId = $request->input('coordinator_id');
        
        $data = $this->buildProfitLossData($month, $coordinatorId);

        $investorSummaries = DB::table('transactions')
            ->join('investors', 'transactions.investor_id', '=', 'investors.id')
            ->select(
                'transactions.investor_id',
                'investors.name as investor_name',
                DB::raw('SUM(CASE WHEN transactions.category = "Investor Profit Share" THEN transactions.amount ELSE 0 END) as profit_share'),
                DB::raw('SUM(CASE WHEN transactions.category = "Investor Cash Fund" THEN transactions.amount ELSE 0 END) as cash_fund'),
                DB::raw('SUM(CASE WHEN transactions.type = "income" THEN transactions.amount ELSE 0 END) as capital'),
                DB::raw('SUM(CASE WHEN transactions.type = "expense" THEN transactions.amount ELSE 0 END) as withdrawals')
            )
            ->whereNotNull('transactions.investor_id');

        if ($month) {
            $investorSummaries->whereMonth('transactions.transaction_date', date('m', strtotime($month)))
                ->whereYear('transactions.transaction_date', date('Y', strtotime($month)));
        }

        if ($coordinatorId) {
            $investorSummaries->where('transactions.coordinator_id', $coordinatorId);
        }

        $investorSummaries = $investorSummaries
            ->groupBy('transactions.investor_id', 'investors.name')
            ->get();

        $coordinatorName = null;
        if ($coordinatorId) {
            $coordinatorName = Coordinator::find($coordinatorId)->name ?? null;
        }

        $managerName = Auth::user()->name;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('finance.profit_loss_pdf', array_merge($data, [
            'month' => $month,
            'investorSummaries' => $investorSummaries,
            'coordinatorName' => $coordinatorName,
            'managerName' => $managerName,
        ]));
        
        $pdf->setPaper('a4', 'portrait');
        
        $fileName = 'Laporan_Laba_Rugi';
        if ($month) {
            $fileName .= '_' . date('Y_m', strtotime($month));
        }
        $fileName .= '.pdf';
        
        return $pdf->stream($fileName, ['Attachment' => false]);
    }

    public function downloadProfitLossExcel(Request $request)
    {
        if (!Auth::user()->hasRole('admin') && !Auth::user()->hasRole('finance')) {
            abort(403, 'Unauthorized action.');
        }

        $month = $request->input('month');
        $data = $this->buildProfitLossData($month);

        $fileName = 'profit_loss';
        if ($month) {
            $fileName .= '_' . date('Y_m', strtotime($month));
        }
        $fileName .= '.xlsx';

        return response()->streamDownload(function () use ($data) {
            $writer = new Writer();
            $writer->openToFile('php://output');

            $writer->addRow(Row::fromValues([
                'Item',
                'Amount',
            ]));

            $writer->addRow(Row::fromValues(['Member Income', $data['memberIncome']]));
            $writer->addRow(Row::fromValues(['Voucher Income', $data['voucherIncome']]));
            $writer->addRow(Row::fromValues(['Other Income', $data['otherIncome']]));
            $writer->addRow(Row::fromValues(['Total Revenue', $data['totalRevenue']]));
            $writer->addRow(Row::fromValues(['Coordinator Commission', -1 * $data['coordCommission']]));
            $writer->addRow(Row::fromValues(['ISP Payment', -1 * $data['ispPayment']]));
            $writer->addRow(Row::fromValues(['Tool Fund', -1 * $data['toolFund']]));
            $writer->addRow(Row::fromValues(['Total Cost of Revenue', -1 * $data['totalCOGS']]));
            $writer->addRow(Row::fromValues(['Gross Profit', $data['grossProfit']]));

            $writer->addRow(Row::fromValues(['Server / Operational', -1 * $data['serverExpenses']]));
            $writer->addRow(Row::fromValues(['Transport', -1 * $data['transportExpenses']]));
            $writer->addRow(Row::fromValues(['Consumption', -1 * $data['consumptionExpenses']]));
            $writer->addRow(Row::fromValues(['Repair', -1 * $data['repairExpenses']]));

            if (isset($data['otherExpensesBreakdown']) && count($data['otherExpensesBreakdown']) > 0) {
                foreach ($data['otherExpensesBreakdown'] as $category => $amount) {
                    $writer->addRow(Row::fromValues([$category, -1 * $amount]));
                }
            } elseif ($data['otherOperatingExpenses'] != 0) {
                $writer->addRow(Row::fromValues(['Other Operating Expenses', -1 * $data['otherOperatingExpenses']]));
            }

            $writer->addRow(Row::fromValues(['Total Operating Expenses', -1 * $data['operatingExpenses']]));
            $writer->addRow(Row::fromValues(['Net Profit', $data['netProfit']]));

            $writer->close();
        }, $fileName);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:income,expense,transfer',
            'category' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'transaction_date' => 'required|date',
            'description' => 'nullable|string',
            'coordinator_id' => 'nullable|exists:coordinators,id',
            'investor_id' => 'nullable|exists:investors,id',
            'reference_number' => 'nullable|string',
        ]);

        $validated['user_id'] = Auth::id();

        DB::transaction(function () use ($validated) {
            $transaction = Transaction::create($validated);

            // Logic for Coordinator Commission (15%)
            // If it's income from a coordinator (Member Income or Voucher Income)
            if ($validated['type'] === 'income' && 
                !empty($validated['coordinator_id']) && 
                in_array($validated['category'], ['Member Income', 'Voucher Income'])) {
                
                $coordRate = Setting::getValue('commission_coordinator_percent', 15);
                $ispRate = Setting::getValue('commission_isp_percent', 25);
                $toolRate = Setting::getValue('commission_tool_percent', 15);

                $gross = $validated['amount'];
                $coordAmount = $gross * ($coordRate / 100);
                $rem1 = $gross - $coordAmount;
                $ispAmount = $rem1 * ($ispRate / 100);
                $rem2 = $rem1 - $ispAmount;
                $toolAmount = $rem2 * ($toolRate / 100);
                $rem3 = $rem2 - $toolAmount;

                $investorCashPercent = Setting::getValue('investor_cash_percent', 5);
                $investorCashAmount = $rem3 * ($investorCashPercent / 100);
                $rem4 = $rem3 - $investorCashAmount;

                // Investor receives 100% of remaining after cash fund
                $investorDistributableAmount = $rem4;

                $singleInvestorId = $validated['investor_id'] ?? null;
                if ($singleInvestorId) {
                    if ($investorDistributableAmount > 0) {
                        Transaction::create([
                            'user_id' => Auth::id(),
                            'type' => 'expense',
                            'category' => 'Investor Profit Share',
                            'amount' => $investorDistributableAmount,
                            'transaction_date' => $validated['transaction_date'],
                            'description' => '100% Profit Share from transaction #' . $transaction->id,
                            'coordinator_id' => $validated['coordinator_id'],
                            'investor_id' => $singleInvestorId,
                            'reference_number' => 'INV-' . $transaction->id,
                        ]);
                    }
                    if ($investorCashAmount > 0) {
                                Transaction::create([
                                    'user_id' => Auth::id(),
                                    'type' => 'expense',
                                    'category' => 'Investor Cash Fund',
                                    'amount' => $investorCashAmount,
                                    'transaction_date' => $validated['transaction_date'],
                                    'description' => $investorCashPercent . '% Uang Kas Pengurus from transaction #' . $transaction->id,
                                    'coordinator_id' => $validated['coordinator_id'],
                                    'reference_number' => 'INV-CASH-' . $transaction->id,
                                ]);
                            }
                } else {
                    $coordinatorInvestors = \App\Models\Investor::where('coordinator_id', $validated['coordinator_id'])->get();
                    if ($coordinatorInvestors->count() === 1 && $investorDistributableAmount > 0) {
                        $investor = $coordinatorInvestors->first();
                        Transaction::create([
                            'user_id' => Auth::id(),
                            'type' => 'expense',
                            'category' => 'Investor Profit Share',
                            'amount' => $investorDistributableAmount,
                            'transaction_date' => $validated['transaction_date'],
                            'description' => '100% Profit Share from transaction #' . $transaction->id,
                            'coordinator_id' => $validated['coordinator_id'],
                            'investor_id' => $investor->id,
                            'reference_number' => 'INV-' . $transaction->id,
                        ]);
                        if ($investorCashAmount > 0) {
                            Transaction::create([
                                'user_id' => Auth::id(),
                                'type' => 'expense',
                                'category' => 'Investor Cash Fund',
                                'amount' => $investorCashAmount,
                                'transaction_date' => $validated['transaction_date'],
                                'description' => $investorCashPercent . '% Uang Kas Pengurus from transaction #' . $transaction->id,
                                'coordinator_id' => $validated['coordinator_id'],
                                'reference_number' => 'INV-CASH-' . $transaction->id,
                            ]);
                        }
                    } elseif ($coordinatorInvestors->count() > 1 && $investorDistributableAmount > 0) {
                        $count = $coordinatorInvestors->count();
                        $baseShare = round($investorDistributableAmount / $count, 2);
                        $allocated = 0;
                        foreach ($coordinatorInvestors as $index => $investor) {
                            if ($index === $count - 1) {
                                $amount = $investorDistributableAmount - $allocated;
                            } else {
                                $amount = $baseShare;
                                $allocated += $amount;
                            }

                            Transaction::create([
                                'user_id' => Auth::id(),
                                'type' => 'expense',
                                'category' => 'Investor Profit Share',
                                'amount' => $amount,
                                'transaction_date' => $validated['transaction_date'],
                                'description' => '100% Profit Share from transaction #' . $transaction->id,
                                'coordinator_id' => $validated['coordinator_id'],
                                'investor_id' => $investor->id,
                                'reference_number' => 'INV-' . $transaction->id,
                            ]);
                        }
                        if ($investorCashAmount > 0) {
                                Transaction::create([
                                    'user_id' => Auth::id(),
                                    'type' => 'expense',
                                    'category' => 'Investor Cash Fund',
                                    'amount' => $investorCashAmount,
                                    'transaction_date' => $validated['transaction_date'],
                                    'description' => $investorCashPercent . '% Uang Kas Pengurus from transaction #' . $transaction->id,
                                    'coordinator_id' => $validated['coordinator_id'],
                                    'reference_number' => 'INV-CASH-' . $transaction->id,
                                ]);
                            }
                    }
                }
                
                // 1. Coordinator Commission
                Transaction::create([
                    'user_id' => Auth::id(),
                    'type' => 'expense',
                    'category' => 'Coordinator Commission',
                    'amount' => $coordAmount,
                    'transaction_date' => $validated['transaction_date'],
                    'description' => $coordRate . '% share for coordinator from transaction #' . $transaction->id,
                    'coordinator_id' => $validated['coordinator_id'],
                    'reference_number' => 'COM-' . $transaction->id,
                ]);

                // 2. ISP Payment
                Transaction::create([
                    'user_id' => Auth::id(),
                    'type' => 'expense',
                    'category' => 'ISP Payment',
                    'amount' => $ispAmount,
                    'transaction_date' => $validated['transaction_date'],
                    'description' => $ispRate . '% ISP share from transaction #' . $transaction->id,
                    'coordinator_id' => $validated['coordinator_id'],
                    'reference_number' => 'ISP-' . $transaction->id,
                ]);

                // 3. Tool Fund
                Transaction::create([
                    'user_id' => Auth::id(),
                    'type' => 'expense',
                    'category' => 'Tool Fund',
                    'amount' => $toolAmount,
                    'transaction_date' => $validated['transaction_date'],
                    'description' => $toolRate . '% Tool fund from transaction #' . $transaction->id,
                    'coordinator_id' => $validated['coordinator_id'],
                    'reference_number' => 'TOOL-' . $transaction->id,
                ]);
            }
        });

        return redirect()->route('finance.index')->with('success', __('Transaction recorded successfully.'));
    }

    public function materialReport(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->endOfMonth()->toDateString());
        $coordinatorId = $request->input('coordinator_id');

        $query = InventoryTransaction::with(['item', 'coordinator.region'])
            ->where('type', 'out')
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->whereHas('item', function($q) {
                $q->where('type_group', 'material');
            });

        if ($coordinatorId) {
            $query->where('coordinator_id', $coordinatorId);
        }

        $transactions = $query->latest()->get();

        $totalQuantity = $transactions->sum('quantity');
        $totalValue = $transactions->sum(function($t) {
            return $t->quantity * ($t->item->price ?? 0);
        });

        $commissionRate = Setting::getValue('commission_coordinator_percent', 15);
        $commissionAmount = $totalValue * ($commissionRate / 100);
        $netTotal = $totalValue - $commissionAmount;

        $coordinators = Coordinator::with('region')->get();

        return view('finance.material_report', compact(
            'transactions', 
            'coordinators', 
            'startDate', 
            'endDate', 
            'coordinatorId',
            'totalQuantity',
            'totalValue',
            'commissionRate',
            'commissionAmount',
            'netTotal'
        ));
    }

    public function exportAccounting()
    {
        return redirect()->back()->with('info', __('Fitur Laporan Pembukuan sedang dalam pengembangan.'));
    }

    public function settings()
    {
         return redirect()->route('settings.index');
    }
}
