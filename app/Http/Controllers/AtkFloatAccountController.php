<?php

namespace App\Http\Controllers;

use App\Models\AtkFloatAccount;
use App\Models\AtkFloatTransaction;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AtkFloatAccountController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:atk.view', only: ['index', 'show']),
            new Middleware('permission:atk.manage', only: ['create', 'store', 'edit', 'update', 'destroy', 'createTransaction', 'storeTransaction']),
        ];
    }

    public function index()
    {
        $accounts = AtkFloatAccount::latest()->paginate(15);
        return view('atk.float-accounts.index', compact('accounts'));
    }

    public function create()
    {
        return view('atk.float-accounts.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|string|unique:atk_float_accounts,code',
            'name' => 'required|string',
            'account_type' => 'required|in:bank,e-wallet,ppob_deposit',
            'current_balance' => 'nullable|numeric|min:0',
            'status' => 'required|in:active,inactive',
            'description' => 'nullable|string',
        ]);

        AtkFloatAccount::create($data);

        return redirect()->route('atk.float-accounts.index')->with('success', 'Akun float berhasil dibuat.');
    }

    public function show(AtkFloatAccount $account)
    {
        $transactions = $account->transactions()->whereNull('reversed_at')->latest()->paginate(15);
        return view('atk.float-accounts.show', compact('account', 'transactions'));
    }

    public function edit(AtkFloatAccount $account)
    {
        return view('atk.float-accounts.edit', compact('account'));
    }

    public function update(Request $request, AtkFloatAccount $account)
    {
        $data = $request->validate([
            'code' => 'required|string|unique:atk_float_accounts,code,' . $account->id,
            'name' => 'required|string',
            'account_type' => 'required|in:bank,e-wallet,ppob_deposit',
            'status' => 'required|in:active,inactive',
            'description' => 'nullable|string',
        ]);

        $account->update($data);

        return redirect()->route('atk.float-accounts.index')->with('success', 'Akun float berhasil diperbarui.');
    }

    public function destroy(AtkFloatAccount $account)
    {
        $account->delete();
        return redirect()->route('atk.float-accounts.index')->with('success', 'Akun float berhasil dihapus.');
    }

    public function createTransaction(AtkFloatAccount $account)
    {
        return view('atk.float-accounts.create-transaction', compact('account'));
    }

    public function storeTransaction(Request $request, AtkFloatAccount $account)
    {
        $data = $request->validate([
            'transaction_type' => 'required|in:deposit,withdrawal,transfer,topup,ppob,adjustment',
            'amount' => 'required|numeric|min:1',
            'description' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Lock the account to prevent race conditions!
            $account = AtkFloatAccount::lockForUpdate()->findOrFail($account->id);
            
            $balanceBefore = $account->current_balance;
            $balanceAfter = $data['transaction_type'] === 'withdrawal' 
                ? $balanceBefore - $data['amount'] 
                : $balanceBefore + $data['amount'];

            if ($data['transaction_type'] === 'withdrawal' && $balanceAfter < 0) {
                throw new \Exception('Saldo tidak mencukupi.');
            }

            $floatTransaction = AtkFloatTransaction::create([
                'atk_float_account_id' => $account->id,
                'transaction_type' => $data['transaction_type'],
                'amount' => $data['amount'],
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'description' => $data['description'],
                'created_by' => Auth::id(),
            ]);

            $account->update(['current_balance' => $balanceAfter]);
            
            // Sync accounting journal
            $floatTransaction->syncAccountingJournal();

            DB::commit();
            return redirect()->route('atk.float-accounts.show', $account)->with('success', 'Transaksi berhasil dicatat.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }
}
