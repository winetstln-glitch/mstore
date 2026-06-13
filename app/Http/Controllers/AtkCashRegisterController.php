<?php

namespace App\Http\Controllers;

use App\Models\AtkCashRegister;
use App\Models\AtkCashMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AtkCashRegisterController extends Controller
{
    public function index()
    {
        $registers = AtkCashRegister::with('user')->latest()->paginate(20);
        return view('atk.cash-registers.index', compact('registers'));
    }

    public function create()
    {
        return view('atk.cash-registers.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'opening_balance' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $register = AtkCashRegister::create([
                'user_id' => Auth::id(),
                'name' => $request->name,
                'opening_balance' => $request->opening_balance,
                'closing_balance' => $request->opening_balance,
                'status' => 'open',
                'opened_at' => now(),
            ]);

            // Create opening movement
            AtkCashMovement::create([
                'atk_cash_register_id' => $register->id,
                'movement_type' => 'opening',
                'amount' => $request->opening_balance,
                'balance_before' => 0,
                'balance_after' => $request->opening_balance,
                'description' => 'Buka shift',
                'created_by' => Auth::id(),
            ]);

            DB::commit();
            return redirect()->route('atk.dashboard')->with('success', 'Shift kasir berhasil dibuka!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal membuka shift: ' . $e->getMessage());
        }
    }

    public function close(Request $request, AtkCashRegister $register)
    {
        if ($register->status !== 'open') {
            return back()->with('error', 'Shift sudah ditutup!');
        }

        $request->validate([
            'closing_balance' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $register->update([
                'closing_balance' => $request->closing_balance,
                'status' => 'closed',
                'closed_at' => now(),
            ]);

            // Create closing movement
            AtkCashMovement::create([
                'atk_cash_register_id' => $register->id,
                'movement_type' => 'closing',
                'amount' => $request->closing_balance - $register->closing_balance,
                'balance_before' => $register->closing_balance,
                'balance_after' => $request->closing_balance,
                'description' => 'Tutup shift',
                'created_by' => Auth::id(),
            ]);

            DB::commit();
            return redirect()->route('atk.dashboard')->with('success', 'Shift kasir berhasil ditutup!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menutup shift: ' . $e->getMessage());
        }
    }

    public function show(AtkCashRegister $register)
    {
        $register->load(['movements' => function($q) {
            $q->latest();
        }, 'user']);
        return view('atk.cash-registers.show', compact('register'));
    }
}
