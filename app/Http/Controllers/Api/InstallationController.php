<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Installation;
use Illuminate\Http\Request;

class InstallationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Installation::with(['customer', 'technician']);

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('technician_id')) {
            $query->where('technician_id', $request->input('technician_id'));
        }

        return response()->json($query->latest()->paginate(15));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'plan_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $installation = Installation::create([
            ...$validated,
            'status' => 'registered',
        ]);

        return response()->json($installation, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Installation $installation)
    {
        return response()->json($installation->load(['customer', 'technician']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Installation $installation)
    {
        $validated = $request->validate([
            'technician_id' => 'nullable|exists:users,id',
            'status' => 'sometimes|required|in:registered,survey,approved,installation,completed,cancelled',
            'plan_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'coordinates' => 'nullable|string',
            'photo_before' => 'nullable|string',
            'photo_after' => 'nullable|string',
        ]);

        $installation->update($validated);

        return response()->json($installation);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Installation $installation)
    {
        $installation->delete();

        return response()->json(['message' => 'Installation deleted successfully']);
    }
}
