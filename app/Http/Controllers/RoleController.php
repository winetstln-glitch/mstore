<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use App\Support\DefaultRolePermissions;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RoleController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:role.view', only: ['index']),
            new Middleware('permission:role.create', only: ['create', 'store']),
            new Middleware('permission:role.edit', only: ['edit', 'update']),
            new Middleware('permission:role.delete', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $roles = Role::where('name', '!=', 'customer')
            ->withCount('users')
            ->withCount('permissions')
            ->when(request('sort'), function ($query) {
                $sortColumn = request('sort', 'created_at');
                $sortDirection = request('direction', 'desc');
                $allowedColumns = ['name', 'label', 'created_at', 'users_count', 'permissions_count'];
                
                if (in_array($sortColumn, $allowedColumns)) {
                    $query->orderBy($sortColumn, $sortDirection);
                }
            }, function ($query) {
                $query->latest();
            })
            ->paginate(10)
            ->appends(request()->query());

        return view('roles.index', compact('roles'));
    }

    /**
     * Get permissions that the current user is allowed to assign.
     */
    private function getAllowedPermissions()
    {
        $user = auth()->user();

        if ($user->hasRole('admin')) {
            return Permission::all();
        }

        return $user->role?->permissions ?? collect();
    }

    /**
     * Filter grouped permissions to only show allowed ones (DRY Helper).
     */
    private function filterGroupedPermissions($allowedPermissions)
    {
        $permissions = Permission::getGroupedPermissions();
        $filteredPermissions = [];
        $allowedIds = $allowedPermissions->pluck('id')->toArray();

        foreach ($permissions as $tab => $groups) {
            foreach ($groups as $group => $perms) {
                $filteredPerms = $perms->filter(function ($permission) use ($allowedIds) {
                    return in_array($permission->id, $allowedIds);
                });
                
                if ($filteredPerms->isNotEmpty()) {
                    $filteredPermissions[$tab][$group] = $filteredPerms;
                }
            }
        }

        return $filteredPermissions;
    }

    /**
     * Get standard permission templates, filtered by what the user can assign.
     */
    private function getStandardPermissions()
    {
        $allowedPermissions = $this->getAllowedPermissions();
        return DefaultRolePermissions::standardTemplatePermissionIds($allowedPermissions);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $allowedPermissions = $this->getAllowedPermissions();
        $filteredPermissions = $this->filterGroupedPermissions($allowedPermissions);
        $standardPermissions = $this->getStandardPermissions();

        return view('roles.create', compact('filteredPermissions', 'standardPermissions'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $allowedPermissions = $this->getAllowedPermissions();
        $allowedIds = $allowedPermissions->pluck('id')->toArray();

        $validated = $request->validate([
            'label' => 'required|string|max:255',
            'permissions' => 'nullable|array',
            'permissions.*' => [
                'required',
                'integer',
                Rule::in($allowedIds),
            ],
        ]);

        $roleName = Str::slug($validated['label']);
        $existingRole = Role::where('name', $roleName)->first();
        if ($existingRole) {
            return back()
                ->withErrors([
                    'label' => __('Role ":role" sudah ada. Gunakan menu edit untuk mengubah izin.', ['role' => $existingRole->label]),
                ])
                ->withInput();
        }

        $role = Role::create([
            'name' => $roleName,
            'label' => $validated['label'],
        ]);

        if (!empty($validated['permissions'])) {
            $role->permissions()->sync($validated['permissions']);
        }
        Cache::forget("sidebar.permission_map.role.{$role->id}");

        return redirect()->route('roles.index')->with('success', __('Role berhasil dibuat.'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Role $role)
    {
        $allowedPermissions = $this->getAllowedPermissions();
        $filteredPermissions = $this->filterGroupedPermissions($allowedPermissions);
        $standardPermissions = $this->getStandardPermissions();

        $rolePermissions = $role->permissions->pluck('id')->toArray();
        $allowedIds = $allowedPermissions->pluck('id')->toArray();
        
        $visiblePermissions = array_intersect($rolePermissions, $allowedIds);
        $hiddenPermissions = array_diff($rolePermissions, $allowedIds);
        
        session()->put('role_hidden_permissions_' . $role->id, $hiddenPermissions);

        return view('roles.edit', compact('role', 'filteredPermissions', 'visiblePermissions', 'standardPermissions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Role $role)
    {
        $allowedPermissions = $this->getAllowedPermissions();
        $allowedIds = $allowedPermissions->pluck('id')->toArray();

        $validated = $request->validate([
            'label' => 'required|string|max:255',
            'permissions' => 'nullable|array',
            'permissions.*' => [
                'required',
                'integer',
                Rule::in($allowedIds),
            ],
        ]);

        $newSlug = Str::slug($validated['label']);
        $existingRole = Role::where('name', $newSlug)
            ->where('id', '!=', $role->id)
            ->first();
        if ($existingRole) {
            return back()
                ->withErrors([
                    'label' => __('Role ":role" sudah ada. Gunakan nama lain.', ['role' => $existingRole->label]),
                ])
                ->withInput();
        }

        $protectedRoles = ['admin', 'customer'];
        if (in_array($role->name, $protectedRoles)) {
            $role->update([
                'label' => $validated['label'],
            ]);
        } else {
            $role->update([
                'name' => $newSlug,
                'label' => $validated['label'],
            ]);
        }

        $hiddenPermissions = session()->pull('role_hidden_permissions_' . $role->id, []);
        $newPermissions = $validated['permissions'] ?? [];
        $finalPermissions = array_unique(array_merge($newPermissions, $hiddenPermissions));

        if (!empty($finalPermissions)) {
            $role->permissions()->sync($finalPermissions);
        } else {
            $role->permissions()->detach();
        }
        Cache::forget("sidebar.permission_map.role.{$role->id}");

        return redirect()->route('roles.index')->with('success', __('Role berhasil diperbarui.'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role)
    {
        $protectedRoles = ['admin', 'customer'];
        if (in_array($role->name, $protectedRoles)) {
            return back()->with('error', __('Tidak dapat menghapus role inti sistem.'));
        }

        if ($role->users()->count() > 0) {
            return back()->with('error', __('Tidak dapat menghapus role yang masih digunakan oleh pengguna.'));
        }

        $role->delete();

        return redirect()->route('roles.index')->with('success', __('Role berhasil dihapus.'));
    }
}
