<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Str;

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
            ->latest()
            ->paginate(10);

        return view('roles.index', compact('roles'));
    }

    private function getStandardPermissions()
    {
        $allPermissions = Permission::all();

        $nocGroups = [
            'Dashboard', 'Customer Management', 'Ticket Management', 'Router Management',
            'OLT Management', 'ODC Management', 'ODP Management', 'Closure Management', 'HTB Management',
            'PPPoE Management', 'Radius', 'Map', 'Network Monitor', 'Profile', 'Notification',
        ];

        $technicianNames = [
            'dashboard.view', 'ticket.view', 'ticket.edit', 'installation.view', 'installation.edit',
            'attendance.view', 'attendance.create', 'attendance.edit', 'attendance.report', 'map.view',
            'odp.view', 'odp.edit', 'odc.edit', 'leave.view', 'leave.create', 'schedule.view',
            'profile.view', 'profile.update', 'notification.view', 'notification.manage',
            'inventory.view', 'inventory.pickup',
        ];

        $coordinatorNames = [
            'dashboard.view', 'inventory.view', 'inventory.pickup', 'map.view',
            'profile.view', 'profile.update', 'notification.view', 'notification.manage',
            'finance.view',
        ];

        $leaderNames = [
            'dashboard.view',
            'ticket.view', 'ticket.create', 'ticket.edit', 'ticket.delete',
            'attendance.view', 'attendance.report',
            'schedule.view',
            'map.view',
            'profile.view', 'profile.update',
            'notification.view', 'notification.manage',
        ];

        $resellerNames = [
            'dashboard.view', 'customer.view', 'customer.create', 'customer.edit',
            'ticket.view', 'ticket.create', 'ticket.edit', 'installation.view',
            'router.view', 'hotspot.view', 'pppoe.view', 'map.view',
            'finance.view', 'profile.view', 'profile.update',
            'notification.view', 'notification.manage',
        ];

        $cashierAtkNames = [
            'atk.view', 'atk.pos', 'atk.report',
            'attendance.view', 'attendance.create', 'attendance.edit',
            'profile.view', 'profile.update',
        ];

        $washNames = ['wash.view', 'wash.pos', 'wash.manage', 'wash.report'];
        $cashierWashNames = array_values(array_unique(array_merge($technicianNames, $washNames)));
        $washEmployeeNames = array_values(array_unique(array_merge($technicianNames, $washNames)));
        $financeStaffNames = $technicianNames;
        $hrdManagerNames = $technicianNames;

        return [
            'Administrator' => $allPermissions->pluck('id')->values()->toArray(),
            'Network Operations Center' => $allPermissions->whereIn('group', $nocGroups)->pluck('id')->values()->toArray(),
            'Technician' => $allPermissions->whereIn('name', $technicianNames)->pluck('id')->values()->toArray(),
            'Leader' => $allPermissions->whereIn('name', $leaderNames)->pluck('id')->values()->toArray(),
            'Coordinator' => $allPermissions->whereIn('name', $coordinatorNames)->pluck('id')->values()->toArray(),
            'Reseller' => $allPermissions->whereIn('name', $resellerNames)->pluck('id')->values()->toArray(),
            'Finance Staff' => $allPermissions->whereIn('name', $financeStaffNames)->pluck('id')->values()->toArray(),
            'Kasir ATK' => $allPermissions->whereIn('name', $cashierAtkNames)->pluck('id')->values()->toArray(),
            'Kasir Wash' => $allPermissions->whereIn('name', $cashierWashNames)->pluck('id')->values()->toArray(),
            'Karyawan Wash' => $allPermissions->whereIn('name', $washEmployeeNames)->pluck('id')->values()->toArray(),
            'HRD Manager' => $allPermissions->whereIn('name', $hrdManagerNames)->pluck('id')->values()->toArray(),
            'Customer' => [],
        ];
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $permissions = Permission::all()->groupBy('group');
        $standardPermissions = $this->getStandardPermissions();

        return view('roles.create', compact('permissions', 'standardPermissions'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'label' => 'required|string|max:255',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id',
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

        if (isset($validated['permissions'])) {
            $role->permissions()->sync($validated['permissions']);
        }

        return redirect()->route('roles.index')->with('success', __('Role created successfully.'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Role $role)
    {
        $permissions = Permission::all()->groupBy('group');
        $rolePermissions = $role->permissions->pluck('id')->toArray();
        $standardPermissions = $this->getStandardPermissions();

        return view('roles.edit', compact('role', 'permissions', 'rolePermissions', 'standardPermissions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'label' => 'required|string|max:255',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        // Don't update name for admin role to prevent system lockout
        // For all roles, we only update the label, not the internal name (slug) to maintain consistency
        $role->update([
            'label' => $validated['label'],
        ]);

        if (isset($validated['permissions'])) {
            $role->permissions()->sync($validated['permissions']);
        } else {
            $role->permissions()->detach();
        }

        return redirect()->route('roles.index')->with('success', __('Role updated successfully.'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role)
    {
        if ($role->name === 'admin') {
            return back()->with('error', __('Cannot delete Admin role.'));
        }

        if ($role->users()->count() > 0) {
            return back()->with('error', __('Cannot delete role that is assigned to users.'));
        }

        $role->delete();

        return redirect()->route('roles.index')->with('success', __('Role deleted successfully.'));
    }
}
