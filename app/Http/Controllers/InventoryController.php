<?php

namespace App\Http\Controllers;

use App\Models\Coordinator;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class InventoryController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:inventory.view', only: ['index']),
            new Middleware('permission:inventory.manage', only: ['storeItem', 'updateItem', 'destroyItem', 'updatePickup', 'destroyPickup']),
            new Middleware('permission:inventory.pickup', only: ['createPickup', 'storePickup']),
        ];
    }

    public function index()
    {
        $items = InventoryItem::all();

        // Dashboard Stats
        $totalStockValue = $items->sum(function($item) {
            return $item->stock * $item->price;
        });
        $totalItems = $items->count();
        
        // Total Pembelian (Purchases) - Expense 'Pembelian Alat'
        $totalPurchases = Transaction::where('category', 'Pembelian Alat')->sum('amount');
        
        // Total Penjualan/Pemakaian (Sales) - Expense 'Pengeluaran Pengurus' linked to Inventory
        $totalSales = Transaction::where('category', 'Pengeluaran Pengurus')
            ->where('reference_number', 'like', 'INV-OUT-%')
            ->sum('amount');
        
        $query = InventoryTransaction::with(['user', 'item', 'coordinator'])
            ->where('type', 'out');

        if (!Auth::user()->hasRole('admin') && !Auth::user()->hasRole('finance')) {
            $query->where('user_id', Auth::id());
        }

        $transactions = $query->latest()->paginate(10);

        return view('inventory.index', compact('items', 'transactions', 'totalStockValue', 'totalItems', 'totalPurchases', 'totalSales'));
    }

    public function createPickup()
    {
        $items = InventoryItem::all();
        $coordinators = Coordinator::orderBy('name')->get();
        return view('inventory.pickup', compact('items', 'coordinators'));
    }

    public function storePickup(Request $request)
    {
        if ($request->has('items')) {
            $data = $request->validate([
                'items' => 'required|array|min:1',
                'items.*.inventory_item_id' => 'required|exists:inventory_items,id',
                'items.*.quantity' => 'required|integer|min:1',
                'usage' => 'required|string|in:New Installation,Replacement',
                'proof_image' => 'required|image|max:10240',
                'description' => 'nullable|string',
                'coordinator_id' => 'nullable|exists:coordinators,id',
            ]);

            $path = $request->file('proof_image')->store('inventory_proofs', 'public');

            $finalDescription = '[' . __($data['usage']) . '] ' . ($data['description'] ?? '');

            DB::transaction(function () use ($data, $path, $finalDescription) {
                $totals = [];
                foreach ($data['items'] as $row) {
                    $itemId = $row['inventory_item_id'];
                    $qty = $row['quantity'];
                    if (!isset($totals[$itemId])) {
                        $totals[$itemId] = 0;
                    }
                    $totals[$itemId] += $qty;
                }

                foreach ($totals as $itemId => $qty) {
                    $item = InventoryItem::find($itemId);
                    if (!$item || $item->stock < $qty) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'items' => [__('Not enough stock available.')],
                        ]);
                    }
                }

                foreach ($data['items'] as $row) {
                    $item = InventoryItem::find($row['inventory_item_id']);

                    $inventoryTransaction = InventoryTransaction::create([
                        'user_id' => Auth::id(),
                        'coordinator_id' => $data['coordinator_id'] ?? null,
                        'inventory_item_id' => $row['inventory_item_id'],
                        'type' => 'out',
                        'quantity' => $row['quantity'],
                        'proof_image' => $path,
                        'description' => $finalDescription,
                    ]);

                    if ($item) {
                        $item->decrement('stock', $row['quantity']);

                        if (!empty($data['coordinator_id']) && $item->price > 0) {
                            Transaction::create([
                                'user_id' => Auth::id(),
                                'coordinator_id' => $data['coordinator_id'],
                                'type' => 'expense',
                                'category' => 'Pengeluaran Pengurus',
                                'amount' => $item->price * $row['quantity'],
                                'transaction_date' => now()->toDateString(),
                                'description' => 'Pengurus mengambil ' . $row['quantity'] . ' ' . $item->unit . ' ' . $item->name,
                                'reference_number' => 'INV-OUT-' . $inventoryTransaction->id,
                            ]);
                        }
                    }
                }
            });
        } else {
            $request->validate([
                'inventory_item_id' => 'required|exists:inventory_items,id',
                'quantity' => 'required|integer|min:1',
                'usage' => 'required|string|in:New Installation,Replacement',
                'proof_image' => 'required|image|max:10240',
                'description' => 'nullable|string',
                'coordinator_id' => 'nullable|exists:coordinators,id',
            ]);

            $path = $request->file('proof_image')->store('inventory_proofs', 'public');

            $finalDescription = '[' . __($request->usage) . '] ' . ($request->description ?? '');

            DB::transaction(function () use ($request, $path, $finalDescription) {
                $item = InventoryItem::find($request->inventory_item_id);
                if ($item && $item->stock < $request->quantity) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'quantity' => __('Not enough stock available.'),
                    ]);
                }

                $inventoryTransaction = InventoryTransaction::create([
                    'user_id' => Auth::id(),
                    'coordinator_id' => $request->coordinator_id,
                    'inventory_item_id' => $request->inventory_item_id,
                    'type' => 'out',
                    'quantity' => $request->quantity,
                    'proof_image' => $path,
                    'description' => $finalDescription,
                ]);

                if ($item) {
                    $item->decrement('stock', $request->quantity);

                    if ($request->coordinator_id && $item->price > 0) {
                        Transaction::create([
                            'user_id' => Auth::id(),
                            'coordinator_id' => $request->coordinator_id,
                            'type' => 'expense',
                            'category' => 'Pengeluaran Pengurus',
                            'amount' => $item->price * $request->quantity,
                            'transaction_date' => now()->toDateString(),
                            'description' => 'Pengurus mengambil ' . $request->quantity . ' ' . $item->unit . ' ' . $item->name,
                            'reference_number' => 'INV-OUT-' . $inventoryTransaction->id,
                        ]);
                    }
                }
            });
        }

        return redirect()->route('inventory.index')->with('success', __('Pickup recorded successfully.'));
    }

    public function updatePickup(Request $request, InventoryTransaction $transaction)
    {
        if (Auth::id() !== $transaction->user_id && !Auth::user()->hasRole('admin') && !Auth::user()->hasRole('finance')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'quantity' => 'required|integer|min:1',
            'description' => 'nullable|string',
        ]);

        DB::transaction(function () use ($request, $transaction) {
            $item = $transaction->item;
            $oldQuantity = $transaction->quantity;
            $newQuantity = $request->quantity;
            $diff = $newQuantity - $oldQuantity;

            // If increasing quantity, check if we have enough stock
            if ($diff > 0) {
                if ($item->stock < $diff) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'quantity' => __('Not enough stock available.'),
                    ]);
                }
            }

            // Adjust stock
            if ($diff != 0) {
                // decrement handles negative values correctly (decrement(-5) = add 5)
                // but Laravel's decrement expects absolute value usually? 
                // actually $model->decrement('column', $amount) -> set column = column - amount.
                // If diff is negative (e.g. -2), column - (-2) = column + 2. Correct.
                $item->decrement('stock', $diff);
            }

            $transaction->update([
                'quantity' => $newQuantity,
                'description' => $request->description,
            ]);

            if ($transaction->coordinator_id && $item && $item->price > 0) {
                $finance = Transaction::where('reference_number', 'INV-OUT-' . $transaction->id)->first();
                if ($finance) {
                    $finance->update([
                        'amount' => $item->price * $newQuantity,
                        'description' => 'Pengurus mengambil ' . $newQuantity . ' ' . $item->unit . ' ' . $item->name,
                    ]);
                }
            }
        });

        return redirect()->back()->with('success', __('Pickup updated successfully.'));
    }

    public function destroyPickup(InventoryTransaction $transaction)
    {
        if (Auth::id() !== $transaction->user_id && !Auth::user()->hasRole('admin') && !Auth::user()->hasRole('finance')) {
            abort(403, 'Unauthorized action.');
        }

        DB::transaction(function () use ($transaction) {
            // Restore stock
            $transaction->item->increment('stock', $transaction->quantity);
            
            // Delete transaction
            $transaction->delete();

            Transaction::where('reference_number', 'INV-OUT-' . $transaction->id)->delete();
        });

        return redirect()->back()->with('success', __('Pickup deleted successfully.'));
    }
    
    public function storeItem(Request $request)
    {
        // Authorization check
        if (!Auth::user()->hasRole('admin') && !Auth::user()->hasRole('finance')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'unit' => 'required|string|max:50',
            'stock' => 'required|integer|min:0',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated) {
            $item = InventoryItem::create($validated);

            if ($item->stock > 0 && $item->price > 0) {
                Transaction::create([
                    'user_id' => Auth::id(),
                    'type' => 'expense',
                    'category' => 'Pembelian Alat',
                    'amount' => $item->stock * $item->price,
                    'transaction_date' => now()->toDateString(),
                    'description' => 'Pembelian awal stok ' . $item->stock . ' ' . $item->unit . ' ' . $item->name,
                    'reference_number' => 'INV-IN-' . $item->id,
                ]);
            }
        });

        return redirect()->back()->with('success', __('Item added successfully.'));
    }

    public function updateItem(Request $request, InventoryItem $item)
    {
        // Authorization check
        if (!Auth::user()->hasRole('admin') && !Auth::user()->hasRole('finance')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'unit' => 'required|string|max:50',
            'stock' => 'required|integer|min:0',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated, $item) {
            $oldStock = $item->stock;

            $item->update($validated);

            $newStock = $item->stock;
            $diff = $newStock - $oldStock;

            if ($diff > 0 && $item->price > 0) {
                Transaction::create([
                    'user_id' => Auth::id(),
                    'type' => 'expense',
                    'category' => 'Pembelian Alat',
                    'amount' => $diff * $item->price,
                    'transaction_date' => now()->toDateString(),
                    'description' => 'Penambahan stok ' . $diff . ' ' . $item->unit . ' ' . $item->name,
                    'reference_number' => 'INV-IN-ITEM-' . $item->id . '-' . now()->format('YmdHis'),
                ]);
            }
        });

        return redirect()->back()->with('success', __('Item updated successfully.'));
    }

    public function destroyItem(InventoryItem $item)
    {
        // Authorization check
        if (!Auth::user()->hasRole('admin') && !Auth::user()->hasRole('finance')) {
            abort(403, 'Unauthorized action.');
        }

        // Check if item has transactions
        if ($item->transactions()->exists()) {
            return redirect()->back()->with('error', __('Cannot delete item with existing transactions.'));
        }

        $item->delete();

        return redirect()->back()->with('success', __('Item deleted successfully.'));
    }
}
