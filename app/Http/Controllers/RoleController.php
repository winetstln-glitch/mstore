<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
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
            ->latest()
            ->paginate(10);

        return view('roles.index', compact('roles'));
    }

    /**
     * Get permissions that the current user is allowed to assign.
     */
    private function getAllowedPermissions()
    {
        $user = auth()->user();

        // Super admin / full access users can assign any permission
        if ($user->hasRole('admin')) {
            return Permission::all();
        }

        // Regular users can only assign permissions they personally have
        return $user->role?->permissions ?? collect();
    }

    /**
     * Get standard permission templates, filtered by what the user can assign.
     */
    private function getStandardPermissions()
    {
        $allowedPermissions = $this->getAllowedPermissions();
        $allowedIds = $allowedPermissions->pluck('id')->toArray();

        $allPermissions = Permission::all();

        $technicianNames = [
            'dashboard.view', 'ticket.view', 'ticket.complete', 'installation.view', 'installation.edit',
            'attendance.view', 'attendance.create', 'attendance.edit', 'attendance.report', 'map.view',
            'odp.view', 'odp.edit', 'odc.view', 'odc.edit', 'leave.view', 'leave.create', 'schedule.view',
            'profile.view', 'profile.update', 'notification.view', 'notification.manage',
            'inventory.view', 'inventory.pickup', 'modem-data.view', 'modem-data.create',
            'olt.view', 'ont.view', 'customer.view',
        ];

        $coordinatorNames = [
            'dashboard.view', 'inventory.view', 'inventory.pickup', 'inventory.manage', 'map.view',
            'profile.view', 'profile.update', 'notification.view', 'notification.manage',
            'finance.view', 'finance.report', 'customer.view', 'odc.view', 'odp.view',
        ];

        $leaderNames = [
            'dashboard.view',
            'ticket.view', 'ticket.create', 'ticket.edit', 'ticket.delete', 'ticket.complete',
            'attendance.view', 'attendance.create', 'attendance.edit', 'attendance.report',
            'schedule.view', 'schedule.create', 'schedule.edit', 'schedule.delete',
            'leave.view', 'leave.create', 'leave.edit',
            'map.view',
            'profile.view', 'profile.update',
            'notification.view', 'notification.manage',
            'technician.view', 'technician.create', 'technician.edit', 'technician.delete',
        ];

        $resellerNames = [
            'dashboard.view', 'customer.view', 'customer.create', 'customer.edit', 'customer.export',
            'ticket.view', 'ticket.create', 'ticket.edit', 'ticket.complete',
            'installation.view', 'installation.create', 'installation.edit',
            'router.view', 'hotspot.view', 'pppoe.view', 'map.view',
            'finance.view', 'profile.view', 'profile.update',
            'notification.view', 'notification.manage',
            'package.view', 'region.view',
        ];

        $cashierAtkNames = [
            'atk.view', 'atk.pos', 'atk.report',
            'attendance.view', 'attendance.create', 'attendance.edit',
            'profile.view', 'profile.update',
        ];

        $washNames = ['wash.view', 'wash.pos', 'wash.manage', 'wash.report'];
        $cashierWashNames = array_values(array_unique(array_merge($technicianNames, $washNames)));
        $washEmployeeNames = array_values(array_unique(array_merge($technicianNames, $washNames)));
        $financeStaffNames = array_values(array_unique(array_merge([
            'dashboard.view', 'finance.view', 'finance.create', 'finance.edit', 'finance.delete', 'finance.report',
            'inventory.view', 'inventory.manage', 'customer.view', 'customer.create', 'customer.edit',
            'profile.view', 'profile.update', 'notification.view', 'notification.manage',
            'attendance.view', 'attendance.report',
        ])));
        $hrdManagerNames = array_values(array_unique(array_merge([
            'dashboard.view',
            'employee.view', 'employee.create', 'employee.edit', 'employee.delete',
            'user.view', 'user.create', 'user.edit', 'user.delete',
            'role.view', 'role.create', 'role.edit', 'role.delete',
            'inventory.view', 'inventory.manage', 'inventory.pickup',
            'attendance.view', 'attendance.create', 'attendance.edit', 'attendance.report',
            'leave.view', 'leave.create', 'leave.edit',
            'schedule.view', 'schedule.create', 'schedule.edit',
            'profile.view', 'profile.update',
            'notification.view', 'notification.manage',
        ])));

        $nocGroups = [
            'Dashboard', 'Customer Management', 'Ticket Management', 'Installation Management',
            'Router Management', 'OLT Management', 'ODC Management', 'ODP Management',
            'Closure Management', 'HTB Management', 'PPPoE Management', 'Hotspot Management',
            'Radius', 'Map', 'Network Monitor', 'Profile', 'Notification',
            'Region Management', 'Package Management',
        ];

        $templates = [
            'Administrator' => $allPermissions->pluck('id')->values()->toArray(),
            'Network Operations Center' => $allPermissions->whereIn('group', $nocGroups)->pluck('id')->values()->toArray(),
            'Teknisi' => $allPermissions->whereIn('name', $technicianNames)->pluck('id')->values()->toArray(),
            'Leader' => $allPermissions->whereIn('name', $leaderNames)->pluck('id')->values()->toArray(),
            'Koordinator' => $allPermissions->whereIn('name', $coordinatorNames)->pluck('id')->values()->toArray(),
            'Reseller' => $allPermissions->whereIn('name', $resellerNames)->pluck('id')->values()->toArray(),
            'Staf Keuangan' => $allPermissions->whereIn('name', $financeStaffNames)->pluck('id')->values()->toArray(),
            'Kasir ATK' => $allPermissions->whereIn('name', $cashierAtkNames)->pluck('id')->values()->toArray(),
            'Kasir Wash' => $allPermissions->whereIn('name', $cashierWashNames)->pluck('id')->values()->toArray(),
            'Karyawan Wash' => $allPermissions->whereIn('name', $washEmployeeNames)->pluck('id')->values()->toArray(),
            'Manager HRD' => $allPermissions->whereIn('name', $hrdManagerNames)->pluck('id')->values()->toArray(),
            'Customer' => [],
        ];

        // Filter each template to only include permissions the user can assign
        foreach ($templates as $key => $ids) {
            $templates[$key] = array_values(array_intersect($ids, $allowedIds));
        }

        return $templates;
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $permissions = Permission::getGroupedPermissions();
        $allowedPermissions = $this->getAllowedPermissions();

        // Filter grouped permissions to only show allowed ones
        $filteredPermissions = [];
        foreach ($permissions as $tab => $groups) {
            foreach ($groups as $group => $perms) {
                $filteredPerms = $perms->filter(function ($permission) use ($allowedPermissions) {
                    return $allowedPermissions->contains('id', $permission->id);
                });
                if ($filteredPerms->isNotEmpty()) {
                    $filteredPermissions[$tab][$group] = $filteredPerms;
                }
            }
        }

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

        return redirect()->route('roles.index')->with('success', __('Role berhasil dibuat.'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Role $role)
    {
        $allowedPermissions = $this->getAllowedPermissions();

        // Get full grouped permissions
        $permissions = Permission::getGroupedPermissions();

        // Filter grouped permissions
        $filteredPermissions = [];
        foreach ($permissions as $tab => $groups) {
            foreach ($groups as $group => $perms) {
                $filteredPerms = $perms->filter(function ($permission) use ($allowedPermissions) {
                    return $allowedPermissions->contains('id', $permission->id);
                });
                if ($filteredPerms->isNotEmpty()) {
                    $filteredPermissions[$tab][$group] = $filteredPerms;
                }
            }
        }

        $rolePermissions = $role->permissions->pluck('id')->toArray();

        // Only keep permissions that the user is allowed to see/assign
        $rolePermissions = array_intersect($rolePermissions, $allowedPermissions->pluck('id')->toArray());

        $standardPermissions = $this->getStandardPermissions();

        return view('roles.edit', compact('role', 'filteredPermissions', 'rolePermissions', 'standardPermissions'));
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

        // Check slug uniqueness (excluding current role)
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

        // Protect critical roles from having their name/slug changed
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

        if (!empty($validated['permissions'])) {
            $role->permissions()->sync($validated['permissions']);
        } else {
            $role->permissions()->detach();
        }

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