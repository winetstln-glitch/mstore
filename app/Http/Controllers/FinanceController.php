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
            new Middleware('permission:finance.view', only: ['index', 'show', 'coordinatorDetail', 'downloadCoordinatorPdf', 'profitLoss', 'downloadProfitLossPdf', 'downloadProfitLossExcel', 'managerReport', 'downloadManagerReportPdf', 'downloadManagerReportExcel']),
            new Middleware('permission:finance.manage', only: ['create', 'store', 'edit', 'update', 'destroy', 'storeCoordinatorIncome']),
        ];
    }

    public function coordinatorDetail(Coordinator $coordinator, Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));

        $query = Transaction::where('coordinator_id', $coordinator->id)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc');

        $transactions = $query->get();

        // Calculate Summaries matching the index logic
        $grossRevenue = $transactions->where('type', 'income')
            ->whereIn('category', ['Member Income', 'Voucher Income'])
            ->sum('amount');

        $commission = $transactions->where('category', 'Coordinator Commission')->sum('amount');
        $ispShare = $transactions->where('category', 'ISP Payment')->sum('amount');
        $toolFund = $transactions->where('category', 'Tool Fund')->sum('amount');
        
        // Other Expenses (excluding automatically generated ones and fund usages)
        $expenses = $transactions->where('type', 'expense')
            ->whereNotIn('category', ['Coordinator Commission', 'ISP Payment', 'Tool Fund', 'Pembayaran ISP', 'Pembelian Alat'])
            ->sum('amount');

        $netBalance = $grossRevenue - $commission - $ispShare - $toolFund - $expenses;

        return view('finance.coordinator_detail', compact(
            'coordinator', 'transactions', 'grossRevenue', 'commission', 
            'ispShare', 'toolFund', 'expenses', 'netBalance', 
            'startDate', 'endDate'
        ));
    }

    public function downloadCoordinatorPdf(Coordinator $coordinator, Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));

        $query = Transaction::where('coordinator_id', $coordinator->id)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->orderBy('transaction_date', 'asc'); // ASC for report usually

        $transactions = $query->get();

        // Calculate Summaries
        $grossRevenue = $transactions->where('type', 'income')
            ->whereIn('category', ['Member Income', 'Voucher Income'])
            ->sum('amount');

        $commission = $transactions->where('category', 'Coordinator Commission')->sum('amount');
        $ispShare = $transactions->where('category', 'ISP Payment')->sum('amount');
        $toolFund = $transactions->where('category', 'Tool Fund')->sum('amount');
        
        $expenses = $transactions->where('type', 'expense')
            ->whereNotIn('category', ['Coordinator Commission', 'ISP Payment', 'Tool Fund', 'Pembayaran ISP', 'Pembelian Alat'])
            ->sum('amount');

        $netBalance = $grossRevenue - $commission - $ispShare - $toolFund - $expenses;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('finance.coordinator_pdf', compact(
            'coordinator', 'transactions', 'grossRevenue', 'commission', 
            'ispShare', 'toolFund', 'expenses', 'netBalance', 
            'startDate', 'endDate'
        ));

        $pdf->setPaper('a4', 'landscape');

        return $pdf->stream('Laporan_Keuangan_' . str_replace(' ', '_', $coordinator->name) . '_' . $startDate . '_sd_' . $endDate . '.pdf', ['Attachment' => false]);
    }

    public function update(Request $request, Transaction $transaction)
    {
        if (!Auth::user()->hasRole('admin')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'type' => 'required|in:income,expense',
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
            $investorShare = Transaction::where('reference_number', 'INV-' . $transaction->id)->first();

            $isEligible = $validated['type'] === 'income' && 
                          !empty($validated['coordinator_id']) && 
                          in_array($validated['category'], ['Member Income', 'Voucher Income']);

            if ($commission || $ispPayment || $toolFund || $investorShare) {
                if (!$isEligible) {
                    // Delete all if no longer eligible
                    if ($commission) $commission->delete();
                    if ($ispPayment) $ispPayment->delete();
                    if ($toolFund) $toolFund->delete();
                    if ($investorShare) $investorShare->delete();
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

                    // Update/Create Investor Share
                    $investorId = $validated['investor_id'] ?? null;
                    if (!$investorId) {
                         // Try to recover from existing transaction if not in request
                         if ($investorShare) {
                             $investorId = $investorShare->investor_id;
                         } else {
                             $investors = \App\Models\Investor::where('coordinator_id', $validated['coordinator_id'])->get();
                             if ($investors->count() === 1) {
                                 $investorId = $investors->first()->id;
                             }
                         }
                    }

                    if ($investorId) {
                        $investorRate = Setting::getValue('commission_investor_percent', 50);
                        $investorAmount = $rem3 * ($investorRate / 100);

                        if ($investorShare) {
                            $investorShare->update([
                                'amount' => $investorAmount,
                                'transaction_date' => $validated['transaction_date'],
                                'coordinator_id' => $validated['coordinator_id'],
                                'investor_id' => $investorId,
                                'description' => $investorRate . '% Profit Share from transaction #' . $transaction->id,
                            ]);
                        } else {
                            Transaction::create([
                                'user_id' => Auth::id(),
                                'type' => 'expense',
                                'category' => 'Investor Profit Share',
                                'amount' => $investorAmount,
                                'transaction_date' => $validated['transaction_date'],
                                'description' => $investorRate . '% Profit Share from transaction #' . $transaction->id,
                                'coordinator_id' => $validated['coordinator_id'],
                                'investor_id' => $investorId,
                                'reference_number' => 'INV-' . $transaction->id,
                            ]);
                        }
                    } elseif ($investorShare) {
                        // If no investor ID is found but share exists, delete it? 
                        // Or keep it? Safest to delete if criteria no longer met, but risky.
                        // Let's delete if we can't determine investor anymore.
                        $investorShare->delete();
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
                }
            }
        });

        return redirect()->route('finance.index')->with('success', __('Transaction updated successfully.'));
    }

    public function destroy(Transaction $transaction)
    {
        if (!Auth::user()->hasRole('admin')) {
            abort(403, 'Unauthorized action.');
        }

        DB::transaction(function () use ($transaction) {
            // Delete associated transactions
            Transaction::where('reference_number', 'COM-' . $transaction->id)->delete();
            Transaction::where('reference_number', 'ISP-' . $transaction->id)->delete();
            Transaction::where('reference_number', 'TOOL-' . $transaction->id)->delete();
            Transaction::where('reference_number', 'INV-' . $transaction->id)->delete();
            
            $transaction->delete();
        });

        return redirect()->route('finance.index')->with('success', __('Transaction deleted successfully.'));
    }

    public function bulkDestroy(Request $request)
    {
        if (!Auth::user()->hasRole('admin')) {
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
        if (!Auth::user()->hasRole('admin')) {
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
        
        // Calculate totals based on user role
        $totalsQuery = Transaction::query();
        if (!Auth::user()->hasRole('admin')) {
            if ($userCoordinator) {
                $totalsQuery->where('coordinator_id', $userCoordinator->id);
            } else {
                $totalsQuery->where('user_id', Auth::id());
            }
        }

        // Apply Month Filter to Totals
        if ($request->has('month')) {
            $totalsQuery->whereMonth('transaction_date', date('m', strtotime($request->month)))
                  ->whereYear('transaction_date', date('Y', strtotime($request->month)));
        }

        $totalIncome = (clone $totalsQuery)->where('type', 'income')->sum('amount');
        $totalExpense = (clone $totalsQuery)->where('type', 'expense')
            ->whereNotIn('category', ['Pembayaran ISP', 'Pembelian Alat'])
            ->sum('amount');
        $balance = $totalIncome - $totalExpense;
        
        // Accumulated Funds (Allocations - Usages)
        $ispAllocations = (clone $totalsQuery)->where('category', 'ISP Payment')->sum('amount');
        $ispUsages = (clone $totalsQuery)->where('category', 'Pembayaran ISP')->sum('amount');
        $totalIspShare = $ispAllocations - $ispUsages;

        $toolAllocations = (clone $totalsQuery)->where('category', 'Tool Fund')->sum('amount');
        $toolUsages = (clone $totalsQuery)->where('category', 'Pembelian Alat')->sum('amount');
        $totalToolFund = $toolAllocations - $toolUsages;
        
        // Calculate Company Share and General Expenses
        // Shares (Allocations)
        $coordShare = (clone $totalsQuery)->where('category', 'Coordinator Commission')->sum('amount');
        $investorShare = (clone $totalsQuery)->where('category', 'Investor Profit Share')->sum('amount');
        
        // Company Gross Share (Allocation) = Total Income - All Shares
        $totalCompanyGrossShare = $totalIncome - $coordShare - $ispAllocations - $toolAllocations - $investorShare;

        // General Expenses (Usage of Company Fund)
        // Exclude: Shares (Allocations) and Fund Usages (ISP/Tool)
        $totalGeneralExpenses = (clone $totalsQuery)->where('type', 'expense')
            ->whereNotIn('category', [
                'Coordinator Commission', 
                'ISP Payment', 
                'Tool Fund', 
                'Investor Profit Share', 
                'Pembayaran ISP', 
                'Pembelian Alat'
            ])->sum('amount');

        $investorCapital = (clone $totalsQuery)->whereNotNull('investor_id')->where('type', 'income')->sum('amount');
        $investorWithdrawals = (clone $totalsQuery)->whereNotNull('investor_id')->where('type', 'expense')->sum('amount');
        $totalInvestorFunds = $investorCapital - $investorWithdrawals;

        if (Auth::user()->hasRole('admin')) {
            $coordinators = Coordinator::all();
        } else {
            $coordinators = Coordinator::where('user_id', Auth::id())->get();
        }
        
        $coordinatorSummaries = [];
        foreach ($coordinators as $coordinator) {
            // Gross Revenue (Member & Voucher Income)
            $grossRevenue = Transaction::where('coordinator_id', $coordinator->id)
                ->where('type', 'income')
                ->whereIn('category', ['Member Income', 'Voucher Income'])
                ->sum('amount');

            // Deductions (Based on actual transactions)
            $commission = Transaction::where('coordinator_id', $coordinator->id)
                ->where('category', 'Coordinator Commission')
                ->sum('amount');

            $ispShare = Transaction::where('coordinator_id', $coordinator->id)
                ->where('category', 'ISP Payment')
                ->sum('amount');

            $toolFund = Transaction::where('coordinator_id', $coordinator->id)
                ->where('category', 'Tool Fund')
                ->sum('amount');

            // Other Expenses (excluding automatically generated ones and fund usages)
            $expenses = Transaction::where('coordinator_id', $coordinator->id)
                ->where('type', 'expense')
                ->whereNotIn('category', ['Coordinator Commission', 'ISP Payment', 'Tool Fund', 'Pembayaran ISP', 'Pembelian Alat'])
                ->sum('amount');

            // Net Balance
            $netBalance = $grossRevenue - $commission - $ispShare - $toolFund - $expenses;

            $coordinatorSummaries[] = (object) [
                'id' => $coordinator->id,
                'name' => $coordinator->name,
                'gross_revenue' => $grossRevenue,
                'commission' => $commission,
                'isp_share' => $ispShare,
                'tools_cost' => $toolFund, // Using Tool Fund allocation
                'expenses' => $expenses,
                'net_balance' => $netBalance,
            ];
        }

        // Fetch Investors
        $investors = [];
        if (Auth::user()->hasRole('admin')) {
            $investors = \App\Models\Investor::all();
        } else {
            if ($userCoordinator) {
                $investors = \App\Models\Investor::where('coordinator_id', $userCoordinator->id)->get();
            }
        }

        return view('finance.index', compact('transactions', 'totalIncome', 'totalExpense', 'balance', 'coordinators', 'coordinatorSummaries', 'totalIspShare', 'totalToolFund', 'investors', 'totalInvestorFunds', 'totalGeneralExpenses', 'totalCompanyGrossShare'));
    }

    private function buildProfitLossData(?string $month): array
    {
        $query = Transaction::query();
        
        if ($month) {
            $query->whereMonth('transaction_date', date('m', strtotime($month)))
                  ->whereYear('transaction_date', date('Y', strtotime($month)));
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
            ->whereNotIn('category', ['Coordinator Commission', 'ISP Payment', 'Tool Fund', 'Pembayaran ISP', 'Pembelian Alat'])
            ->sum('amount');

        $serverExpenses = (clone $query)->where('type', 'expense')->where('category', 'Operational')->sum('amount');
        $transportExpenses = (clone $query)->where('type', 'expense')->where('category', 'Transport')->sum('amount');
        $consumptionExpenses = (clone $query)->where('type', 'expense')->where('category', 'Consumption')->sum('amount');
        $repairExpenses = (clone $query)->where('type', 'expense')->where('category', 'Repair')->sum('amount');

        $knownOperatingExpenses = $serverExpenses + $transportExpenses + $consumptionExpenses + $repairExpenses;
        $otherOperatingExpenses = $operatingExpenses - $knownOperatingExpenses;

        $netProfit = $grossProfit - $operatingExpenses;

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
            'netProfit' => $netProfit,
        ];
    }

    private function buildManagerReportData(?string $month): array
    {
        $query = Transaction::whereNotNull('coordinator_id');
        
        if ($month) {
            $query->whereMonth('transaction_date', date('m', strtotime($month)))
                  ->whereYear('transaction_date', date('Y', strtotime($month)));
        }

        $memberIncome = (clone $query)->where('type', 'income')->where('category', 'Member Income')->sum('amount');
        $voucherIncome = (clone $query)->where('type', 'income')->where('category', 'Voucher Income')->sum('amount');
        $totalRevenue = $memberIncome + $voucherIncome;

        $coordCommissionActual = (clone $query)->where('category', 'Coordinator Commission')->sum('amount');
        if ($coordCommissionActual > 0) {
            $coordCommission = $coordCommissionActual;
        } else {
            $coordRate = Setting::getValue('commission_coordinator_percent', 15);
            $coordCommission = $totalRevenue * ($coordRate / 100);
        }

        $afterCommission = $totalRevenue - $coordCommission;

        $serverExpenses = (clone $query)->where('type', 'expense')->where('category', 'Operational')->sum('amount');
        $transportExpenses = (clone $query)->where('type', 'expense')->where('category', 'Transport')->sum('amount');
        $consumptionExpenses = (clone $query)->where('type', 'expense')->where('category', 'Consumption')->sum('amount');
        $repairExpenses = (clone $query)->where('type', 'expense')->where('category', 'Repair')->sum('amount');

        $operatingExpenses = $transportExpenses + $consumptionExpenses + $repairExpenses;

        $depositToCompany = $afterCommission - $operatingExpenses;

        return [
            'memberIncome' => $memberIncome,
            'voucherIncome' => $voucherIncome,
            'totalRevenue' => $totalRevenue,
            'coordCommission' => $coordCommission,
            'afterCommission' => $afterCommission,
            'operatingExpenses' => $operatingExpenses,
            'transportExpenses' => $transportExpenses,
            'consumptionExpenses' => $consumptionExpenses,
            'repairExpenses' => $repairExpenses,
            'depositToCompany' => $depositToCompany,
        ];
    }

    public function profitLoss(Request $request)
    {
        if (!Auth::user()->hasRole('admin')) {
            abort(403, 'Unauthorized action.');
        }

        $month = $request->input('month');
        $data = $this->buildProfitLossData($month);

        return view('finance.profit_loss', array_merge($data, ['month' => $month]));
    }

    public function managerReport(Request $request)
    {
        if (!Auth::user()->hasRole('admin')) {
            abort(403, 'Unauthorized action.');
        }

        $month = $request->input('month');
        $data = $this->buildManagerReportData($month);

        return view('finance.manager_report', array_merge($data, ['month' => $month]));
    }

    public function downloadManagerReportPdf(Request $request)
    {
        if (!Auth::user()->hasRole('admin')) {
            abort(403, 'Unauthorized action.');
        }

        $month = $request->input('month');
        $data = $this->buildManagerReportData($month);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('finance.manager_report_pdf', array_merge($data, ['month' => $month]));
        
        $pdf->setPaper('a4', 'portrait');
        
        $fileName = 'Laporan_Keuangan_Pengurus';
        if ($month) {
            $fileName .= '_' . date('Y_m', strtotime($month));
        }
        $fileName .= '.pdf';
        
        return $pdf->stream($fileName, ['Attachment' => false]);
    }

    public function downloadManagerReportExcel(Request $request)
    {
        if (!Auth::user()->hasRole('admin')) {
            abort(403, 'Unauthorized action.');
        }

        $month = $request->input('month');
        $data = $this->buildManagerReportData($month);

        $fileName = 'laporan_pengurus';
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

            $writer->addRow(Row::fromValues(['Pendapatan Member', $data['memberIncome']]));
            $writer->addRow(Row::fromValues(['Pendapatan Voucher', $data['voucherIncome']]));
            $writer->addRow(Row::fromValues(['Total Pendapatan', $data['totalRevenue']]));
            $writer->addRow(Row::fromValues(['Komisi Pengurus', -1 * $data['coordCommission']]));
            $writer->addRow(Row::fromValues(['Sisa Setelah Komisi', $data['afterCommission']]));
            $writer->addRow(Row::fromValues(['Pengeluaran Transportasi', -1 * $data['transportExpenses']]));
            $writer->addRow(Row::fromValues(['Pengeluaran Konsumsi', -1 * $data['consumptionExpenses']]));
            $writer->addRow(Row::fromValues(['Pengeluaran Perbaikan', -1 * $data['repairExpenses']]));
            $writer->addRow(Row::fromValues(['Total Pengeluaran Pengurus', -1 * $data['operatingExpenses']]));
            $writer->addRow(Row::fromValues(['Total Sisa Disetor ke Perusahaan', $data['depositToCompany']]));

            $writer->close();
        }, $fileName);
    }

    public function downloadProfitLossPdf(Request $request)
    {
        if (!Auth::user()->hasRole('admin')) {
            abort(403, 'Unauthorized action.');
        }

        $month = $request->input('month');
        $data = $this->buildProfitLossData($month);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('finance.profit_loss', array_merge($data, ['month' => $month]));
        
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
        if (!Auth::user()->hasRole('admin')) {
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
            $writer->addRow(Row::fromValues(['Operating Expenses', -1 * $data['operatingExpenses']]));
            $writer->addRow(Row::fromValues(['Net Profit', $data['netProfit']]));

            $writer->close();
        }, $fileName);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:income,expense',
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

                // Cascade Calculation
                $gross = $validated['amount'];
                $coordAmount = $gross * ($coordRate / 100);
                $rem1 = $gross - $coordAmount;
                $ispAmount = $rem1 * ($ispRate / 100);
                $rem2 = $rem1 - $ispAmount;
                $toolAmount = $rem2 * ($toolRate / 100);
                $rem3 = $rem2 - $toolAmount;

                // Investor Share (50% of remaining)
                $investorId = $validated['investor_id'] ?? null;
                // If not explicitly set, try to find single investor for coordinator
                if (!$investorId) {
                    $investors = \App\Models\Investor::where('coordinator_id', $validated['coordinator_id'])->get();
                    if ($investors->count() === 1) {
                        $investorId = $investors->first()->id;
                    }
                }

                if ($investorId) {
                    $investorRate = Setting::getValue('commission_investor_percent', 50);
                    $investorAmount = $rem3 * ($investorRate / 100);

                    Transaction::create([
                        'user_id' => Auth::id(),
                        'type' => 'expense',
                        'category' => 'Investor Profit Share',
                        'amount' => $investorAmount,
                        'transaction_date' => $validated['transaction_date'],
                        'description' => $investorRate . '% Profit Share from transaction #' . $transaction->id,
                        'coordinator_id' => $validated['coordinator_id'],
                        'investor_id' => $investorId,
                        'reference_number' => 'INV-' . $transaction->id,
                    ]);
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
}
