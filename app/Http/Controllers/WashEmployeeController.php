<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Models\WashEmployee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class WashEmployeeController extends Controller
{
    public function index()
    {
        $employees = WashEmployee::with('user')->latest()->get();

        return view('wash.employees.index', compact('employees'));
    }

    public function create()
    {
        $employee = new WashEmployee;
        $linkedUserIds = WashEmployee::whereNotNull('user_id')->pluck('user_id')->filter()->all();
        $users = User::query()
            ->whereHas('role', fn ($q) => $q->where('name', 'kasir-wash'))
            ->when(! empty($linkedUserIds), fn ($q) => $q->whereNotIn('id', $linkedUserIds))
            ->orderBy('name')
            ->get();

        return view('wash.employees.create', compact('employee', 'users'));
    }

    public function store(Request $request)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'status' => 'required|in:active,inactive',
            'user_option' => 'sometimes|in:existing,new',
        ];

        if ($request->input('user_option') === 'new') {
            $rules['username'] = 'required|string|max:255|unique:users,username';
            $rules['email'] = 'nullable|string|email|max:255|unique:users,email';
            $rules['password'] = ['required', 'confirmed', Rules\Password::defaults()];
        } else {
            $rules['user_id'] = 'nullable|exists:users,id|unique:wash_employees,user_id';
        }

        $validated = $request->validate($rules);
        $userId = $validated['user_id'] ?? null;

        if ($request->input('user_option') === 'new') {
            $role = Role::where('name', 'kasir-wash')->first();
            $user = User::create([
                'name' => $validated['name'],
                'username' => $validated['username'],
                'email' => $validated['email'] ?? null,
                'password' => Hash::make($validated['password']),
                'phone' => $validated['phone'] ?? null,
                'role_id' => $role?->id,
                'is_active' => true,
            ]);
            $userId = $user->id;
        }

        $data = [
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
            'status' => $validated['status'],
            'user_id' => $userId,
        ];
        WashEmployee::create($data);

        return redirect()->route('wash.employees.index')->with('success', 'Karyawan berhasil ditambahkan.');
    }

    public function edit(WashEmployee $employee)
    {
        $linkedUserIds = WashEmployee::whereNotNull('user_id')
            ->where('id', '!=', $employee->id)
            ->pluck('user_id')
            ->filter()
            ->all();

        $users = User::query()
            ->whereHas('role', fn ($q) => $q->where('name', 'kasir-wash'))
            ->when(! empty($linkedUserIds), fn ($q) => $q->whereNotIn('id', $linkedUserIds))
            ->orderBy('name')
            ->get();

        return view('wash.employees.edit', compact('employee', 'users'));
    }

    public function update(Request $request, WashEmployee $employee)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'status' => 'required|in:active,inactive',
            'user_option' => 'sometimes|in:existing,new',
        ];

        if ($request->input('user_option') === 'new') {
            $rules['username'] = 'required|string|max:255|unique:users,username';
            $rules['email'] = 'nullable|string|email|max:255|unique:users,email';
            $rules['password'] = ['required', 'confirmed', Rules\Password::defaults()];
        } else {
            $rules['user_id'] = 'nullable|exists:users,id|unique:wash_employees,user_id,'.$employee->id;
        }

        $validated = $request->validate($rules);
        $userId = $validated['user_id'] ?? null;

        if ($request->input('user_option') === 'new') {
            $role = Role::where('name', 'kasir-wash')->first();
            $user = User::create([
                'name' => $validated['name'],
                'username' => $validated['username'],
                'email' => $validated['email'] ?? null,
                'password' => Hash::make($validated['password']),
                'phone' => $validated['phone'] ?? null,
                'role_id' => $role?->id,
                'is_active' => true,
            ]);
            $userId = $user->id;
        }

        $data = [
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
            'status' => $validated['status'],
            'user_id' => $userId,
        ];
        $employee->update($data);

        return redirect()->route('wash.employees.index')->with('success', 'Karyawan berhasil diperbarui.');
    }

    public function destroy(WashEmployee $employee)
    {
        $employee->delete();

        return redirect()->route('wash.employees.index')->with('success', 'Karyawan berhasil dihapus.');
    }
}
