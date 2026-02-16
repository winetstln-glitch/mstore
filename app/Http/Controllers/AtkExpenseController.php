<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
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

        return redirect()->route('atk.expenses.index')->with('success', 'Pengeluaran berhasil dicatat.');
    }
}
