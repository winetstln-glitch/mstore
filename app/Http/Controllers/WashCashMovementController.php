<?php

namespace App\Http\Controllers;

use App\Models\WashCashRegister;
use App\Models\WashCashMovement;
use App\Models\WashShiftSession;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;

class WashCashMovementController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:wash.cash.view', only: ['index', 'show']),
            new Middleware('permission:wash.cash.manage', only: ['create', 'store']),
        ];
    }

    public function index()
    {
        $movements = WashCashMovement::with('cashRegister', 'user', 'shiftSession')
            ->latest('movement_date')
            ->paginate(20);
        return view('wash.cash-movements.index', compact('movements'));
    }

    public function create()
    {
        $registers = WashCashRegister::where('is_active', true)->orderBy('name')->get();
        $activeSessions = WashShiftSession::where('status', 'open')->with('cashRegister')->get();
        return view('wash.cash-movements.create', compact('registers', 'activeSessions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'wash_cash_register_id' => 'required|exists:wash_cash_registers,id',
            'type' => 'required|in:in,out',
            'amount' => 'required|numeric|min:0',
            'description' => 'required|string',
            'movement_date' => 'required|date',
        ]);

        DB::transaction(function () use ($request) {
            $register = WashCashRegister::find($request->wash_cash_register_id);
            
            if ($request->type === 'out' && $request->amount > $register->current_balance) {
                throw new \Exception('Saldo kas tidak mencukupi!');
            }

            if ($request->type === 'in') {
                $register->increment('current_balance', $request->amount);
            } else {
                $register->decrement('current_balance', $request->amount);
            }

            $activeSession = WashShiftSession::where('status', 'open')
                ->where('wash_cash_register_id', $request->wash_cash_register_id)
                ->first();

            WashCashMovement::create([
                'wash_cash_register_id' => $request->wash_cash_register_id,
                'user_id' => auth()->id(),
                'wash_shift_session_id' => $activeSession?->id,
                'type' => $request->type,
                'amount' => $request->amount,
                'reference_no' => 'MVT-' . date('YmdHis'),
                'description' => $request->description,
                'movement_date' => $request->movement_date,
            ]);
        });

        return redirect()->route('wash.cash-movements.index')->with('success', 'Mutasi kas berhasil dicatat!');
    }

    public function show(WashCashMovement $movement)
    {
        $movement->load('cashRegister', 'user', 'shiftSession');
        return view('wash.cash-movements.show', compact('movement'));
    }
}
