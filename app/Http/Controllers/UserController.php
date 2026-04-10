<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
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
        $query = User::with('role')->latest();

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
        $query = User::with('role')->latest();

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
        $hasAttendanceCardColumn = Schema::hasColumn('users', 'attendance_card_code');
        if ($hasAttendanceCardColumn && trim((string) $user->attendance_card_code) === '') {
            $seed = User::defaultAttendanceCardCodeById((int) $user->id);
            $user->update([
                'attendance_card_code' => User::generateUniqueAttendanceCardCode((string) $seed, $user->id),
            ]);
            $user->refresh();
        }

        if (! $hasAttendanceCardColumn) {
            $user->attendance_card_code = User::generateUniqueAttendanceCardCode(User::defaultAttendanceCardCodeById((int) $user->id), $user->id);
        }

        $idCardCode = $user->attendance_card_code;
        $employee = $user->employee;
        [$brandName, $logoUrl, $brandSlogan, $brandKey] = $this->resolveUserBrand($user);

        return view('users.id-card', compact('user', 'logoUrl', 'brandName', 'brandSlogan', 'brandKey', 'idCardCode', 'employee'));
    }

    public function create()
    {
        $roles = Role::all();

        return view('users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:255', 'unique:users,email'],
            'username' => ['nullable', 'string', 'max:255', 'unique:users,username'],
            'radius_username' => ['nullable', 'string', 'max:255', 'unique:users,radius_username'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role_id' => ['required', 'exists:roles,id'],
            'phone' => ['nullable', 'string', 'max:20'],
            'daily_salary' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
        ];
        if (Schema::hasColumn('users', 'attendance_card_code')) {
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
        if (Schema::hasColumn('users', 'attendance_card_code')) {
            $createData['attendance_card_code'] = trim((string) ($validated['attendance_card_code'] ?? ''));
        }
        $createdUser = User::create($createData);

        if (Schema::hasColumn('users', 'attendance_card_code') && trim((string) $createdUser->attendance_card_code) === '') {
            $createdUser->update([
                'attendance_card_code' => User::generateUniqueAttendanceCardCode(User::defaultAttendanceCardCodeById((int) $createdUser->id), (int) $createdUser->id),
            ]);
        }

        return redirect()->route('users.index')->with('success', __('User created successfully.'));
    }

    public function edit(User $user)
    {
        $roles = Role::all();

        return view('users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'username' => ['nullable', 'string', 'max:255', Rule::unique('users', 'username')->ignore($user->id)],
            'radius_username' => ['nullable', 'string', 'max:255', Rule::unique('users', 'radius_username')->ignore($user->id)],
            'role_id' => ['required', 'exists:roles,id'],
            'phone' => ['nullable', 'string', 'max:20'],
            'daily_salary' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
        ];
        if (Schema::hasColumn('users', 'attendance_card_code')) {
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
        if (Schema::hasColumn('users', 'attendance_card_code')) {
            $updateData['attendance_card_code'] = trim((string) ($validated['attendance_card_code'] ?? '')) ?: User::generateUniqueAttendanceCardCode(User::defaultAttendanceCardCodeById((int) $user->id), (int) $user->id);
        }
        $user->update($updateData);

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

    private function resolveUserBrand(User $user): array
    {
        $scope = strtolower(trim((string) ($user->role?->label ?: $user->role?->name ?: '')));
        $defaultLogo = (string) (Setting::getValue('store_logo') ?: '');
        $defaultSlogan = 'Solusi Digital Cepat dan Terpercaya';
        if (str_contains($scope, 'wash')) {
            $name = (string) (Setting::getValue('brand_gtwash_name') ?: 'GTWASH');
            $logo = (string) (Setting::getValue('brand_gtwash_logo') ?: $defaultLogo);
            $slogan = (string) (Setting::getValue('brand_gtwash_slogan') ?: $defaultSlogan);

            return [strtoupper($name), $this->brandLogoUrl($logo), $slogan, 'gtwash'];
        }
        if (str_contains($scope, 'net') || str_contains($scope, 'network') || str_contains($scope, 'internet')) {
            $name = (string) (Setting::getValue('brand_mstorenet_name') ?: 'MSTORE.NET');
            $logo = (string) (Setting::getValue('brand_mstorenet_logo') ?: $defaultLogo);
            $slogan = (string) (Setting::getValue('brand_mstorenet_slogan') ?: $defaultSlogan);

            return [strtoupper($name), $this->brandLogoUrl($logo), $slogan, 'mstorenet'];
        }

        $name = (string) (Setting::getValue('brand_mstore_name') ?: Setting::getValue('store_name') ?: 'MSTORE');
        $logo = (string) (Setting::getValue('brand_mstore_logo') ?: $defaultLogo);
        $slogan = (string) (Setting::getValue('brand_mstore_slogan') ?: $defaultSlogan);

        return [strtoupper($name), $this->brandLogoUrl($logo), $slogan, 'mstore'];
    }

    private function brandLogoUrl(string $logo): string
    {
        if (! $logo) {
            return asset('img/logo.png');
        }
        if (str_starts_with($logo, 'http://') || str_starts_with($logo, 'https://')) {
            return $logo;
        }

        return asset($logo);
    }
}
