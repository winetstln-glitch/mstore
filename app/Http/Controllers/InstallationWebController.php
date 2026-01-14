<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Installation;
use App\Models\User;
use Illuminate\Http\Request;

use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class InstallationWebController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:installation.view', only: ['index', 'show']),
            new Middleware('permission:installation.create', only: ['create', 'store']),
            new Middleware('permission:installation.edit', only: ['edit', 'update']),
            new Middleware('permission:installation.delete', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Installation::query()->with(['customer', 'technician']);

        if ($request->has('search') && $request->input('search') != '') {
            $search = $request->input('search');
            $query->whereHas('customer', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
            });
        }

        if ($request->has('status') && $request->input('status') != '') {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('technician_id') && $request->input('technician_id') != '') {
            $query->where('technician_id', $request->input('technician_id'));
        }

        if ($request->has('date') && $request->input('date') != '') {
            $query->whereDate('plan_date', $request->input('date'));
        }

        $installations = $query->latest()->paginate(10)->withQueryString();
        $technicians = User::where('role_id', 3)->get(); // Assuming role_id 3 is technician

        return view('installations.index', compact('installations', 'technicians'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $customers = Customer::all();
        $technicians = User::where('role_id', 3)->get();
        $selected_customer_id = $request->input('customer_id');

        return view('installations.create', compact('customers', 'technicians', 'selected_customer_id'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'technician_id' => 'nullable|exists:users,id',
            'plan_date' => 'required|date',
            'status' => 'required|in:registered,survey,approved,installation,completed,cancelled',
            'notes' => 'nullable|string',
            'coordinates' => 'nullable|string',
        ]);

        Installation::create($validated);

        return redirect()->route('installations.index')->with('success', __('Installation created successfully.'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Installation $installation)
    {
        $installation->load(['customer', 'technician']);
        return view('installations.show', compact('installation'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Installation $installation)
    {
        $customers = Customer::all();
        $technicians = User::where('role_id', 3)->get();
        return view('installations.edit', compact('installation', 'customers', 'technicians'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Installation $installation)
    {
        $validated = $request->validate([
            'technician_id' => 'nullable|exists:users,id',
            'plan_date' => 'required|date',
            'status' => 'required|in:registered,survey,approved,installation,completed,cancelled',
            'notes' => 'nullable|string',
            'coordinates' => 'nullable|string',
        ]);

        $installation->update($validated);

        return redirect()->route('installations.index')->with('success', __('Installation updated successfully.'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Installation $installation)
    {
        $installation->delete();
        return redirect()->route('installations.index')->with('success', __('Installation deleted successfully.'));
    }
}
