<?php

namespace App\Http\Controllers;

use App\Models\WashStockItem;
use App\Models\WashStockMovement;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;

class WashStockController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:wash.view', only: ['index', 'show']),
            new Middleware('permission:wash.manage', only: ['create', 'store', 'edit', 'update', 'destroy', 'stockIn', 'storeStockIn']),
        ];
    }
    public function index()
    {
        $items = WashStockItem::orderBy('name')->paginate(20);
        return view('wash.stock.index', compact('items'));
    }

    public function create()
    {
        return view('wash.stock.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
            'unit' => 'required|string|max:50',
            'current_stock' => 'required|numeric|min:0',
            'minimum_stock' => 'nullable|numeric|min:0',
            'last_buy_price' => 'nullable|numeric|min:0',
        ]);

        DB::transaction(function () use ($request) {
            $item = WashStockItem::create([
                'name' => $request->name,
                'category' => $request->category,
                'unit' => $request->unit,
                'current_stock' => $request->current_stock,
                'minimum_stock' => $request->minimum_stock ?? 0,
                'last_buy_price' => $request->last_buy_price,
                'is_active' => $request->has('is_active'),
            ]);
            
            if ($item->current_stock > 0) {
                WashStockMovement::create([
                    'wash_stock_item_id' => $item->id,
                    'movement_type' => 'in',
                    'quantity' => $item->current_stock,
                    'unit_price' => $item->last_buy_price,
                    'total_amount' => $item->current_stock * ($item->last_buy_price ?? 0),
                    'movement_date' => now(),
                    'notes' => 'Stok awal',
                    'user_id' => auth()->id(),
                ]);
            }
        });

        return redirect()->route('wash.stock.index')->with('success', 'Stok item berhasil ditambahkan!');
    }

    public function show(WashStockItem $stockItem)
    {
        $movements = $stockItem->movements()->latest()->paginate(10);
        return view('wash.stock.show', compact('stockItem', 'movements'));
    }

    public function edit(WashStockItem $stockItem)
    {
        return view('wash.stock.edit', compact('stockItem'));
    }

    public function update(Request $request, WashStockItem $stockItem)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
            'unit' => 'required|string|max:50',
            'current_stock' => 'required|numeric|min:0',
            'minimum_stock' => 'nullable|numeric|min:0',
            'last_buy_price' => 'nullable|numeric|min:0',
        ]);

        $stockItem->update([
            'name' => $request->name,
            'category' => $request->category,
            'unit' => $request->unit,
            'current_stock' => $request->current_stock,
            'minimum_stock' => $request->minimum_stock ?? 0,
            'last_buy_price' => $request->last_buy_price,
            'is_active' => $request->has('is_active'),
        ]);
        return redirect()->route('wash.stock.index')->with('success', 'Stok item berhasil diupdate!');
    }

    public function destroy(WashStockItem $stockItem)
    {
        $stockItem->delete();
        return redirect()->route('wash.stock.index')->with('success', 'Stok item berhasil dihapus!');
    }

    public function stockIn(WashStockItem $stockItem)
    {
        return view('wash.stock.stock-in', compact('stockItem'));
    }

    public function storeStockIn(Request $request, WashStockItem $stockItem)
    {
        $request->validate([
            'quantity' => 'required|numeric|min:1',
            'unit_price' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
            'movement_date' => 'required|date',
        ]);

        DB::transaction(function () use ($request, $stockItem) {
            $stockItem->increment('current_stock', $request->quantity);
            
            if ($request->unit_price > 0) {
                $stockItem->update(['last_buy_price' => $request->unit_price]);
            }

            WashStockMovement::create([
                'wash_stock_item_id' => $stockItem->id,
                'movement_type' => 'in',
                'quantity' => $request->quantity,
                'unit_price' => $request->unit_price,
                'total_amount' => $request->quantity * $request->unit_price,
                'movement_date' => $request->movement_date,
                'notes' => $request->notes,
                'user_id' => auth()->id(),
            ]);
        });

        return redirect()->route('wash.stock.show', $stockItem)->with('success', 'Stok masuk berhasil dicatat!');
    }
}
