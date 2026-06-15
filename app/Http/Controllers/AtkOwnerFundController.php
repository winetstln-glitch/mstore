<?php

namespace App\Http\Controllers;

use App\Models\Cash;
use App\Models\OwnerFund;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AtkOwnerFundController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:atk.manage', only: ['index', 'create', 'store', 'show', 'destroy']),
        ];
    }

    public function index()
    {
        $funds = OwnerFund::latest()->paginate(15);
        $currentBalance = optional(OwnerFund::latest()->first())->balance ?? 0;
        return view('atk.owner-funds.index', compact('funds', 'currentBalance'));
    }

    public function create()
    {
        return view('atk.owner-funds.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'transaction_date' => 'required|date',
            'type' => 'required|in:loan,repayment',
            'amount' => 'required|numeric|min:1',
            'description' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $lastFund = OwnerFund::latest()->first();
            $currentBalance = $lastFund ? $lastFund->balance : 0;
            $newBalance = $data['type'] === 'loan' 
                ? $currentBalance + $data['amount'] 
                : $currentBalance - $data['amount'];

            OwnerFund::create([
                'transaction_code' => 'OWNER-FUND-'.now()->format('YmdHis'),
                'transaction_date' => $data['transaction_date'],
                'type' => $data['type'],
                'amount' => $data['amount'],
                'balance' => $newBalance,
                'description' => $data['description'],
                'created_by' => Auth::id(),
                'status' => 'approved', // Auto-approved for now
                'approved_by' => Auth::id(),
            ]);

            // Update Kas Utama
            $cash = Cash::firstOrCreate(['name' => 'Kas Utama'], ['balance' => 0]);
            if ($data['type'] === 'loan') {
                $cash->balance += $data['amount'];
            } else {
                $cash->balance -= $data['amount'];
            }
            $cash->save();

            DB::commit();
            return redirect()->route('atk.owner-funds.index')->with('success', 'Transaksi dana talangan berhasil dicatat.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    public function show(OwnerFund $fund)
    {
        return view('atk.owner-funds.show', compact('fund'));
    }

    public function destroy(OwnerFund $fund)
    {
        DB::beginTransaction();
        try {
            // Reverse cash change
            $cash = Cash::firstOrCreate(['name' => 'Kas Utama'], ['balance' => 0]);
            if ($fund->type === 'loan') {
                $cash->balance -= $fund->amount;
            } else {
                $cash->balance += $fund->amount;
            }
            $cash->save();

            // Delete the fund
            $fund->delete();

            DB::commit();
            return redirect()->route('atk.owner-funds.index')->with('success', 'Transaksi dana talangan berhasil dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }
}
