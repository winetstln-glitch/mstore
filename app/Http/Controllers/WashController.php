<?php

namespace App\Http\Controllers;

use App\Models\WashService;
use Illuminate\Http\Request;

class WashController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $services = WashService::all();

        return view('wash.services.index', compact('services'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('wash.services.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'vehicle_type' => 'required|in:car,motor',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'image' => 'nullable|image|max:2048',
        ]);

        $data = $request->all();

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('wash-services', 'public');
        }

        WashService::create($data);

        return redirect()->route('wash.services.index')
            ->with('success', 'Service created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(WashService $service)
    {
        return view('wash.services.edit', compact('service'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, WashService $service)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'vehicle_type' => 'required|in:car,motor',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'image' => 'nullable|image|max:2048',
        ]);

        $data = $request->all();

        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($service->image) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($service->image);
            }
            $data['image'] = $request->file('image')->store('wash-services', 'public');
        }

        $service->update($data);

        return redirect()->route('wash.services.index')
            ->with('success', 'Service updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(WashService $service)
    {
        if ($service->image) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($service->image);
        }

        $service->delete();

        return redirect()->route('wash.services.index')
            ->with('success', 'Service deleted successfully.');
    }
}
