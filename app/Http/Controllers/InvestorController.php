<?php

namespace App\Http\Controllers;

use App\Models\Coordinator;
use App\Models\Investor;
use App\Models\Role;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
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
        $query = Investor::with(['coordinator', 'user']);

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

        if (! Auth::user()->hasRole('admin') && ! Auth::user()->hasRole('finance')) {
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

        $linkedUserIds = Investor::whereNotNull('user_id')->pluck('user_id')->filter()->all();
        $availableUsers = User::query()
            ->whereHas('role', fn ($q) => $q->where('name', 'customer'))
            ->when(! empty($linkedUserIds), fn ($q) => $q->whereNotIn('id', $linkedUserIds))
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'username']);

        return view('investors.create', compact('coordinators', 'existingInvestors', 'availableUsers'));
    }

    public function store(Request $request)
    {
        $rules = [
            'coordinator_id' => 'required|exists:coordinators,id',
            'mode' => 'required|in:new,select',
            'source_investor_id' => 'nullable|exists:investors,id',
            'name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'description' => 'nullable|string',
            'user_option' => 'sometimes|in:existing,new',
        ];

        if ($request->input('mode') === 'select') {
            $rules['source_investor_id'] = 'required|exists:investors,id';
        } else {
            $rules['name'] = 'required|string|max:255';
        }

        if ($request->input('user_option') === 'new') {
            $rules['username'] = 'required|string|max:255|unique:users,username';
            $rules['email'] = 'nullable|string|email|max:255|unique:users,email';
            $rules['password'] = ['required', 'confirmed', Rules\Password::defaults()];
        } else {
            $rules['user_id'] = 'nullable|exists:users,id|unique:investors,user_id';
        }

        $validated = $request->validate($rules);
        $userId = $validated['user_id'] ?? null;

        if ($request->input('user_option') === 'new') {
            $role = Role::where('name', 'customer')->first();
            $user = User::create([
                'name' => $validated['name'] ?? optional(Investor::find($validated['source_investor_id'] ?? null))->name ?? 'Investor',
                'username' => $validated['username'],
                'email' => $validated['email'] ?? null,
                'password' => Hash::make($validated['password']),
                'phone' => $validated['phone'] ?? null,
                'role_id' => $role?->id,
                'is_active' => true,
            ]);
            $userId = $user->id;
        }

        if ($validated['mode'] === 'select' && $validated['source_investor_id']) {
            $source = Investor::findOrFail($validated['source_investor_id']);
            Investor::create([
                'coordinator_id' => $validated['coordinator_id'],
                'user_id' => $userId,
                'name' => $source->name,
                'phone' => $source->phone,
                'description' => $source->description,
            ]);
        } else {
            Investor::create([
                'coordinator_id' => $validated['coordinator_id'],
                'user_id' => $userId,
                'name' => $validated['name'],
                'phone' => $validated['phone'] ?? null,
                'description' => $validated['description'] ?? null,
            ]);
        }

        return redirect()->route('investors.index')->with('success', 'Investor berhasil ditambahkan.');
    }

    public function show(Investor $investor)
    {
        if (! Auth::user()->hasRole('admin') && ! Auth::user()->hasRole('finance')) {
            $coordinator = Coordinator::where('user_id', Auth::id())->first();
            if (! $coordinator || $investor->coordinator_id !== $coordinator->id) {
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
        if (! Auth::user()->hasRole('admin') && ! Auth::user()->hasRole('finance')) {
            $coordinator = Coordinator::where('user_id', Auth::id())->first();
            if (! $coordinator || $investor->coordinator_id !== $coordinator->id) {
                abort(403);
            }
        }

        $coordinators = [];
        if (Auth::user()->hasRole('admin') || Auth::user()->hasRole('finance')) {
            $coordinators = Coordinator::all();
        } else {
            $coordinators = Coordinator::where('user_id', Auth::id())->get();
        }

        $linkedUserIds = Investor::whereNotNull('user_id')
            ->where('id', '!=', $investor->id)
            ->pluck('user_id')
            ->filter()
            ->all();
        $availableUsers = User::query()
            ->whereHas('role', fn ($q) => $q->where('name', 'customer'))
            ->when(! empty($linkedUserIds), fn ($q) => $q->whereNotIn('id', $linkedUserIds))
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'username']);

        return view('investors.edit', compact('investor', 'coordinators', 'availableUsers'));
    }

    public function update(Request $request, Investor $investor)
    {
        if (! Auth::user()->hasRole('admin') && ! Auth::user()->hasRole('finance')) {
            $coordinator = Coordinator::where('user_id', Auth::id())->first();
            if (! $coordinator || $investor->coordinator_id !== $coordinator->id) {
                abort(403);
            }
        }

        $rules = [
            'coordinator_id' => 'required|exists:coordinators,id',
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'description' => 'nullable|string',
            'user_option' => 'sometimes|in:existing,new',
        ];

        if ($request->input('user_option') === 'new') {
            $rules['username'] = 'required|string|max:255|unique:users,username';
            $rules['email'] = 'nullable|string|email|max:255|unique:users,email';
            $rules['password'] = ['required', 'confirmed', Rules\Password::defaults()];
        } else {
            $rules['user_id'] = 'nullable|exists:users,id|unique:investors,user_id,'.$investor->id;
        }

        $validated = $request->validate($rules);
        $userId = $validated['user_id'] ?? null;

        if ($request->input('user_option') === 'new') {
            $role = Role::where('name', 'customer')->first();
            $user = User::create([
                'name' => $validated['name'],
                'username' => $validated['username'],
                'email' => $validated['email'] ?? null,
                'password' => Hash::make($validated['password']),
                'phone' => $validated['phone'] ?? null,
                'role_id' => $role?->id,
                'is_active' => true,
            ]);
            $userId = $user->id;
        }

        $validated['user_id'] = $userId;
        unset($validated['user_option'], $validated['username'], $validated['email'], $validated['password'], $validated['password_confirmation']);

        $investor->update($validated);

        return redirect()->route('investors.index')->with('success', 'Investor berhasil diperbarui.');
    }

    public function destroy(Investor $investor)
    {
        if (! Auth::user()->hasRole('admin') && ! Auth::user()->hasRole('finance')) {
            $coordinator = Coordinator::where('user_id', Auth::id())->first();
            if (! $coordinator || $investor->coordinator_id !== $coordinator->id) {
                abort(403);
            }
        }

        if ($investor->transactions()->exists()) {
            return back()->with('error', 'Investor tidak dapat dihapus karena masih memiliki transaksi.');
        }

        $investor->delete();

        return redirect()->route('investors.index')->with('success', 'Investor berhasil dihapus.');
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

        if (! Auth::user()->hasRole('admin') && ! Auth::user()->hasRole('finance')) {
            $coordinator = Coordinator::where('user_id', Auth::id())->first();
            if ($coordinator) {
                $query->where('coordinator_id', $coordinator->id);
            }
        }

        $investors = $query->latest()->get();

        $pdf = Pdf::loadView('investors.pdf', compact('investors', 'month'));

        return $pdf->stream('investors'.($month ? '_'.$month : '').'.pdf', ['Attachment' => false]);
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

        if (! Auth::user()->hasRole('admin') && ! Auth::user()->hasRole('finance')) {
            $coordinator = Coordinator::where('user_id', Auth::id())->first();
            if ($coordinator) {
                $query->where('coordinator_id', $coordinator->id);
            }
        }

        $investors = $query->latest()->get();

        return response()->streamDownload(function () use ($investors) {
            $writer = new Writer;
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
        }, 'investors'.($month ? '_'.$month : '').'.xlsx');
    }
}
