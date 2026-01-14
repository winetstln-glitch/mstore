<?php

namespace App\Http\Controllers;

use App\Models\Coordinator;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class InventoryController extends Controller
{
    public function index()
    {
        $items = InventoryItem::all();
        
        $query = InventoryTransaction::with(['user', 'item', 'coordinator'])
            ->where('type', 'out');

        if (!Auth::user()->hasRole('admin')) {
            $query->where('user_id', Auth::id());
        }

        $transactions = $query->latest()->paginate(10);

        return view('inventory.index', compact('items', 'transactions'));
    }

    public function createPickup()
    {
        $items = InventoryItem::all();
        $coordinators = Coordinator::orderBy('name')->get();
        return view('inventory.pickup', compact('items', 'coordinators'));
    }

    public function storePickup(Request $request)
    {
        $request->validate([
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'quantity' => 'required|integer|min:1',
            'usage' => 'required|string|in:New Installation,Replacement',
            'proof_image' => 'required|image|max:2048', // 2MB max
            'description' => 'nullable|string',
            'coordinator_id' => 'nullable|exists:coordinators,id',
        ]);

        $path = $request->file('proof_image')->store('inventory_proofs', 'public');

        $finalDescription = '[' . __($request->usage) . '] ' . ($request->description ?? '');

        DB::transaction(function () use ($request, $path, $finalDescription) {
            // Create Transaction
            InventoryTransaction::create([
                'user_id' => Auth::id(),
                'coordinator_id' => $request->coordinator_id,
                'inventory_item_id' => $request->inventory_item_id,
                'type' => 'out',
                'quantity' => $request->quantity,
                'proof_image' => $path,
                'description' => $finalDescription,
            ]);

            // Update Stock
            $item = InventoryItem::find($request->inventory_item_id);
            $item->decrement('stock', $request->quantity);
        });

        return redirect()->route('inventory.index')->with('success', __('Pickup recorded successfully.'));
    }

    public function updatePickup(Request $request, InventoryTransaction $transaction)
    {
        if (Auth::id() !== $transaction->user_id && !Auth::user()->hasRole('admin')) {
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
        });

        return redirect()->back()->with('success', __('Pickup updated successfully.'));
    }

    public function destroyPickup(InventoryTransaction $transaction)
    {
        if (Auth::id() !== $transaction->user_id && !Auth::user()->hasRole('admin')) {
            abort(403, 'Unauthorized action.');
        }

        DB::transaction(function () use ($transaction) {
            // Restore stock
            $transaction->item->increment('stock', $transaction->quantity);
            
            // Delete transaction
            $transaction->delete();
        });

        return redirect()->back()->with('success', __('Pickup deleted successfully.'));
    }
    
    public function storeItem(Request $request)
    {
        // Simple authorization check for admin
        if (!Auth::user()->hasRole('admin')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'unit' => 'required|string|max:50',
            'stock' => 'required|integer|min:0',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        InventoryItem::create($validated);

        return redirect()->back()->with('success', __('Item added successfully.'));
    }

    public function updateItem(Request $request, InventoryItem $item)
    {
        // Simple authorization check for admin
        if (!Auth::user()->hasRole('admin')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'unit' => 'required|string|max:50',
            'stock' => 'required|integer|min:0',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        $item->update($validated);

        return redirect()->back()->with('success', __('Item updated successfully.'));
    }

    public function destroyItem(InventoryItem $item)
    {
        // Simple authorization check for admin
        if (!Auth::user()->hasRole('admin')) {
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
