<?php

namespace App\Http\Controllers;

use App\Models\Coordinator;
use App\Models\Investor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InvestorController extends Controller
{
    public function index()
    {
        $query = Investor::with('coordinator')
            ->withSum('incomeTransactions', 'amount')
            ->withSum('expenseTransactions', 'amount');

        if (!Auth::user()->hasRole('admin')) {
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
        if (Auth::user()->hasRole('admin')) {
            $coordinators = Coordinator::all();
        } else {
            $coordinators = Coordinator::where('user_id', Auth::id())->get();
        }
        return view('investors.create', compact('coordinators'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'coordinator_id' => 'required|exists:coordinators,id',
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'description' => 'nullable|string',
        ]);

        Investor::create($validated);

        return redirect()->route('investors.index')->with('success', 'Investor created successfully.');
    }

    public function show(Investor $investor)
    {
        if (!Auth::user()->hasRole('admin')) {
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
        if (!Auth::user()->hasRole('admin')) {
             $coordinator = Coordinator::where('user_id', Auth::id())->first();
             if (!$coordinator || $investor->coordinator_id !== $coordinator->id) {
                 abort(403);
             }
        }

        $coordinators = [];
        if (Auth::user()->hasRole('admin')) {
            $coordinators = Coordinator::all();
        } else {
            $coordinators = Coordinator::where('user_id', Auth::id())->get();
        }
        return view('investors.edit', compact('investor', 'coordinators'));
    }

    public function update(Request $request, Investor $investor)
    {
        if (!Auth::user()->hasRole('admin')) {
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
        if (!Auth::user()->hasRole('admin')) {
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
}
