<?php

namespace App\Http\Controllers;

use App\Models\Coordinator;
use App\Models\Investor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Barryvdh\DomPDF\Facade\Pdf;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

class InvestorController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:investor.view', only: ['index', 'show', 'exportPdf', 'exportExcel']),
            new Middleware('permission:investor.create', only: ['create', 'store']),
            new Middleware('permission:investor.edit', only: ['edit', 'update']),
            new Middleware('permission:investor.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request)
    {
        $query = Investor::with('coordinator');

        $month = $request->input('month');

        if ($month) {
            $query->withSum(['incomeTransactions' => function ($q) use ($month) {
                $q->whereMonth('transaction_date', date('m', strtotime($month)))
                  ->whereYear('transaction_date', date('Y', strtotime($month)));
            }], 'amount');

            $query->withSum(['expenseTransactions' => function ($q) use ($month) {
                $q->whereMonth('transaction_date', date('m', strtotime($month)))
                  ->whereYear('transaction_date', date('Y', strtotime($month)));
            }], 'amount');
        } else {
            $query->withSum('incomeTransactions', 'amount')
                  ->withSum('expenseTransactions', 'amount');
        }

        if (!Auth::user()->hasRole('admin') && !Auth::user()->hasRole('finance')) {
            $coordinator = Coordinator::where('user_id', Auth::id())->first();
            if ($coordinator) {
                $query->where('coordinator_id', $coordinator->id);
            }
        }

        $investors = $query->latest()->paginate(10);
        return view('investors.index', compact('investors'));
    }

    public function create()
    {
        $coordinators = [];
        if (Auth::user()->hasRole('admin') || Auth::user()->hasRole('finance')) {
            $coordinators = Coordinator::all();
            $existingInvestors = Investor::orderBy('name')->get();
        } else {
            $coordinators = Coordinator::where('user_id', Auth::id())->get();
            $coordinatorIds = $coordinators->pluck('id');
            $existingInvestors = Investor::whereIn('coordinator_id', $coordinatorIds)->orderBy('name')->get();
        }
        return view('investors.create', compact('coordinators', 'existingInvestors'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'coordinator_id' => 'required|exists:coordinators,id',
            'mode' => 'required|in:new,select',
            'source_investor_id' => 'nullable|exists:investors,id',
            'name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'description' => 'nullable|string',
        ]);

        if ($validated['mode'] === 'select' && $validated['source_investor_id']) {
            $source = Investor::findOrFail($validated['source_investor_id']);
            Investor::create([
                'coordinator_id' => $validated['coordinator_id'],
                'name' => $source->name,
                'phone' => $source->phone,
                'description' => $source->description,
            ]);
        } else {
            $request->validate([
                'name' => 'required|string|max:255',
            ]);

            Investor::create([
                'coordinator_id' => $validated['coordinator_id'],
                'name' => $request->input('name'),
                'phone' => $validated['phone'],
                'description' => $validated['description'],
            ]);
        }

        return redirect()->route('investors.index')->with('success', 'Investor created successfully.');
    }

    public function show(Investor $investor)
    {
        if (!Auth::user()->hasRole('admin') && !Auth::user()->hasRole('finance')) {
             $coordinator = Coordinator::where('user_id', Auth::id())->first();
             if (!$coordinator || $investor->coordinator_id !== $coordinator->id) {
                 abort(403);
             }
        }

        $transactions = $investor->transactions()->latest('transaction_date')->paginate(15);
        
        $totalCapital = $investor->transactions()->where('type', 'income')->sum('amount');
        $totalWithdrawal = $investor->transactions()->where('type', 'expense')->sum('amount');
        $balance = $totalCapital - $totalWithdrawal;

        return view('investors.show', compact('investor', 'transactions', 'balance', 'totalCapital', 'totalWithdrawal'));
    }

    public function edit(Investor $investor)
    {
        if (!Auth::user()->hasRole('admin') && !Auth::user()->hasRole('finance')) {
             $coordinator = Coordinator::where('user_id', Auth::id())->first();
             if (!$coordinator || $investor->coordinator_id !== $coordinator->id) {
                 abort(403);
             }
        }

        $coordinators = [];
        if (Auth::user()->hasRole('admin') || Auth::user()->hasRole('finance')) {
            $coordinators = Coordinator::all();
        } else {
            $coordinators = Coordinator::where('user_id', Auth::id())->get();
        }
        return view('investors.edit', compact('investor', 'coordinators'));
    }

    public function update(Request $request, Investor $investor)
    {
        if (!Auth::user()->hasRole('admin') && !Auth::user()->hasRole('finance')) {
             $coordinator = Coordinator::where('user_id', Auth::id())->first();
             if (!$coordinator || $investor->coordinator_id !== $coordinator->id) {
                 abort(403);
             }
        }

        $validated = $request->validate([
            'coordinator_id' => 'required|exists:coordinators,id',
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'description' => 'nullable|string',
        ]);

        $investor->update($validated);

        return redirect()->route('investors.index')->with('success', 'Investor updated successfully.');
    }

    public function destroy(Investor $investor)
    {
        if (!Auth::user()->hasRole('admin') && !Auth::user()->hasRole('finance')) {
             $coordinator = Coordinator::where('user_id', Auth::id())->first();
             if (!$coordinator || $investor->coordinator_id !== $coordinator->id) {
                 abort(403);
             }
        }

        if ($investor->transactions()->exists()) {
             return back()->with('error', 'Cannot delete investor with existing transactions.');
        }

        $investor->delete();

        return redirect()->route('investors.index')->with('success', 'Investor deleted successfully.');
    }

    public function exportPdf(Request $request)
    {
        $query = Investor::with('coordinator');

        $month = $request->input('month');

        if ($month) {
            $query->withSum(['incomeTransactions' => function ($q) use ($month) {
                $q->whereMonth('transaction_date', date('m', strtotime($month)))
                  ->whereYear('transaction_date', date('Y', strtotime($month)));
            }], 'amount');

            $query->withSum(['expenseTransactions' => function ($q) use ($month) {
                $q->whereMonth('transaction_date', date('m', strtotime($month)))
                  ->whereYear('transaction_date', date('Y', strtotime($month)));
            }], 'amount');
        } else {
            $query->withSum('incomeTransactions', 'amount')
                  ->withSum('expenseTransactions', 'amount');
        }

        if (!Auth::user()->hasRole('admin') && !Auth::user()->hasRole('finance')) {
            $coordinator = Coordinator::where('user_id', Auth::id())->first();
            if ($coordinator) {
                $query->where('coordinator_id', $coordinator->id);
            }
        }

        $investors = $query->latest()->get();

        $pdf = Pdf::loadView('investors.pdf', compact('investors', 'month'));
        return $pdf->download('investors' . ($month ? '_' . $month : '') . '.pdf');
    }

    public function exportExcel(Request $request)
    {
        $query = Investor::with('coordinator');

        $month = $request->input('month');

        if ($month) {
            $query->withSum(['incomeTransactions' => function ($q) use ($month) {
                $q->whereMonth('transaction_date', date('m', strtotime($month)))
                  ->whereYear('transaction_date', date('Y', strtotime($month)));
            }], 'amount');

            $query->withSum(['expenseTransactions' => function ($q) use ($month) {
                $q->whereMonth('transaction_date', date('m', strtotime($month)))
                  ->whereYear('transaction_date', date('Y', strtotime($month)));
            }], 'amount');
        } else {
            $query->withSum('incomeTransactions', 'amount')
                  ->withSum('expenseTransactions', 'amount');
        }

        if (!Auth::user()->hasRole('admin') && !Auth::user()->hasRole('finance')) {
            $coordinator = Coordinator::where('user_id', Auth::id())->first();
            if ($coordinator) {
                $query->where('coordinator_id', $coordinator->id);
            }
        }

        $investors = $query->latest()->get();

        return response()->streamDownload(function () use ($investors) {
            $writer = new Writer();
            $writer->openToFile('php://output');

            $writer->addRow(Row::fromValues([
                'Name',
                'Coordinator',
                'Phone',
                'Total Investment',
                'Net Balance',
            ]));

            foreach ($investors as $investor) {
                $totalInvestment = $investor->income_transactions_sum_amount ?? 0;
                $totalExpense = $investor->expense_transactions_sum_amount ?? 0;
                $netBalance = $totalInvestment - $totalExpense;

                $writer->addRow(Row::fromValues([
                    $investor->name,
                    $investor->coordinator->name ?? '-',
                    $investor->phone ?? '-',
                    number_format($totalInvestment, 0, ',', '.'),
                    number_format($netBalance, 0, ',', '.'),
                ]));
            }

            $writer->close();
        }, 'investors' . ($month ? '_' . $month : '') . '.xlsx');
    }
}
