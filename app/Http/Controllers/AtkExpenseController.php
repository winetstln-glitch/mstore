<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Account;
use App\Services\AccountingPoster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AtkExpenseController extends Controller
{
    public function index()
    {
        $expenses = Transaction::where('type', 'expense')
            ->where('reference_number', 'like', 'ATK-EXP-%')
            ->latest('transaction_date')
            ->paginate(15);
        return view('atk.expenses.index', compact('expenses'));
    }

    public function create()
    {
        return view('atk.expenses.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'transaction_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'description' => 'required|string|max:255',
        ]);

        Transaction::create([
            'user_id' => Auth::id(),
            'type' => 'expense',
            'category' => 'Pengeluaran Pengurus',
            'amount' => $data['amount'],
            'description' => $data['description'],
            'transaction_date' => $data['transaction_date'],
            'reference_number' => 'ATK-EXP-' . now()->format('YmdHis'),
        ]);

        $expAccId = Account::where('code', '6004')->value('id');
        $cashAccId = Account::where('code', '1001')->value('id');
        if ($expAccId && $cashAccId) {
            $poster = app(AccountingPoster::class);
            $poster->post(
                'ATK-EXP-' . now()->format('YmdHis'),
                $data['transaction_date'],
                $data['description'],
                [
                    ['account_id' => $expAccId, 'debit' => $data['amount'], 'credit' => 0, 'unit' => 'ATK'],
                    ['account_id' => $cashAccId, 'debit' => 0, 'credit' => $data['amount'], 'unit' => 'ATK'],
                ],
                null,
                'atk_expense',
                null
            );
        }

        return redirect()->route('atk.expenses.index')->with('success', 'Pengeluaran berhasil dicatat.');
    }
}
