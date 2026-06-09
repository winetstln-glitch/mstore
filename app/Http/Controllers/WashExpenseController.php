<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\WashStockItem;
use App\Models\WashStockMovement;
use App\Services\AccountingPoster;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class WashExpenseController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:wash.report', only: ['index']),
            new Middleware('permission:wash.manage', only: ['create', 'store', 'edit', 'update', 'destroy', 'stockOut']),
        ];
    }

    private function queryWashExpenses()
    {
        return Transaction::where('type', 'expense')
            ->where('reference_number', 'like', 'WASH-EXP-%');
    }

    public function index(Request $request)
    {
        $hasStockTables = $this->hasStockTables();
        $category = $this->normalizeExpenseGroup(trim((string) $request->query('category', '')));
        $query = $this->queryWashExpenses();
        if ($category !== '') {
            $query->where(function ($q) use ($category) {
                match ($category) {
                    'shampoo' => $q->where('category', 'like', '%Sampo%'),
                    'snack' => $q->where('category', 'like', '%Snack%'),
                    'caffe' => $q->where(function ($subQ) {
                        $subQ->where('category', 'like', '%Kopi%')
                            ->orWhere('category', 'like', '%Caffe%');
                    }),
                    default => $q->where('category', 'like', '%Lain%'),
                };
            });
        }
        if ($hasStockTables) {
            $query->with('washStockMovement.stockItem');
        }
        $expenses = $query->latest('transaction_date')->paginate(15);
        $stockItems = $hasStockTables
            ? WashStockItem::query()
                ->where('is_active', true)
                ->when($category !== '', function ($q) use ($category) {
                    if ($category === 'caffe') {
                        $q->whereIn('category', ['caffe', 'kopi']);

                        return;
                    }
                    $q->where('category', $category);
                })
                ->orderBy('category')
                ->orderBy('name')
                ->get()
            : collect();
        $lowStockCount = $hasStockTables ? $stockItems->filter(fn ($item) => (float) $item->current_stock <= (float) $item->minimum_stock)->count() : 0;
        $stockMovements = $hasStockTables
            ? WashStockMovement::query()
                ->with('stockItem')
                ->latest('movement_date')
                ->latest('id')
                ->limit(30)
                ->get()
            : collect();

        return view('wash.expenses.index', compact('expenses', 'stockItems', 'hasStockTables', 'category', 'lowStockCount', 'stockMovements'));
    }

    public function create()
    {
        $stockItems = $this->hasStockTables()
            ? WashStockItem::query()->where('is_active', true)->orderBy('category')->orderBy('name')->get()
            : collect();

        return view('wash.expenses.create', compact('stockItems'));
    }

    public function store(Request $request)
    {
        if (! $this->hasStockTables()) {
            $data = $request->validate([
                'transaction_date' => 'required|date',
                'amount' => 'required|numeric|min:0',
                'description' => 'required|string|max:255',
            ]);

            $reference = 'WASH-EXP-'.now()->format('YmdHis').Str::upper(Str::random(3));
            Transaction::create([
                'user_id' => Auth::id(),
                'type' => 'expense',
                'category' => 'Pengeluaran Pengurus',
                'amount' => $data['amount'],
                'description' => $data['description'],
                'transaction_date' => $data['transaction_date'],
                'reference_number' => $reference,
            ]);

            return redirect()->route('wash.expenses.index')->with('success', 'Pengeluaran berhasil dicatat. Jalankan migrate untuk mengaktifkan stok wash.');
        }

        $data = $request->validate([
            'transaction_date' => 'required|date',
            'expense_group' => 'required|in:shampoo,snack,caffe,kopi,lainnya',
            'stock_item_id' => 'nullable|exists:wash_stock_items,id',
            'item_name' => 'required_without:stock_item_id|nullable|string|max:100',
            'unit' => 'required|string|max:20',
            'quantity' => 'required|numeric|min:0.01',
            'unit_price' => 'required|numeric|min:0',
            'amount' => 'nullable|numeric|min:0',
            'description' => 'required|string|max:255',
        ]);
        $data['expense_group'] = $this->normalizeExpenseGroup((string) $data['expense_group']);

        $amount = (float) ($data['amount'] ?? 0);
        if ($amount <= 0) {
            $amount = ((float) $data['quantity']) * ((float) $data['unit_price']);
        }

        $reference = 'WASH-EXP-'.now()->format('YmdHis').Str::upper(Str::random(3));
        $categoryLabel = match ($data['expense_group']) {
            'shampoo' => 'Belanja Sampo Wash',
            'snack' => 'Belanja Snack',
            'caffe' => 'Belanja Caffe',
            default => 'Belanja Lainnya',
        };

        DB::transaction(function () use ($data, $amount, $reference, $categoryLabel) {
            $stockItem = $this->resolveStockItem($data);
            $stockItem->current_stock = (float) $stockItem->current_stock + (float) $data['quantity'];
            $stockItem->last_buy_price = (float) $data['unit_price'];
            $stockItem->save();

            $expense = Transaction::create([
                'user_id' => Auth::id(),
                'type' => 'expense',
                'category' => $categoryLabel,
                'amount' => $amount,
                'description' => $data['description'],
                'transaction_date' => $data['transaction_date'],
                'reference_number' => $reference,
            ]);

            WashStockMovement::create([
                'wash_stock_item_id' => $stockItem->id,
                'transaction_id' => $expense->id,
                'movement_type' => 'in',
                'quantity' => $data['quantity'],
                'unit_price' => $data['unit_price'],
                'total_amount' => $amount,
                'movement_date' => $data['transaction_date'],
                'notes' => $data['description'],
                'user_id' => Auth::id(),
            ]);

            $expAccId = Account::where('code', '6005')->value('id');
            $cashAccId = Account::where('code', '1001')->value('id');
            if ($expAccId && $cashAccId) {
                $poster = app(AccountingPoster::class);
                $poster->post(
                    $reference,
                    $data['transaction_date'],
                    $data['description'],
                    [
                        ['account_id' => $expAccId, 'debit' => $amount, 'credit' => 0, 'unit' => 'MSTORE'],
                        ['account_id' => $cashAccId, 'debit' => 0, 'credit' => $amount, 'unit' => 'MSTORE'],
                    ],
                    null,
                    'wash_expense',
                    null
                );
            }
        });

        return redirect()->route('wash.expenses.index')->with('success', 'Pengeluaran berhasil dicatat.');
    }

    public function edit(Transaction $expense)
    {
        abort_unless(
            $expense->type === 'expense' && str_starts_with((string) $expense->reference_number, 'WASH-EXP-'),
            404
        );

        $stockItems = $this->hasStockTables()
            ? WashStockItem::query()->where('is_active', true)->orderBy('category')->orderBy('name')->get()
            : collect();
        $stockMovement = $this->hasStockTables()
            ? WashStockMovement::query()->where('transaction_id', $expense->id)->first()
            : null;

        return view('wash.expenses.create', compact('expense', 'stockItems', 'stockMovement'));
    }

    public function update(Request $request, Transaction $expense)
    {
        abort_unless(
            $expense->type === 'expense' && str_starts_with((string) $expense->reference_number, 'WASH-EXP-'),
            404
        );

        if (! $this->hasStockTables()) {
            $data = $request->validate([
                'transaction_date' => 'required|date',
                'amount' => 'required|numeric|min:0',
                'description' => 'required|string|max:255',
            ]);
            $expense->update($data);

            return redirect()->route('wash.expenses.index')->with('success', 'Pengeluaran berhasil diperbarui.');
        }

        $data = $request->validate([
            'transaction_date' => 'required|date',
            'expense_group' => 'required|in:shampoo,snack,caffe,kopi,lainnya',
            'stock_item_id' => 'nullable|exists:wash_stock_items,id',
            'item_name' => 'required_without:stock_item_id|nullable|string|max:100',
            'unit' => 'required|string|max:20',
            'quantity' => 'required|numeric|min:0.01',
            'unit_price' => 'required|numeric|min:0',
            'amount' => 'nullable|numeric|min:0',
            'description' => 'required|string|max:255',
        ]);
        $data['expense_group'] = $this->normalizeExpenseGroup((string) $data['expense_group']);
        $amount = (float) ($data['amount'] ?? 0);
        if ($amount <= 0) {
            $amount = ((float) $data['quantity']) * ((float) $data['unit_price']);
        }
        $categoryLabel = match ($data['expense_group']) {
            'shampoo' => 'Belanja Sampo Wash',
            'snack' => 'Belanja Snack',
            'caffe' => 'Belanja Caffe',
            default => 'Belanja Lainnya',
        };

        DB::transaction(function () use ($expense, $data, $amount, $categoryLabel) {
            $existingMovement = WashStockMovement::query()->where('transaction_id', $expense->id)->first();
            if ($existingMovement) {
                $oldItem = WashStockItem::query()->find($existingMovement->wash_stock_item_id);
                if ($oldItem) {
                    $oldItem->current_stock = max(0, (float) $oldItem->current_stock - (float) $existingMovement->quantity);
                    $oldItem->save();
                }
            }

            $stockItem = $this->resolveStockItem($data);
            $stockItem->current_stock = (float) $stockItem->current_stock + (float) $data['quantity'];
            $stockItem->last_buy_price = (float) $data['unit_price'];
            $stockItem->save();

            $expense->update([
                'transaction_date' => $data['transaction_date'],
                'amount' => $amount,
                'description' => $data['description'],
                'category' => $categoryLabel,
            ]);

            WashStockMovement::query()->updateOrCreate(
                ['transaction_id' => $expense->id],
                [
                    'wash_stock_item_id' => $stockItem->id,
                    'movement_type' => 'in',
                    'quantity' => $data['quantity'],
                    'unit_price' => $data['unit_price'],
                    'total_amount' => $amount,
                    'movement_date' => $data['transaction_date'],
                    'notes' => $data['description'],
                    'user_id' => Auth::id(),
                ]
            );
        });

        return redirect()->route('wash.expenses.index')->with('success', 'Pengeluaran berhasil diperbarui.');
    }

    public function destroy(Transaction $expense)
    {
        abort_unless(
            $expense->type === 'expense' && str_starts_with((string) $expense->reference_number, 'WASH-EXP-'),
            404
        );

        if (! $this->hasStockTables()) {
            $expense->delete();

            return redirect()->route('wash.expenses.index')->with('success', 'Pengeluaran berhasil dihapus.');
        }

        DB::transaction(function () use ($expense) {
            $movement = WashStockMovement::query()->where('transaction_id', $expense->id)->first();
            if ($movement) {
                $stockItem = WashStockItem::query()->find($movement->wash_stock_item_id);
                if ($stockItem) {
                    $stockItem->current_stock = max(0, (float) $stockItem->current_stock - (float) $movement->quantity);
                    $stockItem->save();
                }
                $movement->delete();
            }
            $expense->delete();
        });

        return redirect()->route('wash.expenses.index')->with('success', 'Pengeluaran berhasil dihapus.');
    }

    public function stockOut(Request $request)
    {
        if (! $this->hasStockTables()) {
            return redirect()->route('wash.expenses.index')->with('error', 'Modul stok wash belum aktif.');
        }

        $data = $request->validate([
            'wash_stock_item_id' => 'nullable|exists:wash_stock_items,id',
            'new_item_name' => 'required_without:wash_stock_item_id|nullable|string|max:100',
            'new_item_category' => 'required_with:new_item_name|nullable|in:shampoo,snack,caffe,kopi,lainnya',
            'new_item_unit' => 'required_with:new_item_name|nullable|string|max:20',
            'new_item_minimum_stock' => 'nullable|numeric|min:0',
            'movement_date' => 'required|date',
            'quantity' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string|max:255',
        ]);
        if (isset($data['new_item_category'])) {
            $data['new_item_category'] = $this->normalizeExpenseGroup((string) $data['new_item_category']);
        }

        DB::transaction(function () use ($data) {
            $item = ! empty($data['wash_stock_item_id'])
                ? WashStockItem::query()->findOrFail($data['wash_stock_item_id'])
                : WashStockItem::query()->firstOrCreate(
                    [
                        'name' => trim((string) $data['new_item_name']),
                        'category' => (string) $data['new_item_category'],
                    ],
                    [
                        'unit' => (string) $data['new_item_unit'],
                        'minimum_stock' => (float) ($data['new_item_minimum_stock'] ?? 0),
                        'is_active' => true,
                    ]
                );
            $qty = (float) $data['quantity'];
            abort_if((float) $item->current_stock < $qty, 422, 'Stok tidak cukup untuk pemakaian.');

            $item->current_stock = (float) $item->current_stock - $qty;
            $item->save();

            WashStockMovement::create([
                'wash_stock_item_id' => $item->id,
                'movement_type' => 'out',
                'quantity' => $qty,
                'movement_date' => $data['movement_date'],
                'notes' => $data['notes'] ?? 'Pemakaian stok operasional wash',
                'user_id' => Auth::id(),
            ]);
        });

        return redirect()->route('wash.expenses.index')->with('success', 'Pemakaian stok berhasil dicatat.');
    }

    private function hasStockTables(): bool
    {
        return Schema::hasTable('wash_stock_items') && Schema::hasTable('wash_stock_movements');
    }

    private function resolveStockItem(array $data): WashStockItem
    {
        if (! empty($data['stock_item_id'])) {
            $item = WashStockItem::query()->findOrFail($data['stock_item_id']);
            if ($item->unit !== $data['unit']) {
                $item->unit = $data['unit'];
            }

            return $item;
        }

        $name = trim((string) ($data['item_name'] ?? ''));
        abort_if($name === '', 422, 'Nama item wajib diisi jika tidak memilih stok yang ada.');

        return WashStockItem::query()->firstOrCreate(
            ['name' => $name, 'category' => $this->normalizeExpenseGroup((string) $data['expense_group'])],
            ['unit' => $data['unit'], 'is_active' => true]
        );
    }

    private function normalizeExpenseGroup(string $group): string
    {
        $group = strtolower(trim($group));

        return $group === 'kopi' ? 'caffe' : $group;
    }
}
