<?php

namespace App\Http\Controllers;

use App\Models\Closure;
use App\Models\Odc;
use App\Models\Region;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;

class ClosureController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:closure.view', only: ['index', 'show']),
            new Middleware('permission:closure.create', only: ['create', 'store']),
            new Middleware('permission:closure.edit', only: ['edit', 'update']),
            new Middleware('permission:closure.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $isSuperAdmin = $user->hasAnyRole(config('auth.super_admin_roles', ['admin', 'direktur', 'hrd-manager']));

        $query = Closure::with(['odc', 'region'])->forUserArea($user);

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->has('odc_id') && $request->odc_id != '') {
            $query->where('odc_id', $request->odc_id);
        }

        if ($request->has('region_id') && $request->region_id != '' && $isSuperAdmin) {
            $query->where('region_id', $request->region_id);
        }

        $closures = $query->latest()->customPaginate()->withQueryString();
        $odcs = Odc::query()->forUserArea($user)->orderBy('name')->get();
        $regions = $isSuperAdmin ? Region::orderBy('name')->get() : null;

        return view('closures.index', compact('closures', 'odcs', 'regions', 'isSuperAdmin'))
            ->with('scopeRegion', $user?->coordinator?->region)
            ->with('scopeCompany', $user?->company);
    }

    public function create(Request $request)
    {
        $user = $request->user();
        $isSuperAdmin = $user->hasAnyRole(config('auth.super_admin_roles', ['admin', 'direktur', 'hrd-manager']));

        $odcs = Odc::query()->forUserArea($user)->orderBy('name')->get();
        $regions = Region::orderBy('name')->get();

        return view('closures.create', compact('odcs', 'regions', 'isSuperAdmin'))
            ->with('defaultRegionId', $user->coordinator?->region_id);
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255|unique:closures',
                'odc_id' => 'nullable|exists:odcs,id',
                'region_id' => 'nullable|exists:regions,id',
                'latitude' => 'nullable|numeric',
                'longitude' => 'nullable|numeric',
                'capacity' => 'required|integer|min:0',
                'image' => 'nullable|image|max:2048',
            ]);

            $data = $request->all();

            if ($request->hasFile('image')) {
                $data['image'] = $request->file('image')->store('closures', 'public');
            }

            $closure = Closure::create($data);

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'data' => $closure, 'message' => __('Closure created successfully.')]);
            }

            return redirect()->route('closures.index')->with('success', __('Closure created successfully.'));
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }

            return back()->withErrors($e->getMessage())->withInput();
        }
    }

    public function show(Closure $closure)
    {
        return view('closures.show', compact('closure'));
    }

    public function edit(Request $request, Closure $closure)
    {
        $user = $request->user();
        $isSuperAdmin = $user->hasAnyRole(config('auth.super_admin_roles', ['admin', 'direktur', 'hrd-manager']));

        $odcs = Odc::query()->forUserArea($user)->orderBy('name')->get();
        $regions = Region::orderBy('name')->get();

        return view('closures.edit', compact('closure', 'odcs', 'regions', 'isSuperAdmin'));
    }

    public function update(Request $request, Closure $closure)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255|unique:closures,name,'.$closure->id,
                'odc_id' => 'nullable|exists:odcs,id',
                'region_id' => 'nullable|exists:regions,id',
                'latitude' => 'nullable|numeric',
                'longitude' => 'nullable|numeric',
                'capacity' => 'required|integer|min:0',
                'image' => 'nullable|image|max:2048',
            ]);

            $data = $request->all();

            if ($request->hasFile('image')) {
                if ($closure->image) {
                    Storage::disk('public')->delete($closure->image);
                }
                $data['image'] = $request->file('image')->store('closures', 'public');
            }

            $closure->update($data);

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'data' => $closure, 'message' => __('Closure updated successfully.')]);
            }

            return redirect()->route('closures.index')->with('success', __('Closure updated successfully.'));
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }

            return back()->withErrors($e->getMessage())->withInput();
        }
    }

    public function destroy(Closure $closure)
    {
        if ($closure->image) {
            Storage::disk('public')->delete($closure->image);
        }

        $closure->delete();

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => __('Closure deleted successfully.')]);
        }

        return redirect()->route('closures.index')->with('success', __('Closure deleted successfully.'));
    }
}
