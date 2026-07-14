<?php

namespace App\Http\Controllers;

use App\Models\Cash;
use App\Models\OwnerFund;
use App\Services\Atk\AtkCashService;
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

    public function __construct(
        private readonly AtkCashService $cashService
    ) {
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

            $ownerFund = OwnerFund::create([
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

            // Update Kas Utama dengan mencatat pergerakan kas
            $cash = $this->cashService->getLockedMainCash();
            $balanceBefore = $cash->balance;
            
            if ($data['type'] === 'loan') {
                $netCashChange = $data['amount'];
                $movementType = 'owner_fund_loan';
                $direction = 'in';
                $description = 'Tambah Dana Talangan - ' . $ownerFund->transaction_code;
            } else {
                $netCashChange = -$data['amount'];
                $movementType = 'owner_fund_repayment';
                $direction = 'out';
                $description = 'Pengembalian Dana Talangan - ' . $ownerFund->transaction_code;
            }

            // Validate balance
            $this->cashService->validateBalance($cash, $netCashChange);

            // Record movement
            $this->cashService->recordMovement($cash, [
                'atk_transaction_id' => null,
                'movement_type' => $movementType,
                'direction' => $direction,
                'amount' => $data['amount'],
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceBefore + $netCashChange,
                'idempotency_key' => "owner-fund:{$ownerFund->id}",
                'description' => $description,
                'reference_type' => \App\Models\OwnerFund::class,
                'reference_id' => $ownerFund->id,
                'occurred_at' => $data['transaction_date'],
                'created_by' => Auth::id(),
            ]);
            
            // Sync accounting journal
            $ownerFund->syncAccountingJournal();

            DB::commit();
            return redirect()->route('atk.owner-funds.index')->with('success', 'Transaksi dana talangan berhasil dicatat.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    public function show($id)
    {
        $fund = OwnerFund::findOrFail($id);
        return view('atk.owner-funds.show', compact('fund'));
    }

    public function destroy($id)
    {
        $fund = OwnerFund::findOrFail($id);
        
        DB::beginTransaction();
        try {
            // Reverse cash change dengan mencatat pergerakan kas reversal
            $cash = $this->cashService->getLockedMainCash();
            
            // Find original movement and reverse it
            $originalMovement = \App\Models\AtkCashMovement::where('idempotency_key', "owner-fund:{$fund->id}")->first();
            
            if ($originalMovement) {
                $this->cashService->reverseMovement($originalMovement, Auth::id());
            }

            // Recalculate balances for all subsequent transactions
            $subsequentFunds = OwnerFund::where('id', '>', $fund->id)->orderBy('id')->get();
            $currentBalance = $fund->balance - ($fund->type === 'loan' ? $fund->amount : -$fund->amount);
            
            foreach ($subsequentFunds as $subFund) {
                $currentBalance = $subFund->type === 'loan' 
                    ? $currentBalance + $subFund->amount 
                    : $currentBalance - $subFund->amount;
                $subFund->update(['balance' => $currentBalance]);
            }

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
