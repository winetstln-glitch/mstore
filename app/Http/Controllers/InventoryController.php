<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Coordinator;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\XLSX\Reader;

class InventoryController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:inventory.view', only: ['index', 'exportPdf', 'exportExcel', 'downloadTemplate']),
            new Middleware('permission:inventory.manage', only: ['storeItem', 'updateItem', 'destroyItem', 'updatePickup', 'destroyPickup', 'importExcel']),
            new Middleware('permission:inventory.pickup', only: ['createPickup', 'storePickup']),
        ];
    }

    public function index(Request $request)
    {
        $query = InventoryItem::query();

        if ($request->has('category') && $request->category != '') {
            $query->where('category', $request->category);
        }

        if ($request->has('type_group') && $request->type_group != '') {
            $query->where('type_group', $request->type_group);
        }

        // Default sorting: Tools first, then Materials, then by Name
        $query->orderBy('type_group', 'desc')->orderBy('name', 'asc');

        $items = $query->get();
        $categories = InventoryItem::select('category')->distinct()->pluck('category');

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
        
        // Transaction History
        $query = InventoryTransaction::with(['user', 'item', 'coordinator'])
            ->where('type', 'out');

        if ($request->has('type_group') && $request->type_group != '') {
            $query->whereHas('item', function($q) use ($request) {
                $q->where('type_group', $request->type_group);
            });
        }

        if (!Auth::user()->hasRole('admin') && !Auth::user()->hasRole('finance')) {
            $query->where('user_id', Auth::id());
        }

        $transactions = $query->latest()->paginate(10);

        // My Assigned Assets (For Technicians/Coordinators)
        $user = Auth::user();
        $myAssetsQuery = Asset::with('item')
            ->where(function($q) use ($user) {
                $q->where(function($sub) use ($user) {
                    $sub->where('holder_type', User::class)
                        ->where('holder_id', $user->id);
                });

                $coordinator = Coordinator::where('user_id', $user->id)->first();
                if ($coordinator) {
                    $q->orWhere(function($sub) use ($coordinator) {
                        $sub->where('holder_type', Coordinator::class)
                            ->where('holder_id', $coordinator->id);
                    });
                }
            });
            
        $myAssets = $myAssetsQuery->get();

        return view('inventory.index', compact('items', 'transactions', 'totalStockValue', 'totalItems', 'totalPurchases', 'totalSales', 'categories', 'myAssets'));
    }

    public function createPickup(Request $request)
    {
        $query = InventoryItem::query();

        if ($request->has('type_group') && in_array($request->type_group, ['tool', 'material'])) {
            $query->where('type_group', $request->type_group);
        }

        $items = $query->orderBy('type_group', 'desc')->orderBy('name')->get();
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

            $hasTools = false;
            DB::transaction(function () use ($data, $path, $finalDescription, &$hasTools) {
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

                        // Auto-create Asset records for Tools
                        if ($item->type_group === 'tool') {
                            $hasTools = true;
                            $holderType = !empty($data['coordinator_id']) ? Coordinator::class : User::class;
                            $holderId = $data['coordinator_id'] ?? Auth::id();

                            for ($i = 0; $i < $row['quantity']; $i++) {
                                Asset::create([
                                    'inventory_item_id' => $item->id,
                                    'asset_code' => 'TOOL-' . $item->id . '-' . time() . '-' . uniqid(),
                                    'status' => 'deployed',
                                    'condition' => 'good',
                                    'holder_type' => $holderType,
                                    'holder_id' => $holderId,
                                    'latitude' => $data['latitude'] ?? null,
                                    'longitude' => $data['longitude'] ?? null,
                                    'purchase_date' => now(),
                                    'meta_data' => ['source_transaction_id' => $inventoryTransaction->id],
                                ]);
                            }
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

            DB::transaction(function () use ($request, $path) {
                $item = InventoryItem::findOrFail($request->inventory_item_id);

                if ($item->stock < $request->quantity) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'quantity' => [__('Not enough stock available.')],
                    ]);
                }

                $inventoryTransaction = InventoryTransaction::create([
                    'user_id' => Auth::id(),
                    'coordinator_id' => $request->coordinator_id,
                    'inventory_item_id' => $request->inventory_item_id,
                    'type' => 'out',
                    'quantity' => $request->quantity,
                    'proof_image' => $path,
                    'description' => '[' . __($request->usage) . '] ' . $request->description,
                ]);

                $item->decrement('stock', $request->quantity);

                // Auto-create Asset records for Tools
                if ($item->type_group === 'tool') {
                    $hasTools = true;
                    $holderType = $request->coordinator_id ? Coordinator::class : User::class;
                    $holderId = $request->coordinator_id ?? Auth::id();

                    for ($i = 0; $i < $request->quantity; $i++) {
                        Asset::create([
                            'inventory_item_id' => $item->id,
                            'asset_code' => 'TOOL-' . $item->id . '-' . time() . '-' . uniqid(),
                            'status' => 'deployed',
                            'condition' => 'good',
                            'holder_type' => $holderType,
                            'holder_id' => $holderId,
                            'purchase_date' => now(),
                            'meta_data' => ['source_transaction_id' => $inventoryTransaction->id],
                        ]);
                    }
                }

                // Add to Finance Expense if Coordinator is selected
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
            });
        }

        return redirect()->route('inventory.index')->with('success', __('Items picked up successfully.'));
    }

    public function storeItem(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'sometimes|string|max:255',
            'type_group' => 'sometimes|in:material,tool',
            'type' => 'nullable|string|max:255',
            'brand' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'unit' => 'required|string|max:50',
            'stock' => 'required|integer|min:0',
            'price' => 'required|numeric|min:0',
            'expense_source' => 'nullable|in:company,investor',
            'investor_id' => 'nullable|required_if:expense_source,investor|exists:users,id',
        ]);

        $validated['category'] = $validated['category'] ?? ($request->input('category', ''));
        $validated['type_group'] = $validated['type_group'] ?? ($request->input('type_group', 'material'));

        $item = InventoryItem::create($validated);

        if (($item->stock ?? 0) > 0 && ($item->price ?? 0) > 0) {
            $transactionData = [
                'user_id' => Auth::id(),
                'type' => 'expense',
                'category' => 'Pembelian Alat',
                'amount' => $item->stock * $item->price,
                'transaction_date' => now()->toDateString(),
                'description' => 'Pembelian awal stok ' . $item->name,
                'reference_number' => 'INV-IN-' . $item->id,
            ];

            if ($request->expense_source === 'investor' && $request->investor_id) {
                $transactionData['investor_id'] = $request->investor_id;
                $transactionData['description'] .= ' (Dibebankan ke Investor)';
            }

            Transaction::create($transactionData);
        }

        return redirect()->route('inventory.index', ['type_group' => $validated['type_group']])->with('success', __('Item added successfully.'));
    }

    public function updateItem(Request $request, InventoryItem $item)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'sometimes|string|max:255',
            'type_group' => 'sometimes|in:material,tool',
            'type' => 'nullable|string|max:255',
            'brand' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'unit' => 'required|string|max:50',
            'stock' => 'required|integer|min:0',
            'price' => 'required|numeric|min:0',
            'expense_source' => 'nullable|in:company,investor',
            'investor_id' => 'nullable|required_if:expense_source,investor|exists:users,id',
        ]);

        $oldStock = $item->stock;
        $item->update($validated);

        $diff = ($item->stock - $oldStock);
        if ($diff > 0 && ($item->price ?? 0) > 0) {
            $transactionData = [
                'user_id' => Auth::id(),
                'type' => 'expense',
                'category' => 'Pembelian Alat',
                'amount' => $diff * $item->price,
                'transaction_date' => now()->toDateString(),
                'description' => 'Penambahan stok ' . $item->name,
                'reference_number' => 'INV-IN-' . $item->id,
            ];

            if ($request->expense_source === 'investor' && $request->investor_id) {
                $transactionData['investor_id'] = $request->investor_id;
                $transactionData['description'] .= ' (Dibebankan ke Investor)';
            }

            Transaction::create($transactionData);
        }

        $redirectTypeGroup = $validated['type_group'] ?? ($item->type_group ?? 'material');
        return redirect()->route('inventory.index', ['type_group' => $redirectTypeGroup])->with('success', __('Item updated successfully.'));
    }

    public function destroyItem(InventoryItem $item)
    {
        $item->delete();
        return redirect()->route('inventory.index')->with('success', __('Item deleted successfully.'));
    }

    public function updatePickup(Request $request, InventoryTransaction $transaction)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
            'description' => 'nullable|string',
        ]);

        // Revert old stock
        $item = $transaction->item;
        $item->increment('stock', $transaction->quantity);

        // Check new stock availability (old + current stock)
        if ($item->stock < $validated['quantity']) {
            $item->decrement('stock', $transaction->quantity); // Re-revert if failed
            return back()->withErrors(['quantity' => __('Not enough stock available.')]);
        }

        // Apply new stock
        $item->decrement('stock', $validated['quantity']);

        $transaction->update([
            'quantity' => $validated['quantity'],
            'description' => $validated['description'],
        ]);

        Transaction::where('reference_number', 'INV-OUT-' . $transaction->id)
            ->update([
                'amount' => $item->price * $validated['quantity'],
            ]);

        return redirect()->route('inventory.index')->with('success', __('Pickup updated successfully.'));
    }

    public function destroyPickup(InventoryTransaction $transaction)
    {
        // Return stock
        $transaction->item->increment('stock', $transaction->quantity);
        
        // Delete auto-created assets
        Asset::whereJsonContains('meta_data->source_transaction_id', $transaction->id)->delete();

        // Delete related finance transaction if exists
        Transaction::where('reference_number', 'INV-OUT-' . $transaction->id)->delete();

        $transaction->delete();

        return redirect()->route('inventory.index')->with('success', __('Pickup deleted and stock returned.'));
    }

    public function exportPdf()
    {
        $items = InventoryItem::all();
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('inventory.pdf', compact('items'));
        return $pdf->download('inventory_report.pdf');
    }

    public function exportExcel()
    {
        return response()->streamDownload(function () {
            $writer = new Writer();
            $writer->openToFile('php://output');
            
            // Header
            $writer->addRow(Row::fromValues(['Name', 'Category', 'Type', 'Brand', 'Model', 'Description', 'Stock', 'Unit', 'Price']));
            
            // Data
            InventoryItem::chunk(100, function ($items) use ($writer) {
                foreach ($items as $item) {
                    $writer->addRow(Row::fromValues([
                        $item->name,
                        $item->category,
                        $item->type,
                        $item->brand,
                        $item->model,
                        $item->description,
                        $item->stock,
                        $item->unit,
                        $item->price
                    ]));
                }
            });
            
            $writer->close();
        }, 'inventory_report.xlsx');
    }

    public function downloadTemplate()
    {
        if (!Auth::user()->hasRole('admin') && !Auth::user()->hasRole('finance')) {
            abort(403, 'Unauthorized action.');
        }

        return response()->streamDownload(function () {
            $writer = new Writer();
            $writer->openToFile('php://output');
            
            // Header
            $writer->addRow(Row::fromValues(['Name', 'Category', 'Type', 'Brand', 'Model', 'Description', 'Stock', 'Unit', 'Price']));
            
            // Sample Data
            $writer->addRow(Row::fromValues(['Kabel Fiber Optic 1 Core', 'Fiber', 'Cable', 'Zte', 'Generic', 'Kabel dropcore 1 core', '1000', 'meter', '1500']));
            $writer->addRow(Row::fromValues(['Router ZTE F609', 'Device', 'Router', 'ZTE', 'F609', 'Router bekas layak pakai', '10', 'pcs', '150000']));
            $writer->addRow(Row::fromValues(['Splicer Tumtec', 'Tool', 'Splicer', 'Tumtec', 'V9', 'Mesin Splicing', '1', 'unit', '15000000']));
            
            $writer->close();
        }, 'inventory_import_template.xlsx');
    }

    public function importExcel(Request $request)
    {
        if (!Auth::user()->hasRole('admin') && !Auth::user()->hasRole('finance')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'file' => 'required|file|mimes:xlsx',
        ]);

        $reader = new Reader();
        $reader->open($request->file('file')->getRealPath());

        $count = 0;
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $index => $row) {
                if ($index === 1) continue; // Skip header

                $cells = $row->getCells();
                if (count($cells) < 9) continue;

                $category = $cells[1]->getValue();
                // Auto-detect type_group based on category if possible
                $typeGroup = strtolower($category) === 'tool' || strtolower($category) === 'vehicle' ? 'tool' : 'material';

                InventoryItem::updateOrCreate(
                    ['name' => $cells[0]->getValue()],
                    [
                        'category' => $category,
                        'type_group' => $typeGroup,
                        'type' => $cells[2]->getValue(),
                        'brand' => $cells[3]->getValue(),
                        'model' => $cells[4]->getValue(),
                        'description' => $cells[5]->getValue(),
                        'stock' => (int)$cells[6]->getValue(),
                        'unit' => $cells[7]->getValue(),
                        'price' => (float)$cells[8]->getValue(),
                    ]
                );
                $count++;
            }
        }

        $reader->close();

        return redirect()->route('inventory.index')->with('success', "$count items imported successfully.");
    }
}
