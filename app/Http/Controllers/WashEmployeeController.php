<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Models\WashEmployee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class WashEmployeeController extends Controller
{
    public function index()
    {
        $employees = WashEmployee::all();

        return view('wash.employees.index', compact('employees'));
    }

    public function create()
    {
        // 1. Create an empty instance so the form has a model to bind to
        $employee = new WashEmployee;

        // 2. Get users for the dropdown (like in the edit method)
        $users = User::all();

        // 3. Pass both variables to the view
        return view('wash.employees.create', compact('employee', 'users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:30',
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'user_option' => ['required', Rule::in(['existing', 'new'])],
            'user_id' => 'nullable|exists:users,id',
            'username' => 'required_if:user_option,new|nullable|string|max:255|unique:users,username',
            'email' => 'nullable|email|max:255|unique:users,email',
            'password' => 'required_if:user_option,new|nullable|string|min:6|confirmed',
        ]);

        DB::transaction(function () use ($validated) {
            $userId = $this->resolveUserId($validated);
            WashEmployee::create([
                'name' => $validated['name'],
                'phone' => $validated['phone'] ?? null,
                'status' => $validated['status'],
                'user_id' => $userId,
            ]);
        });

        return redirect()->route('wash.employees.index')->with('success', 'Employee created successfully.');
    }

    public function edit(WashEmployee $employee)
    {
        $users = User::all();

        return view('wash.employees.edit', compact('employee', 'users'));
    }

    public function update(Request $request, WashEmployee $employee)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:30',
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'user_option' => ['required', Rule::in(['existing', 'new'])],
            'user_id' => 'nullable|exists:users,id',
            'username' => 'required_if:user_option,new|nullable|string|max:255|unique:users,username',
            'email' => 'nullable|email|max:255|unique:users,email',
            'password' => 'required_if:user_option,new|nullable|string|min:6|confirmed',
        ]);

        DB::transaction(function () use ($employee, $validated) {
            $userId = $this->resolveUserId($validated);
            $employee->update([
                'name' => $validated['name'],
                'phone' => $validated['phone'] ?? null,
                'status' => $validated['status'],
                'user_id' => $userId,
            ]);
        });

        return redirect()->route('wash.employees.index')->with('success', 'Employee updated successfully.');
    }

    public function destroy(WashEmployee $employee)
    {
        $employee->delete();

        return redirect()->route('wash.employees.index')->with('success', 'Employee deleted successfully.');
    }

    private function resolveUserId(array $validated): ?int
    {
        if (($validated['user_option'] ?? 'existing') === 'new') {
            // Check if user with this email or username or name already exists
            $existingUser = User::findExistingUser([
                'email' => $validated['email'] ?? null,
                'username' => $validated['username'] ?? null,
                'name' => $validated['name'],
                'phone' => $validated['phone'] ?? null,
            ]);

            if ($existingUser) {
                // If existing user doesn't have a role or is just a customer, upgrade to karyawan-wash
                $roleId = Role::query()
                    ->whereIn('name', ['karyawan-wash', 'kasir-wash', 'employee'])
                    ->value('id');

                if (! $existingUser->role_id || $existingUser->role?->name === 'customer') {
                    $existingUser->update(['role_id' => $roleId]);
                }

                return (int) $existingUser->id;
            }

            $roleId = Role::query()
                ->whereIn('name', ['karyawan-wash', 'kasir-wash', 'employee'])
                ->value('id');

            $user = User::create([
                'name' => $validated['name'],
                'username' => $validated['username'],
                'email' => $validated['email'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'password' => $validated['password'],
                'role_id' => $roleId,
                'is_active' => ($validated['status'] ?? 'active') === 'active',
            ]);

            return (int) $user->id;
        }

        return ! empty($validated['user_id']) ? (int) $validated['user_id'] : null;
    }
}
