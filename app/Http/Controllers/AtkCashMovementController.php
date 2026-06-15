<?php

namespace App\Http\Controllers;

use App\Models\AtkCashMovement;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class AtkCashMovementController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:atk.manage', only: ['index', 'create', 'store']),
        ];
    }

    public function index(Request $request)
    {
        $query = AtkCashMovement::with(['creator'])->orderBy('created_at', 'desc');

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }
        if ($request->filled('movement_type')) {
            $query->where('movement_type', $request->movement_type);
        }
        if ($request->filled('created_by')) {
            $query->where('created_by', $request->created_by);
        }

        $movements = $query->paginate(20);
        $users = User::orderBy('name')->get();

        $types = ['sale', 'service', 'expense', 'transfer', 'withdrawal', 'topup', 'ppob', 'owner_loan', 'owner_repayment', 'adjustment'];

        return view('atk.cash-movements.index', compact('movements', 'users', 'types'));
    }

    public function create()
    {
        return view('atk.cash-movements.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:255',
        ]);

        $cash = \App\Models\Cash::firstOrCreate(['name' => 'Kas Utama'], ['balance' => 0]);
        $balanceBefore = $cash->balance;

        \DB::beginTransaction();
        try {
            AtkCashMovement::create([
            'atk_cash_register_id' => null,
            'movement_type' => 'adjustment',
            'amount' => $request->amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $request->amount,
            'description' => $request->description ?? 'Penyesuaian Saldo Kas',
            'reference_type' => null,
            'reference_id' => null,
            'created_by' => auth()->id(),
        ]);

            $cash->balance = $request->amount;
            $cash->save();

            \DB::commit();

            return redirect()->route('atk.cash-movements.index')->with('success', 'Saldo Kas berhasil disesuaikan!');
        } catch (\Exception $e) {
            \DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }
}
