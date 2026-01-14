<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class TechnicianController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:technician.view', only: ['index', 'show']),
            new Middleware('permission:technician.create', only: ['create', 'store']),
            new Middleware('permission:technician.edit', only: ['edit', 'update']),
            new Middleware('permission:technician.delete', only: ['destroy']),
        ];
    }

    public function index()
    {
        $technicians = User::whereHas('role', function($q) {
            $q->where('name', 'technician');
        })->latest()->paginate(10);

        return view('technicians.index', compact('technicians'));
    }

    public function create()
    {
        return view('technicians.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'phone' => ['nullable', 'string', 'max:20'],
            'telegram_chat_id' => ['nullable', 'string', 'max:100'],
            'daily_salary' => ['nullable', 'numeric', 'min:0'],
        ]);

        $role = Role::where('name', 'technician')->firstOrFail();

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => $role->id,
            'phone' => $request->phone,
            'telegram_chat_id' => $request->telegram_chat_id,
            'daily_salary' => $request->daily_salary ?? 0,
            'is_active' => true,
        ]);

        return redirect()->route('technicians.index')
            ->with('success', __('Technician created successfully.'));
    }

    public function show(User $technician)
    {
        return view('technicians.show', compact('technician'));
    }

    public function edit(User $technician)
    {
        return view('technicians.edit', compact('technician'));
    }

    public function update(Request $request, User $technician)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$technician->id],
            'phone' => ['nullable', 'string', 'max:20'],
            'telegram_chat_id' => ['nullable', 'string', 'max:100'],
            'daily_salary' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        $technician->update([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'telegram_chat_id' => $request->telegram_chat_id,
            'daily_salary' => $request->daily_salary ?? 0,
            'is_active' => $request->has('is_active'),
        ]);

        if ($request->filled('password')) {
            $request->validate([
                'password' => ['confirmed', Rules\Password::defaults()],
            ]);
            
            $technician->update([
                'password' => Hash::make($request->password),
            ]);
        }

        return redirect()->route('technicians.index')
            ->with('success', __('Technician updated successfully.'));
    }

    public function destroy(User $technician)
    {
        // Prevent deleting if assigned to active tickets or installations
        if ($technician->tickets()->whereIn('status', ['assigned', 'in_progress'])->exists() ||
            $technician->installations()->whereIn('status', ['assigned', 'survey', 'installation'])->exists()) {
            return back()->with('error', __('Cannot delete technician with active assignments.'));
        }

        $technician->delete();

        return redirect()->route('technicians.index')
            ->with('success', __('Technician deleted successfully.'));
    }
}
