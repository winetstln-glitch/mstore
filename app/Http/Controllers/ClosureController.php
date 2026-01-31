<?php

namespace App\Http\Controllers;

use App\Models\Closure;
use App\Models\Odc;
use App\Models\Olt;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

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

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Closure::with(['parent']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
        }

        $closures = $query->latest()->paginate(10);

        return view('closures.index', compact('closures'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $olts = Olt::orderBy('name')->get();
        $odcs = Odc::orderBy('name')->get();
        return view('closures.create', compact('olts', 'odcs'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:closures',
            'coordinates' => 'nullable|string',
            'parent_type' => 'nullable|string|in:App\Models\Olt,App\Models\Odc',
            'parent_id' => 'nullable|integer',
            'description' => 'nullable|string',
        ]);

        // Validate parent_id existence based on parent_type
        if (!empty($validated['parent_type']) && !empty($validated['parent_id'])) {
            $parentClass = $validated['parent_type'];
            if (!$parentClass::where('id', $validated['parent_id'])->exists()) {
                return back()->withErrors(['parent_id' => 'Selected parent does not exist.'])->withInput();
            }
        }

        Closure::create($validated);

        return redirect()->route('closures.index')->with('success', __('Closure created successfully.'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Closure $closure)
    {
        $closure->load(['parent', 'odcs', 'odps']);
        return view('closures.show', compact('closure'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Closure $closure)
    {
        $olts = Olt::orderBy('name')->get();
        $odcs = Odc::orderBy('name')->get();
        return view('closures.edit', compact('closure', 'olts', 'odcs'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Closure $closure)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:closures,name,' . $closure->id,
            'coordinates' => 'nullable|string',
            'parent_type' => 'nullable|string|in:App\Models\Olt,App\Models\Odc',
            'parent_id' => 'nullable|integer',
            'description' => 'nullable|string',
        ]);

        // Validate parent_id existence based on parent_type
        if (!empty($validated['parent_type']) && !empty($validated['parent_id'])) {
            $parentClass = $validated['parent_type'];
            if (!$parentClass::where('id', $validated['parent_id'])->exists()) {
                return back()->withErrors(['parent_id' => 'Selected parent does not exist.'])->withInput();
            }
        }

        $closure->update($validated);

        return redirect()->route('closures.index')->with('success', __('Closure updated successfully.'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Closure $closure)
    {
        $closure->delete();

        return redirect()->route('closures.index')->with('success', __('Closure deleted successfully.'));
    }
}
