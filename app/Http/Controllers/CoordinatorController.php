<?php

namespace App\Http\Controllers;

use App\Models\Coordinator;
use App\Models\Region;
use App\Models\User;
use App\Models\Router;
use Illuminate\Http\Request;

class CoordinatorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $coordinators = Coordinator::with(['region', 'user', 'router'])->latest()->paginate(10);
        return view('coordinators.index', compact('coordinators'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $regions = Region::orderBy('name')->get();
        $users = User::orderBy('name')->get();
        $routers = Router::where('is_active', true)->orderBy('name')->get();
        return view('coordinators.create', compact('regions', 'users', 'routers'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'region_id' => 'required|exists:regions,id',
            'router_id' => 'nullable|exists:routers,id',
            'user_option' => 'sometimes|in:existing,new',
        ];

        if ($request->input('user_option') === 'new') {
            $rules['email'] = 'required|string|email|max:255|unique:users';
            $rules['password'] = 'required|string|min:8';
        } else {
            $rules['user_id'] = 'nullable|exists:users,id';
        }

        $validated = $request->validate($rules);
        
        $userId = $request->user_id;

        if ($request->input('user_option') === 'new') {
            $role = Role::where('name', 'coordinator')->first();
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role_id' => $role ? $role->id : null,
            ]);
            $userId = $user->id;
        }

        Coordinator::create([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'address' => $validated['address'],
            'region_id' => $validated['region_id'],
            'user_id' => $userId,
            'router_id' => $validated['router_id'],
        ]);

        return redirect()->route('coordinators.index')->with('success', 'Coordinator created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Coordinator $coordinator)
    {
        $regions = Region::orderBy('name')->get();
        $users = User::orderBy('name')->get();
        $routers = Router::where('is_active', true)->orderBy('name')->get();
        return view('coordinators.edit', compact('coordinator', 'regions', 'users', 'routers'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Coordinator $coordinator)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'region_id' => 'required|exists:regions,id',
            'user_id' => 'nullable|exists:users,id',
            'router_id' => 'nullable|exists:routers,id',
        ]);

        $coordinator->update($request->all());

        return redirect()->route('coordinators.index')->with('success', 'Coordinator updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Coordinator $coordinator)
    {
        if ($coordinator->tickets()->exists()) {
             return back()->with('error', 'Cannot delete coordinator associated with tickets.');
        }

        $coordinator->delete();

        return redirect()->route('coordinators.index')->with('success', 'Coordinator deleted successfully.');
    }
}
