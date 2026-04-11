<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Traits\HasIdCard;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

class UserController extends Controller implements HasMiddleware
{
    use HasIdCard;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:user.view', only: ['index', 'export', 'idCard']),
            new Middleware('permission:user.create', only: ['create', 'store']),
            new Middleware('permission:user.edit', only: ['edit', 'update']),
            new Middleware('permission:user.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request)
    {
        $query = User::with('role')
            ->whereHas('role', function ($q) {
                $q->where('name', '!=', 'customer');
            })
            ->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role_id')) {
            $query->where('role_id', $request->integer('role_id'));
        }

        $users = $query->paginate(10)->withQueryString();
        $roles = Role::orderBy('label')->get();

        return view('users.index', compact('users', 'roles'));
    }

    public function export(Request $request)
    {
        $query = User::with('role')
            ->whereHas('role', function ($q) {
                $q->where('name', '!=', 'customer');
            })
            ->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role_id')) {
            $query->where('role_id', $request->integer('role_id'));
        }

        $users = $query->get();

        return response()->streamDownload(function () use ($users) {
            $writer = new Writer;
            $writer->openToFile('php://output');

            $writer->addRow(Row::fromValues([
                'ID',
                'Name',
                'Email',
                'Password',
                'Role',
                'Phone',
                'Daily Salary',
                'Status',
                'Created At',
            ]));

            foreach ($users as $user) {
                $writer->addRow(Row::fromValues([
                    $user->id,
                    $user->name,
                    $user->email,
                    $user->password,
                    $user->role ? $user->role->label : 'No Role',
                    $user->phone,
                    $user->daily_salary,
                    $user->is_active ? 'Active' : 'Inactive',
                    $user->created_at->format('Y-m-d H:i:s'),
                ]));
            }

            $writer->close();
        }, 'users-'.date('Y-m-d-His').'.xlsx');
    }

    public function idCard(User $user)
    {
        $idCardCode = $this->userIdCardCode($user);
        $employee = $user->employee;
        [$brandName, $logoUrl, $brandSlogan, $brandKey] = $this->resolveUserBrand($user);

        return view('users.id-card', compact('user', 'logoUrl', 'brandName', 'brandSlogan', 'brandKey', 'idCardCode', 'employee'));
    }

    public function create()
    {
        $roles = Role::where('name', '!=', 'customer')->get();

        return view('users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $customerRoleId = Role::where('name', 'customer')->value('id');
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:255', 'unique:users,email'],
            'username' => ['nullable', 'string', 'max:255', 'unique:users,username'],
            'radius_username' => ['nullable', 'string', 'max:255', 'unique:users,radius_username'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role_id' => ['required', 'exists:roles,id', Rule::notIn([$customerRoleId])],
            'phone' => ['nullable', 'string', 'max:20'],
            'daily_salary' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
        ];
        if ($this->hasAttendanceCardColumn()) {
            $rules['attendance_card_code'] = ['nullable', 'string', 'max:255', 'unique:users,attendance_card_code'];
        }
        $validated = $request->validate($rules);

        // Prevent duplicates by checking name too
        $existing = User::findExistingUser([
            'email' => $validated['email'] ?? null,
            'username' => $validated['username'] ?? null,
            'radius_username' => $validated['radius_username'] ?? null,
            'attendance_card_code' => $validated['attendance_card_code'] ?? null,
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
        ]);

        if ($existing) {
            return back()->withErrors(['name' => __('User with similar information already exists: :name (:email)', ['name' => $existing->name, 'email' => $existing->email ?: $existing->username])])->withInput();
        }

        $username = trim((string) ($validated['username'] ?? ''));
        if ($username === '') {
            $username = User::generateUniqueUsername($validated['name'], $validated['email'] ?? null);
        }

        $createData = [
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'username' => $username,
            'radius_username' => $validated['radius_username'] ?? null,
            'password' => Hash::make($validated['password']),
            'role_id' => $validated['role_id'],
            'phone' => $validated['phone'] ?? null,
            'daily_salary' => $validated['daily_salary'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ];
        if ($this->hasAttendanceCardColumn()) {
            $createData['attendance_card_code'] = trim((string) ($validated['attendance_card_code'] ?? ''));
        }
        $createdUser = User::create($createData);

        if ($this->hasAttendanceCardColumn() && trim((string) $createdUser->attendance_card_code) === '') {
            $createdUser->update([
                'attendance_card_code' => User::generateUniqueAttendanceCardCode(User::defaultAttendanceCardCodeById((int) $createdUser->id), (int) $createdUser->id),
            ]);
        }

        return redirect()->route('users.index')->with('success', __('User created successfully.'));
    }

    public function edit(User $user)
    {
        $roles = Role::where('name', '!=', 'customer')->get();

        return view('users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $customerRoleId = Role::where('name', 'customer')->value('id');
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'username' => ['nullable', 'string', 'max:255', Rule::unique('users', 'username')->ignore($user->id)],
            'radius_username' => ['nullable', 'string', 'max:255', Rule::unique('users', 'radius_username')->ignore($user->id)],
            'role_id' => ['required', 'exists:roles,id', Rule::notIn([$customerRoleId])],
            'phone' => ['nullable', 'string', 'max:20'],
            'daily_salary' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
        ];
        if ($this->hasAttendanceCardColumn()) {
            $rules['attendance_card_code'] = ['nullable', 'string', 'max:255', Rule::unique('users', 'attendance_card_code')->ignore($user->id)];
        }
        $validated = $request->validate($rules);

        $username = trim((string) ($validated['username'] ?? ''));
        if ($username === '') {
            $username = User::generateUniqueUsername($validated['name'], $validated['email'] ?? null);
        }

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'username' => $username,
            'radius_username' => $validated['radius_username'] ?? null,
            'role_id' => $validated['role_id'],
            'phone' => $validated['phone'] ?? null,
            'daily_salary' => $validated['daily_salary'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ];
        if ($this->hasAttendanceCardColumn()) {
            $updateData['attendance_card_code'] = trim((string) ($validated['attendance_card_code'] ?? '')) ?: User::generateUniqueAttendanceCardCode(User::defaultAttendanceCardCodeById((int) $user->id), (int) $user->id);
        }
        $user->update($updateData);

        \App\Models\Employee::query()
            ->where('user_id', $user->id)
            ->update([
                'full_name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
            ]);

        if ($request->filled('password')) {
            $request->validate([
                'password' => ['confirmed', Rules\Password::defaults()],
            ]);
            $user->update([
                'password' => Hash::make($request->password),
            ]);
        }

        return redirect()->route('users.index')->with('success', __('User updated successfully.'));
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', __('You cannot delete yourself.'));
        }

        try {
            $user->delete();

            return redirect()->route('users.index')->with('success', __('User deleted successfully.'));
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() === '23000') {
                return back()->with('error', __('Cannot delete user because they have related records (e.g., attendance, transactions, logs).'));
            }
            throw $e;
        }
    }

    private function hasAttendanceCardColumn(): bool
    {
        return \Illuminate\Support\Facades\Cache::rememberForever('users_attendance_card_column', function () {
            return Schema::hasColumn('users', 'attendance_card_code');
        });
    }
}
