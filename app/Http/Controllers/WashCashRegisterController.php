<?php

namespace App\Http\Controllers;

use App\Models\WashCashRegister;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class WashCashRegisterController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:wash.cash.view', only: ['index', 'show']),
            new Middleware('permission:wash.cash.manage', only: ['create', 'store', 'edit', 'update', 'destroy']),
        ];
    }

    public function index()
    {
        $registers = WashCashRegister::orderBy('name')->paginate(20);
        return view('wash.cash-registers.index', compact('registers'));
    }

    public function create()
    {
        return view('wash.cash-registers.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'current_balance' => 'required|numeric|min:0',
        ]);

        WashCashRegister::create([
            'name' => $request->name,
            'code' => 'CASH-' . str_pad(WashCashRegister::max('id') + 1, 4, '0', STR_PAD_LEFT),
            'description' => $request->description,
            'current_balance' => $request->current_balance,
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()->route('wash.cash-registers.index')->with('success', 'Kasir berhasil ditambahkan!');
    }

    public function show(WashCashRegister $register)
    {
        $movements = $register->cashMovements()->latest('movement_date')->paginate(20);
        $sessions = $register->sessions()->latest('opened_at')->limit(10)->get();
        return view('wash.cash-registers.show', compact('register', 'movements', 'sessions'));
    }

    public function edit(WashCashRegister $register)
    {
        return view('wash.cash-registers.edit', compact('register'));
    }

    public function update(Request $request, WashCashRegister $register)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'current_balance' => 'required|numeric|min:0',
        ]);

        $register->update([
            'name' => $request->name,
            'description' => $request->description,
            'current_balance' => $request->current_balance,
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()->route('wash.cash-registers.index')->with('success', 'Kasir berhasil diupdate!');
    }

    public function destroy(WashCashRegister $register)
    {
        $register->delete();
        return redirect()->route('wash.cash-registers.index')->with('success', 'Kasir berhasil dihapus!');
    }
}
