<?php

namespace App\Http\Controllers;

use App\Models\WashShift;
use App\Models\WashShiftSession;
use App\Models\WashCashRegister;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;

class WashShiftSessionController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:wash.shift.view', only: ['index', 'show']),
            new Middleware('permission:wash.shift.open', only: ['create', 'store']),
            new Middleware('permission:wash.shift.close', only: ['edit', 'update']),
        ];
    }

    public function index()
    {
        $sessions = WashShiftSession::with('shift', 'user', 'cashRegister')
            ->latest('opened_at')
            ->paginate(20);
        return view('wash.shift-sessions.index', compact('sessions'));
    }

    public function create()
    {
        $shifts = WashShift::where('is_active', true)->orderBy('name')->get();
        $cashRegisters = WashCashRegister::where('is_active', true)->orderBy('name')->get();
        return view('wash.shift-sessions.create', compact('shifts', 'cashRegisters'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'wash_shift_id' => 'nullable|exists:wash_shifts,id',
            'wash_cash_register_id' => 'nullable|exists:wash_cash_registers,id',
            'opening_cash' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        WashShiftSession::create([
            'wash_shift_id' => $request->wash_shift_id,
            'user_id' => auth()->id(),
            'wash_cash_register_id' => $request->wash_cash_register_id,
            'opened_at' => now(),
            'opening_cash' => $request->opening_cash,
            'notes' => $request->notes,
            'status' => 'open',
        ]);

        return redirect()->route('wash.shift-sessions.index')->with('success', 'Sesi shift berhasil dibuka!');
    }

    public function show(WashShiftSession $session)
    {
        $session->load('shift', 'user', 'cashRegister', 'transactions', 'cashMovements');
        return view('wash.shift-sessions.show', compact('session'));
    }

    public function edit(WashShiftSession $session)
    {
        if ($session->status !== 'open') {
            return redirect()->route('wash.shift-sessions.index')->with('error', 'Sesi shift sudah ditutup!');
        }
        return view('wash.shift-sessions.edit', compact('session'));
    }

    public function update(Request $request, WashShiftSession $session)
    {
        if ($session->status !== 'open') {
            return redirect()->route('wash.shift-sessions.index')->with('error', 'Sesi shift sudah ditutup!');
        }

        $request->validate([
            'closing_cash' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($request, $session) {
            $totalSales = $session->transactions()->sum('total_amount');
            $totalExpenses = $session->cashMovements()->where('type', 'out')->sum('amount');
            $cashDifference = $request->closing_cash - ($session->opening_cash + $totalSales - $totalExpenses);

            $session->update([
                'closed_at' => now(),
                'closing_cash' => $request->closing_cash,
                'total_sales' => $totalSales,
                'total_expenses' => $totalExpenses,
                'cash_difference' => $cashDifference,
                'notes' => $request->notes,
                'status' => 'closed',
            ]);
        });

        return redirect()->route('wash.shift-sessions.index')->with('success', 'Sesi shift berhasil ditutup!');
    }
}
