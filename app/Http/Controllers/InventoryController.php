<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Coordinator;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\Transaction;
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\XLSX\Reader;
use OpenSpout\Writer\XLSX\Writer;

class InventoryController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:inventory.view', only: ['index', 'exportPdf', 'exportExcel', 'exportMovementExcel', 'downloadTemplate']),
            new Middleware('permission:inventory.manage', only: ['storeItem', 'storeStockIn', 'updateItem', 'destroyItem', 'updatePickup', 'destroyPickup', 'importExcel']),
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

        $items = (clone $query)
            ->select([
                'id',
                'name',
                'category',
                'type_group',
                'type',
                'brand',
                'model',
                'unit',
                'stock',
                'price',
                'selling_price',
                'description',
                'created_at',
            ])
            ->get();
        $categories = Cache::remember('inventory.categories', now()->addMinutes(10), function () {
            return InventoryItem::query()
                ->whereNotNull('category')
                ->where('category', '!=', '')
                ->orderBy('category')
                ->distinct()
                ->pluck('category');
        });

        // Dashboard Stats
        $totalStockValue = (clone $query)
            ->selectRaw('COALESCE(SUM(stock * price), 0) as total_stock_value')
            ->value('total_stock_value') ?? 0;
        $totalSellingValue = (clone $query)
            ->selectRaw('COALESCE(SUM(stock * selling_price), 0) as total_selling_value')
            ->value('total_selling_value') ?? 0;
        $totalItems = (clone $query)->count();

        // Total Pembelian (Purchases) separated by type_group
        $purchaseStats = Cache::remember('inventory.purchase_stats', now()->addMinutes(5), function () {
            return InventoryTransaction::where('inventory_transactions.type', 'in')
                ->join('inventory_items', 'inventory_transactions.inventory_item_id', '=', 'inventory_items.id')
                ->select('inventory_items.type_group', DB::raw('SUM(total_cost) as total'))
                ->groupBy('inventory_items.type_group')
                ->pluck('total', 'type_group');
        });

        $totalToolPurchases = $purchaseStats['tool'] ?? 0;
        $totalMaterialPurchases = $purchaseStats['material'] ?? 0;
        $totalPurchases = $totalToolPurchases + $totalMaterialPurchases;

        // Total Penjualan/Pemakaian (Sales) - Expense 'Pengeluaran Pengurus' linked to Inventory
        $totalSales = Cache::remember('inventory.total_sales', now()->addMinutes(5), function () {
            return Transaction::where('category', 'Pengeluaran Pengurus')
                ->where('reference_number', 'like', 'INV-OUT-%')
                ->sum('amount');
        });

        // Transaction History (Stock Movements: in/out)
        $transactions = $this->buildMovementQuery($request)
            ->latest()
            ->paginate(10)
            ->withQueryString();

        // My Assigned Assets (For Technicians/Coordinators)
        $user = Auth::user();
        $coordinatorId = Coordinator::where('user_id', $user->id)->value('id');
        $myAssetsQuery = Asset::query()
            ->with(['item:id,name,unit'])
            ->where(function ($q) use ($user) {
                $q->where(function ($sub) use ($user) {
                    $sub->where('holder_type', User::class)
                        ->where('holder_id', $user->id);
                });

            });
        if ($coordinatorId) {
            $myAssetsQuery->orWhere(function ($sub) use ($coordinatorId) {
                $sub->where('holder_type', Coordinator::class)
                    ->where('holder_id', $coordinatorId);
            });
        }

        $myAssets = $myAssetsQuery->get();

        return view('inventory.index', compact('items', 'transactions', 'totalStockValue', 'totalItems', 'totalPurchases', 'totalToolPurchases', 'totalMaterialPurchases', 'totalSales', 'categories', 'myAssets'));
    }

    private function buildMovementQuery(Request $request)
    {
        $query = InventoryTransaction::query()->with([
            'user:id,name',
            'item:id,name,unit,type_group',
            'coordinator:id,name',
        ]);

        if ($request->filled('type_group')) {
            $query->whereHas('item', function ($q) use ($request) {
                $q->where('type_group', $request->type_group);
            });
        }

        if ($request->filled('movement_type') && in_array($request->movement_type, ['in', 'out'], true)) {
            $query->where('type', $request->movement_type);
        }

        if ($request->input('movement_period') === 'day' && $request->filled('movement_day')) {
            try {
                $day = Carbon::createFromFormat('Y-m-d', $request->movement_day)->toDateString();
                $query->whereDate('created_at', $day);
            } catch (\Throwable $e) {
                // Ignore invalid filter format.
            }
        }

        if ($request->input('movement_period') === 'month' && $request->filled('movement_month')) {
            try {
                $month = Carbon::createFromFormat('Y-m', $request->movement_month);
                $query->whereYear('created_at', $month->year)
                    ->whereMonth('created_at', $month->month);
            } catch (\Throwable $e) {
                // Ignore invalid filter format.
            }
        }

        if (! Auth::user()->hasRole('admin') && ! Auth::user()->hasRole('finance')) {
            $query->where('user_id', Auth::id());
        }

        return $query;
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
                'latitude' => 'nullable|numeric',
                'longitude' => 'nullable|numeric',
            ]);

            $path = $request->file('proof_image')->store('inventory_proofs', 'public');

            $finalDescription = '['.__($data['usage']).'] '.($data['description'] ?? '');

            $hasTools = false;
            DB::transaction(function () use ($data, $path, $finalDescription, &$hasTools) {
                $totals = [];
                foreach ($data['items'] as $row) {
                    $itemId = $row['inventory_item_id'];
                    $qty = $row['quantity'];
                    if (! isset($totals[$itemId])) {
                        $totals[$itemId] = 0;
                    }
                    $totals[$itemId] += $qty;
                }

                foreach ($totals as $itemId => $qty) {
                    $item = InventoryItem::find($itemId);
                    if (! $item || $item->stock < $qty) {
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
                        'unit_cost' => $item?->price ?? 0,
                        'total_cost' => ($item?->price ?? 0) * $row['quantity'],
                        'source_type' => 'pickup',
                        'proof_image' => $path,
                        'description' => $finalDescription,
                    ]);

                    if ($item) {
                        $item->decrement('stock', $row['quantity']);

                        if (! empty($data['coordinator_id']) && $item->price > 0) {
                            Transaction::create([
                                'user_id' => Auth::id(),
                                'coordinator_id' => $data['coordinator_id'],
                                'type' => 'expense',
                                'category' => 'Pengeluaran Pengurus',
                                'amount' => $item->price * $row['quantity'],
                                'transaction_date' => now()->toDateString(),
                                'description' => 'Pengurus mengambil '.$row['quantity'].' '.$item->unit.' '.$item->name,
                                'reference_number' => 'INV-OUT-'.$inventoryTransaction->id,
                            ]);
                        }

                        // Auto-create Asset records for Tools
                        if ($item->type_group === 'tool') {
                            $hasTools = true;
                            $holderType = ! empty($data['coordinator_id']) ? Coordinator::class : User::class;
                            $holderId = $data['coordinator_id'] ?? Auth::id();

                            for ($i = 0; $i < $row['quantity']; $i++) {
                                Asset::create([
                                    'inventory_item_id' => $item->id,
                                    'asset_code' => 'TOOL-'.$item->id.'-'.time().'-'.uniqid(),
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
                'latitude' => 'nullable|numeric',
                'longitude' => 'nullable|numeric',
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
                    'unit_cost' => $item->price,
                    'total_cost' => $item->price * $request->quantity,
                    'source_type' => 'pickup',
                    'proof_image' => $path,
                    'description' => '['.__($request->usage).'] '.$request->description,
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
                            'asset_code' => 'TOOL-'.$item->id.'-'.time().'-'.uniqid(),
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
                        'description' => 'Pengurus mengambil '.$request->quantity.' '.$item->unit.' '.$item->name,
                        'reference_number' => 'INV-OUT-'.$inventoryTransaction->id,
                    ]);
                }
            });
        }

        return redirect()->route('inventory.index')->with('success', __('Items picked up successfully.'));
    }

    public function storeStockIn(Request $request)
    {
        $validated = $request->validate([
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'quantity' => 'required|integer|min:1',
            'unit_cost' => 'required|numeric|min:0',
            'purchase_date' => 'nullable|date',
            'supplier_name' => 'nullable|string|max:150',
            'reference_no' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'selling_price' => 'nullable|numeric|min:0',
        ]);

        $purchaseDate = $validated['purchase_date'] ?? now()->toDateString();

        $item = DB::transaction(function () use ($validated, $purchaseDate) {
            $item = InventoryItem::lockForUpdate()->findOrFail($validated['inventory_item_id']);
            $qty = (int) $validated['quantity'];
            $unitCost = (float) $validated['unit_cost'];
            $totalCost = $qty * $unitCost;

            $oldStock = (int) $item->stock;
            $oldPrice = (float) $item->price;
            $newStock = $oldStock + $qty;
            $newAveragePrice = $newStock > 0
                ? (($oldStock * $oldPrice) + $totalCost) / $newStock
                : $oldPrice;

            $item->update([
                'stock' => $newStock,
                'price' => $newAveragePrice,
                'selling_price' => $validated['selling_price'] ?? $item->selling_price,
            ]);

            $inventoryIn = InventoryTransaction::create([
                'user_id' => Auth::id(),
                'inventory_item_id' => $item->id,
                'type' => 'in',
                'quantity' => $qty,
                'unit_cost' => $unitCost,
                'total_cost' => $totalCost,
                'source_type' => 'purchase',
                'supplier_name' => $validated['supplier_name'] ?? null,
                'reference_no' => $validated['reference_no'] ?? null,
                'description' => $validated['description'] ?? 'Barang masuk dari pembelian stok',
            ]);

            Transaction::create([
                'user_id' => Auth::id(),
                'type' => 'expense',
                'category' => 'Pembelian Alat',
                'amount' => $totalCost,
                'transaction_date' => $purchaseDate,
                'description' => 'Pembelian stok '.$item->name.' ('.$qty.' '.$item->unit.')',
                'reference_number' => 'INV-IN-'.$inventoryIn->id,
            ]);

            return $item;
        });

        return redirect()->route('inventory.index', ['type_group' => $item->type_group])
            ->with('success', __('Stock in recorded successfully.'));
    }

    public function storeItem(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'type_group' => 'required|in:material,tool',
            'type' => 'nullable|string|max:255',
            'brand' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'unit' => 'required|string|max:50',
            'stock' => 'required|integer|min:0',
            'price' => 'required|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
        ]);

        $item = InventoryItem::create($validated);

        if ($item->stock > 0) {
            InventoryTransaction::create([
                'user_id' => Auth::id(),
                'inventory_item_id' => $item->id,
                'type' => 'in',
                'quantity' => $item->stock,
                'unit_cost' => $item->price,
                'total_cost' => $item->stock * $item->price,
                'source_type' => 'opening_balance',
                'description' => 'Stok awal item dibuat',
            ]);
        }

        if ($item->stock > 0 && $item->price > 0) {
            Transaction::create([
                'user_id' => Auth::id(),
                'type' => 'expense',
                'category' => 'Pembelian Alat',
                'amount' => $item->stock * $item->price,
                'transaction_date' => now()->toDateString(),
                'description' => 'Pembelian stok awal '.$item->name,
                'reference_number' => 'INV-IN-'.$item->id,
            ]);
        }

        return redirect()->route('inventory.index', ['type_group' => $validated['type_group']])->with('success', __('Item added successfully.'));
    }

    public function updateItem(Request $request, InventoryItem $item)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'type_group' => 'required|in:material,tool',
            'type' => 'nullable|string|max:255',
            'brand' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'unit' => 'required|string|max:50',
            'stock' => 'nullable|integer|min:0',
            'stock_adjustment' => 'nullable|integer',
            'price' => 'required|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
        ]);

        $oldStock = $item->stock;
        $stockAdjustment = (int) ($validated['stock_adjustment'] ?? 0);
        unset($validated['stock_adjustment']);

        if ($stockAdjustment !== 0) {
            $newStock = $oldStock + $stockAdjustment;
            if ($newStock < 0) {
                return back()->withInput()->withErrors([
                    'stock_adjustment' => __('Stock adjustment causes negative stock.'),
                ]);
            }
            $validated['stock'] = $newStock;
        } elseif (! array_key_exists('stock', $validated) || $validated['stock'] === null) {
            $validated['stock'] = $oldStock;
        }

        $item->update($validated);
        $stockDiff = $item->stock - $oldStock;

        if ($stockDiff !== 0) {
            InventoryTransaction::create([
                'user_id' => Auth::id(),
                'inventory_item_id' => $item->id,
                'type' => $stockDiff > 0 ? 'in' : 'out',
                'quantity' => abs($stockDiff),
                'unit_cost' => $item->price,
                'total_cost' => abs($stockDiff) * $item->price,
                'source_type' => 'adjustment',
                'description' => $stockDiff > 0
                    ? 'Penyesuaian stok masuk via edit item'
                    : 'Penyesuaian stok keluar via edit item',
            ]);
        }

        if ($stockDiff > 0 && $validated['price'] > 0) {
            Transaction::create([
                'user_id' => Auth::id(),
                'type' => 'expense',
                'category' => 'Pembelian Alat',
                'amount' => $stockDiff * $validated['price'],
                'transaction_date' => now()->toDateString(),
                'description' => 'Penambahan stok '.$item->name,
                'reference_number' => 'INV-IN-ADJ-'.$item->id.'-'.time(),
            ]);
        }

        return redirect()->route('inventory.index', ['type_group' => $validated['type_group']])->with('success', __('Item updated successfully.'));
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

        // Update Finance Transaction if exists
        $financeTx = Transaction::where('reference_number', 'INV-OUT-'.$transaction->id)->first();
        if ($financeTx && $item->price > 0) {
            $financeTx->update([
                'amount' => $validated['quantity'] * $item->price,
                'description' => 'Pengurus mengambil '.$validated['quantity'].' '.$item->unit.' '.$item->name,
            ]);
        }

        return redirect()->route('inventory.index')->with('success', __('Pickup updated successfully.'));
    }

    public function destroyPickup(InventoryTransaction $transaction)
    {
        // Return stock
        $transaction->item->increment('stock', $transaction->quantity);

        // Delete auto-created assets
        Asset::whereJsonContains('meta_data->source_transaction_id', $transaction->id)->delete();

        // Delete related finance transaction if exists
        Transaction::where('reference_number', 'INV-OUT-'.$transaction->id)->delete();

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
            $writer = new Writer;
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
                        $item->price,
                    ]));
                }
            });

            $writer->close();
        }, 'inventory_report.xlsx');
    }

    public function exportMovementExcel(Request $request)
    {
        return response()->streamDownload(function () use ($request) {
            $writer = new Writer;
            $writer->openToFile('php://output');

            $writer->addRow(Row::fromValues([
                'Tanggal',
                'Jenis',
                'Tipe Item',
                'User',
                'Item',
                'Qty',
                'Unit',
                'Unit Cost',
                'Total Cost',
                'Sumber',
                'Supplier',
                'Ref No',
                'Deskripsi',
            ]));

            $this->buildMovementQuery($request)
                ->orderBy('id')
                ->chunk(200, function ($movements) use ($writer) {
                    foreach ($movements as $movement) {
                        $writer->addRow(Row::fromValues([
                            optional($movement->created_at)->format('Y-m-d H:i:s'),
                            strtoupper((string) $movement->type),
                            $movement->item?->type_group,
                            $movement->user?->name,
                            $movement->item?->name,
                            (int) $movement->quantity,
                            $movement->item?->unit,
                            (float) ($movement->unit_cost ?? 0),
                            (float) ($movement->total_cost ?? 0),
                            $movement->source_type,
                            $movement->supplier_name,
                            $movement->reference_no,
                            $movement->description,
                        ]));
                    }
                });

            $writer->close();
        }, 'inventory_stock_movements_'.now()->format('Ymd_His').'.xlsx');
    }

    public function downloadTemplate()
    {
        if (! Auth::user()->hasRole('admin') && ! Auth::user()->hasRole('finance')) {
            abort(403, 'Unauthorized action.');
        }

        return response()->streamDownload(function () {
            $writer = new Writer;
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
        if (! Auth::user()->hasRole('admin') && ! Auth::user()->hasRole('finance')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'file' => 'required|file|mimes:xlsx',
        ]);

        $reader = new Reader;
        $reader->open($request->file('file')->getRealPath());

        $count = 0;
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $index => $row) {
                if ($index === 1) {
                    continue;
                } // Skip header

                $cells = $row->getCells();
                if (count($cells) < 9) {
                    continue;
                }

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
                        'stock' => (int) $cells[6]->getValue(),
                        'unit' => $cells[7]->getValue(),
                        'price' => (float) $cells[8]->getValue(),
                    ]
                );
                $count++;
            }
        }

        $reader->close();

        return redirect()->route('inventory.index')->with('success', "$count items imported successfully.");
    }
}
