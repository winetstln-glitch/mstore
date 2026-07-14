<?php

namespace App\Http\Controllers;

use App\Models\AtkCashMovement;
use App\Models\User;
use App\Models\Cash;
use App\Services\Atk\AtkCashService;
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
        // Query tanpa filter tanggal terlebih dahulu untuk hitung saldo awal
        $baseQuery = AtkCashMovement::with(['creator', 'atkTransaction.items'])->whereNull('reversed_at');

        // Filter tanggal untuk list transaksi
        $query = (clone $baseQuery)->orderBy('created_at', 'asc');
        $startDate = $request->filled('start_date') ? $request->start_date : null;
        $endDate = $request->filled('end_date') ? $request->end_date : null;
        
        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }
        if ($request->filled('movement_type')) {
            $query->where('movement_type', $request->movement_type);
        }
        if ($request->filled('created_by')) {
            $query->where('created_by', $request->created_by);
        }

        // Dapatkan transaksi tanpa pagination untuk menghitung running balance
        $movementsCollection = $query->get();
        
        // Hitung saldo awal dari opening_balance shift terakhir
        $lastShift = \App\Models\AtkCashRegister::latest()->first();
        $initialBalance = $lastShift ? $lastShift->opening_balance : 0;
        
        // Jika ada filter tanggal, gunakan saldo sebelum start_date
        if ($startDate) {
            $lastMovementBefore = (clone $baseQuery)
                ->whereDate('created_at', '<', $startDate)
                ->orderBy('created_at', 'desc')
                ->first();
            $initialBalance = $lastMovementBefore ? $lastMovementBefore->balance_after : $initialBalance;
        }

        // Hitung running balance untuk setiap transaksi
        $runningBalance = $initialBalance;
        foreach ($movementsCollection as $movement) {
            $movement->running_balance = $movement->balance_after;
        }

        // Urutkan kembali ke desc untuk tampilan
        $movementsCollection = $movementsCollection->sortByDesc('created_at');
        
        // Buat pagination manual
        $page = $request->get('page', 1);
        $perPage = 20;
        $offset = ($page - 1) * $perPage;
        $movements = new \Illuminate\Pagination\LengthAwarePaginator(
            $movementsCollection->slice($offset, $perPage)->values(),
            $movementsCollection->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $users = User::orderBy('name')->get();
        $types = ['sale', 'service', 'expense', 'transfer', 'withdrawal', 'topup', 'ppob', 'owner_loan', 'owner_repayment', 'adjustment'];

        return view('atk.cash-movements.index', compact('movements', 'users', 'types', 'initialBalance'));
    }

    public function create()
    {
        return view('atk.cash-movements.create');
    }

    public function store(Request $request, AtkCashService $cashService)
    {
        $request->validate([
            'amount' => 'required|numeric',
            'description' => 'nullable|string|max:255',
        ]);

        \DB::beginTransaction();
        try {
            // Lock Kas Utama
            $cash = $cashService->getLockedMainCash();
            $balanceBefore = $cash->balance;
            
            // Determine direction (in if positive, out if negative)
            $amount = (float)$request->amount;
            $direction = $amount >= 0 ? 'in' : 'out';
            $absAmount = abs($amount);
            
            $balanceAfter = $balanceBefore + $amount;
            
            // Validate balance if we're taking out money
            if ($direction === 'out' && $balanceAfter < 0) {
                throw new \Exception('Saldo Kas Utama tidak cukup!');
            }

            $cashService->recordMovement($cash, [
                'atk_cash_register_id' => null,
                'movement_type' => 'adjustment',
                'direction' => $direction,
                'amount' => $absAmount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'idempotency_key' => "atk-cash-adjustment:" . now()->timestamp,
                'description' => $request->description ?? 'Penyesuaian Saldo Kas',
                'reference_type' => null,
                'reference_id' => null,
                'created_by' => auth()->id(),
            ]);

            \DB::commit();

            return redirect()->route('atk.cash-movements.index')->with('success', 'Saldo Kas berhasil disesuaikan!');
        } catch (\Exception $e) {
            \DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }
}
