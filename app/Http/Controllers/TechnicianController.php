<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

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
        $technicians = User::whereHas('role', function ($q) {
            $q->where('name', 'technician');
        })->with('employee')->latest()->paginate(10);

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
            'monthly_salary' => ['nullable', 'numeric', 'min:0'],
            'daily_salary' => ['nullable', 'numeric', 'min:0'],
        ]);

        $existing = User::findExistingUser([
            'email' => $request->email,
            'name' => $request->name,
            'phone' => $request->phone,
        ]);

        if ($existing) {
            return back()->withErrors(['name' => __('User with similar information already exists: :name (:email)', ['name' => $existing->name, 'email' => $existing->email])])->withInput();
        }

        $role = Role::where('name', 'technician')->firstOrFail();

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
            'role_id' => $role->id,
            'phone' => $request->phone,
            'telegram_chat_id' => $request->telegram_chat_id,
            'is_active' => true,
        ]);

        // Create or update Employee record for this technician
        if (! $user->employee) {
            $user->employee()->create([
                'full_name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'monthly_salary' => $request->monthly_salary ?? 0,
                'daily_salary' => $request->daily_salary ?? 0,
                'position' => 'Teknisi',
                'department' => 'Teknis',
                'join_date' => now(),
                'employment_status' => 'Tetap',
            ]);
        } else {
            $user->employee()->update([
                'monthly_salary' => $request->monthly_salary ?? 0,
                'daily_salary' => $request->daily_salary ?? 0,
            ]);
        }

        return redirect()->route('technicians.index')
            ->with('success', __('Technician created successfully.'));
    }

    public function show(User $technician)
    {
        $technician->load('employee');
        return view('technicians.show', compact('technician'));
    }

    public function edit(User $technician)
    {
        $technician->load('employee');
        return view('technicians.edit', compact('technician'));
    }

    public function update(Request $request, User $technician)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$technician->id],
            'phone' => ['nullable', 'string', 'max:20'],
            'telegram_chat_id' => ['nullable', 'string', 'max:100'],
            'monthly_salary' => ['nullable', 'numeric', 'min:0'],
            'daily_salary' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        $technician->update([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'telegram_chat_id' => $request->telegram_chat_id,
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

        // Create or update Employee record for this technician
        if (! $technician->employee) {
            $technician->employee()->create([
                'full_name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'monthly_salary' => $request->monthly_salary ?? 0,
                'daily_salary' => $request->daily_salary ?? 0,
                'position' => 'Teknisi',
                'department' => 'Teknis',
                'join_date' => $technician->created_at,
                'employment_status' => 'Tetap',
            ]);
        } else {
            $technician->employee()->update([
                'monthly_salary' => $request->monthly_salary ?? 0,
                'daily_salary' => $request->daily_salary ?? 0,
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
