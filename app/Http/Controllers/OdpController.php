<?php

namespace App\Http\Controllers;

use App\Models\Odp;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class OdpController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:odp.view', only: ['index', 'show']),
            new Middleware('permission:odp.create', only: ['create', 'store']),
            new Middleware('permission:odp.edit', only: ['edit', 'update']),
            new Middleware('permission:odp.delete', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $odps = Odp::latest()->paginate(10);
        return view('odps.index', compact('odps'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('odps.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:odps',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'capacity' => 'nullable|integer|min:1',
            'description' => 'nullable|string',
            'region_id' => 'nullable|exists:regions,id',
            'odc_id' => 'nullable|exists:odcs,id',
            'color' => 'nullable|string|max:20',
        ]);

        $odp = Odp::create($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('ODP created successfully.'),
                'data' => $odp
            ]);
        }

        return redirect()->route('odps.index')->with('success', __('ODP created successfully.'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Odp $odp)
    {
        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $odp
            ]);
        }
        return view('odps.show', compact('odp'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Odp $odp)
    {
        return view('odps.edit', compact('odp'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Odp $odp)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:odps,name,' . $odp->id,
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'capacity' => 'nullable|integer|min:1',
            'description' => 'nullable|string',
            'region_id' => 'nullable|exists:regions,id',
            'odc_id' => 'nullable|exists:odcs,id',
            'color' => 'nullable|string|max:20',
        ]);

        $odp->update($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('ODP updated successfully.'),
                'data' => $odp
            ]);
        }

        return redirect()->route('odps.index')->with('success', __('ODP updated successfully.'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Odp $odp)
    {
        $odp->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('ODP deleted successfully.')
            ]);
        }

        return redirect()->route('odps.index')->with('success', __('ODP deleted successfully.'));
    }
}
