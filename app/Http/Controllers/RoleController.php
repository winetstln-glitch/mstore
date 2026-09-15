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
        $hiddenNames = Role::hiddenInUserManagement();
        $systemNames = Role::allSystemRoleNames();

        $roles = Role::query()
            ->withCount('users')
            ->withCount('permissions')
            ->whereNotIn('name', $hiddenNames)
            ->when(request('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('label', 'like', "%{$search}%");
                });
            })
            ->when(request('sort'), function ($query) {
                $sortColumn = request('sort', 'created_at');
                $sortDirection = request('direction', 'desc');
                $allowedColumns = ['name', 'label', 'is_system', 'created_at', 'users_count', 'permissions_count'];

                if (in_array($sortColumn, $allowedColumns, true)) {
                    if ($sortColumn === 'is_system') {
                        $query->orderByRaw('is_system desc, name asc');
                    } else {
                        $query->orderBy($sortColumn, $sortDirection);
                    }
                }
            }, function ($query) use ($systemNames) {
                $query->orderByRaw(
                    'case when name in ('.implode(',', array_fill(0, count($systemNames), '?')).') then 0 else 1 end, name asc',
                    $systemNames
                );
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
        $superAdminRoles = (array) config('auth.super_admin_roles', []);

        foreach ($superAdminRoles as $superRole) {
            if ($user?->hasRole($superRole)) {
                return Permission::all();
            }
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
        $systemNames = Role::allSystemRoleNames();

        if (in_array($roleName, $systemNames, true)) {
            return back()
                ->withErrors([
                    'label' => __('Nama role ":name" adalah nama system role yang dipesan. Pilih nama lain.', ['name' => $roleName]),
                ])
                ->withInput();
        }

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
            'is_system' => false,
        ]);

        if (! empty($validated['permissions'])) {
            $role->permissions()->sync($validated['permissions']);
        }
        Cache::forget("sidebar.permission_map.role.{$role->id}");
        Cache::forget('sidebar.menu.tree.v11');

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

        $systemNames = Role::allSystemRoleNames();

        if ((bool) $role->is_system || in_array($role->name, $systemNames, true)) {
            $role->update([
                'label' => $validated['label'],
            ]);
        } else {
            if (in_array($newSlug, $systemNames, true)) {
                return back()
                    ->withErrors([
                        'label' => __('Nama system role dipesan. Pilih nama lain.'),
                    ])
                    ->withInput();
            }
            $role->update([
                'name' => $newSlug,
                'label' => $validated['label'],
            ]);
        }

        $hiddenPermissions = session()->pull('role_hidden_permissions_'.$role->id, []);
        $newPermissions = $validated['permissions'] ?? [];
        $finalPermissions = array_unique(array_merge($newPermissions, $hiddenPermissions));

        if ((bool) $role->is_system || in_array($role->name, $systemNames, true)) {
            // System role tidak boleh diedit permissionnya via UI.
            // Permission sync hanya diizinkan lewat artisan roles:normalize.
        } else {
            if (! empty($finalPermissions)) {
                $role->permissions()->sync($finalPermissions);
            } else {
                $role->permissions()->detach();
            }
        }
        Cache::forget("sidebar.permission_map.role.{$role->id}");
        Cache::forget('sidebar.menu.tree.v11');

        return redirect()->route('roles.index')->with('success', __('Role berhasil diperbarui.'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role)
    {
        $systemNames = Role::allSystemRoleNames();
        if ((bool) $role->is_system || in_array($role->name, $systemNames, true)) {
            return back()->with('error', __('Role sistem tidak dapat dihapus (atur is_system=false dulu jika memang dibutuhkan).'));
        }

        if ($role->users()->count() > 0) {
            return back()->with('error', __('Tidak dapat menghapus role yang masih digunakan oleh pengguna.'));
        }

        $role->permissions()->detach();
        Cache::forget("sidebar.permission_map.role.{$role->id}");
        Cache::forget('sidebar.menu.tree.v11');
        $role->delete();

        return redirect()->route('roles.index')->with('success', __('Role berhasil dihapus.'));
    }
}
