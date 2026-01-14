<?php

namespace App\Http\Controllers;

use App\Models\Odc;
use App\Models\Olt;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class OdcController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:odc.view', only: ['index', 'show']),
            new Middleware('permission:odc.create', only: ['store']),
            new Middleware('permission:odc.edit', only: ['update']),
            new Middleware('permission:odc.delete', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Usually handled by MapController for visualization, 
        // but we might want a list view later.
        $odcs = Odc::with('olt')->get();
        return response()->json($odcs);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'olt_id' => 'required|exists:olts,id',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'capacity' => 'required|integer|min:0',
            'description' => 'nullable|string',
        ]);

        $odc = Odc::create($validated);

        return response()->json(['success' => true, 'data' => $odc]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Odc $odc)
    {
        return response()->json($odc);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Odc $odc)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'olt_id' => 'required|exists:olts,id',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'capacity' => 'required|integer|min:0',
            'description' => 'nullable|string',
        ]);

        $odc->update($validated);

        return response()->json(['success' => true, 'data' => $odc]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Odc $odc)
    {
        $odc->delete();
        return response()->json(['success' => true]);
    }
}
