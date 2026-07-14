<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AtkCashMovement;
use App\Models\Cash;
use App\Models\Transaction;
use App\Services\AccountingPoster;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AtkExpenseController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:atk.report', only: ['index']),
            new Middleware('permission:atk.manage', only: ['create', 'store', 'edit', 'update', 'destroy']),
        ];
    }

    private function queryAtkExpenses()
    {
        return Transaction::where('type', 'expense')
            ->where('reference_number', 'like', 'ATK-EXP-%');
    }

    public function index()
    {
        $expenses = $this->queryAtkExpenses()
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
            'amount' => 'required|numeric|min:1',
            'description' => 'required|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            $referenceNumber = 'ATK-EXP-'.now()->format('YmdHis');
            
            $expense = Transaction::create([
                'user_id' => Auth::id(),
                'type' => 'expense',
                'category' => 'Pengeluaran Pengurus',
                'amount' => $data['amount'],
                'description' => $data['description'],
                'transaction_date' => $data['transaction_date'],
                'reference_number' => $referenceNumber,
            ]);

            // Update Kas Utama
            $cash = Cash::firstOrCreate(['name' => 'Kas Utama'], ['balance' => 0]);
            $balanceBefore = $cash->balance;
            $cash->balance = (float) $cash->balance - (float) $data['amount'];
            $cash->save();

            // Create AtkCashMovement
            AtkCashMovement::create([
                'atk_cash_register_id' => null,
                'movement_type' => 'expense',
                'amount' => $data['amount'],
                'balance_before' => $balanceBefore,
                'balance_after' => $cash->balance,
                'reference_type' => 'transaction',
                'reference_id' => $expense->id,
                'description' => 'Pengeluaran ATK - ' . $data['description'],
                'created_by' => Auth::id(),
            ]);

            // Jurnal Akuntansi
            $expAccId = Account::where('code', '6004')->value('id');
            $cashAccId = Account::where('code', '1001')->value('id');
            if ($expAccId && $cashAccId) {
                $poster = app(AccountingPoster::class);
                $poster->post(
                    $referenceNumber,
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

            DB::commit();
            return redirect()->route('atk.expenses.index')->with('success', 'Pengeluaran berhasil dicatat.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    public function edit(Transaction $expense)
    {
        abort_unless(
            $expense->type === 'expense' && str_starts_with((string) $expense->reference_number, 'ATK-EXP-'),
            404
        );

        return view('atk.expenses.create', compact('expense'));
    }

    public function update(Request $request, Transaction $expense)
    {
        abort_unless(
            $expense->type === 'expense' && str_starts_with((string) $expense->reference_number, 'ATK-EXP-'),
            404
        );

        $data = $request->validate([
            'transaction_date' => 'required|date',
            'amount' => 'required|numeric|min:1',
            'description' => 'required|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            $expense->update($data);
            
            DB::commit();
            return redirect()->route('atk.expenses.index')->with('success', 'Pengeluaran berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    public function destroy(Transaction $expense)
    {
        abort_unless(
            $expense->type === 'expense' && str_starts_with((string) $expense->reference_number, 'ATK-EXP-'),
            404
        );

        DB::beginTransaction();
        try {
            $expense->delete();
            
            DB::commit();
            return redirect()->route('atk.expenses.index')->with('success', 'Pengeluaran berhasil dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }
}
