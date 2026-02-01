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
        ]);

        WashService::create($request->all());

        return redirect()->route('wash.services.index')
            ->with('success', 'Service created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(WashService $service) // Implicit binding requires correct route param name.
    // In web.php: Route::resource('wash/services', ...). The param is likely 'service' or derived from 'services'.
    // Checking web.php: Route::resource('wash/services', ...); 
    // Laravel default param for 'wash/services' is 'service'.
    // However, I should check if I need to explicitly define the parameter name or use $id.
    // Let's use standard dependency injection.
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
        ]);

        $service->update($request->all());

        return redirect()->route('wash.services.index')
            ->with('success', 'Service updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(WashService $service)
    {
        $service->delete();

        return redirect()->route('wash.services.index')
            ->with('success', 'Service deleted successfully.');
    }
}
