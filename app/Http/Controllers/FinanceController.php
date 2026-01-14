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

class FinanceController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:finance.view', only: ['index', 'show']),
            new Middleware('permission:finance.manage', only: ['create', 'store', 'edit', 'update', 'destroy', 'storeCoordinatorIncome']),
        ];
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

    public function profitLoss(Request $request)
    {
        if (!Auth::user()->hasRole('admin')) {
            abort(403, 'Unauthorized action.');
        }

        $query = Transaction::query();
        
        if ($request->has('month') && $request->month) {
            $query->whereMonth('transaction_date', date('m', strtotime($request->month)))
                  ->whereYear('transaction_date', date('Y', strtotime($request->month)));
        }

        // 1. Revenue
        $memberIncome = (clone $query)->where('category', 'Member Income')->sum('amount');
        $voucherIncome = (clone $query)->where('category', 'Voucher Income')->sum('amount');
        $otherIncome = (clone $query)->where('type', 'income')
            ->whereNotIn('category', ['Member Income', 'Voucher Income'])
            ->sum('amount');
        
        $totalRevenue = $memberIncome + $voucherIncome + $otherIncome;

        // 2. Variable Costs (COGS)
        $coordCommission = (clone $query)->where('category', 'Coordinator Commission')->sum('amount');
        $ispPayment = (clone $query)->where('category', 'ISP Payment')->sum('amount');
        $toolFund = (clone $query)->where('category', 'Tool Fund')->sum('amount');
        
        $totalCOGS = $coordCommission + $ispPayment + $toolFund;

        // 3. Gross Profit
        $grossProfit = $totalRevenue - $totalCOGS;

        // 4. Operating Expenses
        $operatingExpenses = (clone $query)->where('type', 'expense')
            ->whereNotIn('category', ['Coordinator Commission', 'ISP Payment', 'Tool Fund', 'Pembayaran ISP', 'Pembelian Alat'])
            ->sum('amount');

        // 5. Net Profit
        $netProfit = $grossProfit - $operatingExpenses;

        return view('finance.profit_loss', compact(
            'memberIncome', 'voucherIncome', 'otherIncome', 'totalRevenue',
            'coordCommission', 'ispPayment', 'toolFund', 'totalCOGS',
            'grossProfit', 'operatingExpenses', 'netProfit'
        ));
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
