<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\InventoryItem;
use App\Models\User;
use App\Models\Coordinator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class AssetController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:inventory.view', only: ['index', 'show', 'myAssets', 'returnAsset']),
            new Middleware('permission:inventory.manage', only: ['create', 'store', 'edit', 'update', 'destroy', 'assign', 'processAssignment']),
        ];
    }

    public function index(Request $request)
    {
        $query = Asset::with(['item', 'holder']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('holder_id')) {
            $query->where('holder_id', $request->holder_id);
        }

        // If accessed via /inventory/item/{item}/assets
        if ($request->route('item')) {
            $query->where('inventory_item_id', $request->route('item'));
            $item = InventoryItem::find($request->route('item'));
        } else {
            $item = null;
        }

        $assets = $query->latest()->paginate(10);
        $items = InventoryItem::all(); // For filters or creation
        $users = User::orderBy('name')->get(); // For assignment modal

        return view('inventory.assets.index', compact('assets', 'items', 'users', 'item'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'asset_code' => 'required|unique:assets,asset_code',
            'serial_number' => 'nullable|unique:assets,serial_number',
            'mac_address' => 'nullable|unique:assets,mac_address',
            'condition' => 'required|in:good,damaged',
            'status' => 'required|in:in_stock,deployed,maintenance,broken,lost',
        ]);

        Asset::create($validated);

        return redirect()->back()->with('success', __('Asset created successfully.'));
    }

    public function update(Request $request, Asset $asset)
    {
        $validated = $request->validate([
            'asset_code' => 'required|unique:assets,asset_code,' . $asset->id,
            'serial_number' => 'nullable|unique:assets,serial_number,' . $asset->id,
            'condition' => 'required|in:good,damaged',
            'status' => 'required|in:in_stock,deployed,maintenance,broken,lost',
            'latitude' => 'nullable|string',
            'longitude' => 'nullable|string',
        ]);

        $asset->update($validated);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'asset' => $asset]);
        }

        return redirect()->back()->with('success', __('Asset updated successfully.'));
    }

    public function assign(Asset $asset)
    {
        $users = User::all();
        // Coordinators are usually Users with a role, or a separate model?
        // In this system, Coordinator is a separate model but might be linked to User.
        // Based on `InventoryTransaction`, `coordinator_id` refers to `coordinators` table.
        // `holder` is polymorphic.
        
        $coordinators = Coordinator::all();
        
        return view('inventory.assets.assign', compact('asset', 'users', 'coordinators'));
    }

    public function processAssignment(Request $request, Asset $asset)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'notes' => 'nullable|string',
        ]);

        $holderType = User::class;
        $holderId = $request->user_id;

        $asset->update([
            'holder_type' => $holderType,
            'holder_id' => $holderId,
            'status' => 'deployed',
            'meta_data' => array_merge($asset->meta_data ?? [], [
                'assignment_note' => $request->notes,
                'assigned_at' => now()->toDateTimeString(),
                'assigned_by' => Auth::id()
            ]),
        ]);

        return redirect()->back()->with('success', __('Asset assigned successfully.'));
    }

    public function returnAsset(Request $request, Asset $asset)
    {
        // Authorization check
        $user = Auth::user();
        // Check if user has management permission
        $isManager = $user->hasPermission('inventory.manage') || $user->hasRole('admin');
        
        // Check if user is the holder
        $isHolder = false;
        if ($asset->holder_type === User::class && $asset->holder_id === $user->id) {
            $isHolder = true;
        } elseif ($asset->holder_type === Coordinator::class) {
            $coordinator = Coordinator::where('user_id', $user->id)->first();
            if ($coordinator && $asset->holder_id === $coordinator->id) {
                $isHolder = true;
            }
        }

        if (!$isManager && !$isHolder) {
            abort(403, __('You are not authorized to return this asset.'));
        }

        $asset->update([
            'holder_type' => null,
            'holder_id' => null,
            'status' => 'in_stock',
            'meta_data' => array_merge($asset->meta_data ?? [], ['returned_at' => now()->toDateTimeString(), 'returned_by' => Auth::id()]),
        ]);

        // Increment inventory stock
        $asset->item->increment('stock');

        return redirect()->back()->with('success', __('Asset returned to stock.'));
    }

    public function myAssets()
    {
        $user = Auth::user();
        
        $query = Asset::with('item')
            ->where(function($q) use ($user) {
                // Assets assigned to the User
                $q->where(function($sub) use ($user) {
                    $sub->where('holder_type', User::class)
                        ->where('holder_id', $user->id);
                });

                // Assets assigned to the Coordinator (if linked)
                $coordinator = Coordinator::where('user_id', $user->id)->first();
                if ($coordinator) {
                    $q->orWhere(function($sub) use ($coordinator) {
                        $sub->where('holder_type', Coordinator::class)
                            ->where('holder_id', $coordinator->id);
                    });
                }
            });

        $myAssets = $query->get();

        return view('inventory.assets.my_assets', compact('myAssets'));
    }

    public function destroy(Asset $asset)
    {
        $asset->delete();
        return redirect()->back()->with('success', __('Asset deleted successfully.'));
    }
}
